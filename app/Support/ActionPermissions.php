<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Fine-grained ACTION permissions an agency admin can grant to individual staff.
 *
 * Deliberately separate from App\Support\PagePermissions: those are page/module
 * gates that also drive the sidebar + page-access middleware. These are per-action
 * grants that must NEVER appear as a navigable module. Admins always hold all of
 * them; agency_staff hold only what an admin grants on the Staff Accounts screen.
 */
class ActionPermissions
{
    /** key => [permission, label, icon, description]. key == permission by design. */
    public const ACTIONS = [
        'erp_receive_payment' => [
            'permission'  => 'erp_receive_payment',
            'label'       => 'Receive Payment (Delivery/Double MOFA)',
            'icon'        => 'bi-cash-coin',
            'description' => 'Record customer payments on Delivery and Double MOFA records.',
        ],
    ];

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return self::ACTIONS;
    }

    /** @return string[] the action keys. */
    public static function keys(): array
    {
        return array_keys(self::ACTIONS);
    }

    /** @return string[] all action permission names. */
    public static function permissions(): array
    {
        return array_values(array_map(fn ($a) => $a['permission'], self::ACTIONS));
    }

    public static function permissionFor(string $key): ?string
    {
        return self::ACTIONS[$key]['permission'] ?? null;
    }

    /** Does the user hold this action grant? Admins/super-admins always pass. */
    public static function userHas(?User $user, string $key): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin() || $user->isAgencyAdmin()) {
            return true;
        }
        $permission = self::permissionFor($key);
        if (! $permission) {
            return false;
        }

        // Defensive: if the permission row doesn't exist yet (migration not run
        // or rolled back), a missing permission must read as "no access", never a
        // 500. Laravel's can() already routes through Spatie's checkPermissionTo()
        // which swallows PermissionDoesNotExist; this guard also covers any direct
        // hasPermissionTo path or a disabled register_permission_check_method.
        try {
            return $user->can($permission);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }
}
