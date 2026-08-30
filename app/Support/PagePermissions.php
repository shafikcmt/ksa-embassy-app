<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for the agency "page access" modules.
 *
 * Each agency staff account can be granted access to a subset of the agency
 * modules below. Agency admins always have every module; only agency_staff
 * accounts are ever restricted. The same map drives:
 *   - the EnsurePageAccess middleware (route-level enforcement),
 *   - the sidebar navigation gating (layouts.agency-app),
 *   - the Staff Accounts create/edit checkboxes (StaffController + view),
 *   - the access_* permission seeding (migration + RolesPermissionsSeeder).
 *
 * Keep the keys stable — they are used as the middleware parameter
 * (e.g. page-access:hr) and stored implicitly through the mapped permissions.
 */
class PagePermissions
{
    /**
     * key => [permission, label, icon, description]
     */
    public const MODULES = [
        'hr' => [
            'permission'  => 'access_hr',
            'label'       => 'HR / Candidates',
            'icon'        => 'bi-person-vcard',
            'description' => 'Candidate records, documents and printing.',
        ],
        'embassy_list' => [
            'permission'  => 'access_embassy_list',
            'label'       => 'Embassy Lists',
            'icon'        => 'bi-list-ol',
            'description' => 'Create and manage embassy submission lists.',
        ],
        'agents' => [
            'permission'  => 'access_agents',
            'label'       => 'Agents',
            'icon'        => 'bi-people',
            'description' => 'Manage the agency\'s recruitment agents.',
        ],
        'license' => [
            'permission'  => 'access_license',
            'label'       => 'License',
            'icon'        => 'bi-patch-check',
            'description' => 'View the agency licence details.',
        ],
        'notes' => [
            'permission'  => 'access_notes',
            'label'       => 'Smart Notes',
            'icon'        => 'bi-journal-text',
            'description' => 'Personal notes and reminders.',
        ],
        'erp' => [
            'permission'  => 'access_erp',
            'label'       => 'ERP Suite',
            'icon'        => 'bi-cash-stack',
            'description' => 'Accounting & operations: MOFA, stamping, delivery, ledgers, expenses.',
        ],
        'attendance' => [
            'permission'  => 'access_attendance',
            'label'       => 'Attendance',
            'icon'        => 'bi-calendar-check',
            'description' => 'Shifts, holidays, leave types and attendance settings.',
        ],
    ];

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return self::MODULES;
    }

    /** @return string[] All access_* permission names. */
    public static function permissions(): array
    {
        return array_values(array_map(fn ($m) => $m['permission'], self::MODULES));
    }

    /** @return string[] The module keys (hr, embassy_list, ...). */
    public static function keys(): array
    {
        return array_keys(self::MODULES);
    }

    public static function permissionFor(string $key): ?string
    {
        return self::MODULES[$key]['permission'] ?? null;
    }

    /**
     * Can the given user open the module?
     *
     * Super admins and agency admins always pass (admins hold every access_*
     * permission and must never be restricted). Restricted staff pass only when
     * they hold the module's access_* permission directly. Uncached/unknown
     * modules deny by default.
     */
    public static function userCanAccess(?User $user, string $key): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAgencyAdmin()) {
            return true;
        }

        $permission = self::permissionFor($key);

        // Use can() (not hasPermissionTo) so a not-yet-seeded permission returns
        // false gracefully instead of throwing.
        return $permission ? $user->can($permission) : false;
    }
}
