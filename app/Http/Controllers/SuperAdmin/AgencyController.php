<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Agency::with('activeSubscription.plan')
            ->withCount('users');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('owner_name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('license_number', 'like', "%$search%")
                ->orWhere('rl_number', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agencies = $query->latest()->paginate(15)->withQueryString();

        return view('super-admin.agencies.index', compact('agencies'));
    }

    public function create()
    {
        $plans = Plan::active()->get();
        return view('super-admin.agencies.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'owner_name'          => 'nullable|string|max:255',
            'license_number'      => 'nullable|string|max:100',
            'rl_number'           => 'nullable|string|max:100',
            'address'             => 'nullable|string',
            'phone'               => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:255',
            'print_logo'          => 'required|boolean',
            'license_expiry_date' => 'nullable|date',
            'status'              => 'required|in:active,suspended',
            'admin_name'          => 'required|string|max:255',
            'admin_email'         => 'required|email|unique:users,email',
            'admin_password'      => 'required|string|min:8',
            'plan_id'             => 'nullable|exists:plans,id',
            'logo'                => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            $agency = Agency::create([
                'name'                => $validated['name'],
                'owner_name'          => $validated['owner_name'] ?? null,
                'slug'                => Str::slug($validated['name']) . '-' . Str::random(5),
                'license_number'      => $validated['license_number'] ?? null,
                'rl_number'           => $validated['rl_number'] ?? null,
                'address'             => $validated['address'] ?? null,
                'phone'               => $validated['phone'] ?? null,
                'email'               => $validated['email'] ?? null,
                'logo'                => $logoPath,
                'print_logo'          => (bool) $validated['print_logo'],
                'license_expiry_date' => $validated['license_expiry_date'] ?? null,
                'status'              => $validated['status'],
            ]);

            $admin = User::create([
                'name'     => $validated['admin_name'],
                'email'    => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'agency_id'=> $agency->id,
                'is_active'=> true,
            ]);
            $admin->assignRole('agency_admin');

            if (! empty($validated['plan_id'])) {
                $plan = Plan::findOrFail($validated['plan_id']);
                Subscription::create([
                    'agency_id'      => $agency->id,
                    'plan_id'        => $plan->id,
                    'start_date'     => now(),
                    'end_date'       => now()->addDays($plan->duration_days),
                    'status'         => 'trial',
                    'payment_status' => 'pending',
                    'amount'         => $plan->price,
                ]);
            }

            AuditLog::record('create_agency', $agency, [], $agency->toArray());
        });

        return redirect()->route('super-admin.agencies.index')
            ->with('success', 'Agency created successfully.');
    }

    public function show(Agency $agency)
    {
        $agency->load(['users.roles', 'subscriptions.plan']);
        return view('super-admin.agencies.show', compact('agency'));
    }

    public function edit(Agency $agency)
    {
        $adminUser = $agency->adminUser();
        return view('super-admin.agencies.edit', compact('agency', 'adminUser'));
    }

    public function update(Request $request, Agency $agency)
    {
        $adminUser = $agency->adminUser();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'owner_name'          => 'nullable|string|max:255',
            'license_number'      => 'nullable|string|max:100',
            'rl_number'           => 'nullable|string|max:100',
            'address'             => 'nullable|string',
            'phone'               => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:255',
            'print_logo'          => 'required|boolean',
            'license_expiry_date' => 'nullable|date',
            'status'              => 'required|in:active,suspended',
            'logo'                => 'nullable|image|max:2048',
            // Login account (agency admin) — only validated when an admin exists.
            'login_email'         => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($adminUser?->id),
            ],
            'new_password'        => 'nullable|string|min:8|confirmed',
        ]);

        $old = $agency->toArray();

        $agencyData = collect($validated)->only([
            'name', 'owner_name', 'license_number', 'rl_number', 'address',
            'phone', 'email', 'license_expiry_date', 'status',
        ])->all();
        $agencyData['print_logo'] = (bool) $validated['print_logo'];

        if ($request->hasFile('logo')) {
            $agencyData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $agency->update($agencyData);

        // Super Admin may also update the agency admin's login email / password.
        if ($adminUser) {
            $userData = [];
            if ($request->filled('login_email')) {
                $userData['email'] = $validated['login_email'];
            }
            if ($request->filled('new_password')) {
                $userData['password'] = Hash::make($validated['new_password']);
            }
            if ($userData) {
                $adminUser->update($userData);
            }
        }

        AuditLog::record('update_agency', $agency, $old, $agency->fresh()->toArray());

        return redirect()->route('super-admin.agencies.index')
            ->with('success', 'Agency updated successfully.');
    }

    public function destroy(Agency $agency)
    {
        AuditLog::record('delete_agency', $agency, $agency->toArray(), []);
        $agency->delete();
        return redirect()->route('super-admin.agencies.index')
            ->with('success', 'Agency deleted.');
    }

    public function toggleStatus(Agency $agency)
    {
        $agency->update([
            'status' => $agency->status === 'active' ? 'suspended' : 'active',
        ]);
        AuditLog::record('toggle_agency_status', $agency);
        return back()->with('success', 'Agency status updated.');
    }
}
