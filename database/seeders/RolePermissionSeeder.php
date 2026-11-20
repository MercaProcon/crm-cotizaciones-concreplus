<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions.
     */
    public function run(): void
    {
        // Copia de Concreplus (Fase 1): sin módulo Fachadas ni rol "agente"
        // — eran exclusivos de PROCON. Si se reactivan más adelante, restaurar
        // desde el seeder original de crm-cotizaciones-procon.
        $permissions = [
            'quotations.view.all',
            'quotations.view.assigned',
            'quotations.view.search',
            'quotations.assign',
            'quotations.reassign',
            'quotations.update.status',
            'quotations.add.followup',
            'duplicates.view',
            'reports.view',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'admin' => $permissions,
            'coordinadora' => [
                'quotations.view.all',
                'quotations.view.search',
                'quotations.assign',
                'quotations.reassign',
                'quotations.update.status',
                'quotations.add.followup',
                'duplicates.view',
                'reports.view',
            ],
            'vendedora' => [
                'quotations.view.assigned',
                'quotations.view.search',
                'quotations.update.status',
                'quotations.add.followup',
                'duplicates.view',
            ],
            'ventanilla' => [
                'quotations.view.search',
                'duplicates.view',
            ],
        ];

        foreach ($rolePermissions as $role => $rolePermissionNames) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($rolePermissionNames);
        }
    }
}
