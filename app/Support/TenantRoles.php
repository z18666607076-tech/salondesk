<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TenantRoles
{
    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        'services.view',
        'services.manage',
        'staff.view',
        'staff.manage',
        'schedules.manage',
        'appointments.view',
        'appointments.manage',
        'customers.view',
        'customers.manage',
        'billing.manage',
        'assistant.use',
    ];

    /**
     * @var list<string>
     */
    public const STAFF_PERMISSIONS = [
        'services.view',
        'staff.view',
        'schedules.manage',
        'appointments.view',
        'appointments.manage',
        'customers.view',
        'customers.manage',
        'assistant.use',
    ];

    /**
     * Front desk: every appointment in the salon, no billing and no staff admin.
     *
     * @var list<string>
     */
    public const RECEPTIONIST_PERMISSIONS = [
        'services.view',
        'staff.view',
        'appointments.view',
        'appointments.manage',
        'customers.view',
        'customers.manage',
        'assistant.use',
    ];

    public static function ensure(Tenant $tenant): void
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $owner = Role::findOrCreate('owner', 'web');
        $staff = Role::findOrCreate('staff', 'web');
        $receptionist = Role::findOrCreate('receptionist', 'web');

        $owner->syncPermissions(self::PERMISSIONS);
        $staff->syncPermissions(self::STAFF_PERMISSIONS);
        $receptionist->syncPermissions(self::RECEPTIONIST_PERMISSIONS);

        setPermissionsTeamId($previous);
    }

    public static function assign(User $user, string $role): void
    {
        $previous = getPermissionsTeamId();
        setPermissionsTeamId($user->tenant_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->assignRole($role);

        setPermissionsTeamId($previous);
    }
}
