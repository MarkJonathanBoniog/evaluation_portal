<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = [
            'users.manage',
            'departments.manage',
            'colleges.manage',
            'programs.manage',
            'courses.manage',
            'sections.manage',
            'rosters.manage',
            'evaluations.submit',
            'evaluations.view_own_summary',
            'evaluations.view_all',
        ];
        
        foreach ($perms as $p) { Permission::firstOrCreate(['name' => $p]); }

        $roles = [
            'student'     => ['evaluations.submit'],
            'instructor'  => ['evaluations.view_own_summary'],
            'chairman'    => ['evaluations.view_own_summary','departments.manage','programs.manage','courses.manage','sections.manage','rosters.manage'],
            'dean'         => ['evaluations.view_own_summary','colleges.manage','departments.manage','programs.manage','courses.manage','sections.manage'],
            'ced'         => ['evaluations.view_own_summary','colleges.manage','departments.manage','programs.manage','courses.manage','sections.manage'],
            'systemadmin' => Permission::pluck('name')->all(),
        ];

        foreach ($roles as $role => $allowed) {
            $r = Role::firstOrCreate(['name' => $role]);
            $r->syncPermissions($allowed);
        }
    }
}
