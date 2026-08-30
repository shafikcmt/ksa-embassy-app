<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PagePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Agency Staff Accounts — the agency admin creates staff logins and chooses
 * exactly which modules (HR, Embassy, Agents, License, Notes) each one may open.
 *
 * Tenancy: staff are always scoped to the admin's own agency. Every action
 * re-checks that the target user belongs to this agency and carries the
 * agency_staff role, so an admin can never read or mutate another tenant's
 * users. Admin-only: every method aborts for non-admins.
 */
class StaffController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $agencyId = auth()->user()->agency_id;

        $staff = User::where('agency_id', $agencyId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'agency_staff'))
            ->orderBy('name')
            ->get();

        $modules = PagePermissions::all();

        // module keys each staff member currently holds, for display + edit form.
        $staffAccess = [];
        foreach ($staff as $member) {
            $staffAccess[$member->id] = collect($modules)
                ->filter(fn ($meta) => $member->hasPermissionTo($meta['permission']))
                ->keys()
                ->all();
        }

        return view('agency.staff.index', compact('staff', 'modules', 'staffAccess'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'modules'   => ['array'],
            'modules.*' => ['string', Rule::in(PagePermissions::keys())],
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => Hash::make($validated['password']),
            'agency_id'      => auth()->user()->agency_id,
            'is_super_admin' => false,
            'is_active'      => true,
        ]);

        $user->assignRole('agency_staff');
        $user->syncPermissions($this->permissionsFor($validated['modules'] ?? []));

        AuditLog::record('create_staff', $user, [], $this->auditSnapshot($user));

        return redirect()->route('staff.index')
            ->with('success', 'Staff account "' . $user->name . '" created.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();
        $this->authorizeStaffMember($user);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'modules'   => ['array'],
            'modules.*' => ['string', Rule::in(PagePermissions::keys())],
        ]);

        $old = $this->auditSnapshot($user);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncPermissions($this->permissionsFor($validated['modules'] ?? []));

        AuditLog::record('update_staff', $user, $old, $this->auditSnapshot($user->fresh()));

        return redirect()->route('staff.index')
            ->with('success', 'Staff account "' . $user->name . '" updated.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin();
        $this->authorizeStaffMember($user);

        $name = $user->name;
        AuditLog::record('delete_staff', $user, $this->auditSnapshot($user), []);
        $user->delete();

        return redirect()->route('staff.index')->with('success', "Staff account \"$name\" deleted.");
    }

    /** Only agency admins may manage staff accounts. */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
    }

    /**
     * The target must be an agency_staff account inside the admin's own agency.
     * This is the multi-tenant guard: it blocks touching another agency's users
     * as well as elevating/deleting the admin's own account.
     */
    private function authorizeStaffMember(User $user): void
    {
        abort_unless(
            $user->agency_id === auth()->user()->agency_id
                && $user->hasRole('agency_staff')
                && ! $user->isSuperAdmin(),
            403
        );
    }

    /** Map submitted module keys to their access_* permission names. */
    private function permissionsFor(array $moduleKeys): array
    {
        return collect($moduleKeys)
            ->map(fn ($key) => PagePermissions::permissionFor($key))
            ->filter()
            ->values()
            ->all();
    }

    /** Compact record for the audit log (never store the password hash). */
    private function auditSnapshot(User $user): array
    {
        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'is_active' => $user->is_active,
            'modules'   => $user->getDirectPermissions()->pluck('name')->values()->all(),
        ];
    }
}
