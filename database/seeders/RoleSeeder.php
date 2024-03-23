<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleClient = Role::create(['name' => 'Client']);
        $roleVisitor = Role::create(['name' => 'Visitor']);

        Permission::create(['name' => 'client.test'])->syncRoles([$roleClient, $roleVisitor]);
        Permission::create(['name' => 'visitor.test'])->syncRoles([$roleVisitor]);

        Permission::create(['name' => 'auth.logout'])->syncRoles([$roleVisitor, $roleClient]);

        // Permission::create(['name' => 'business.store'])->syncRoles([$roleClient]);
    }
}
