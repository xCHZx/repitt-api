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
        $roleOwner = Role::create(['name' => 'Owner']);
        $roleVisitor = Role::create(['name' => 'Visitor']);

        Permission::create(['name' => 'owner.test'])->syncRoles([$roleOwner, $roleVisitor]);
        Permission::create(['name' => 'visitor.test'])->syncRoles([$roleVisitor]);

        Permission::create(['name' => 'auth.logout'])->syncRoles([$roleVisitor, $roleOwner]);

        Permission::create(['name' => 'business.store'])->syncRoles([$roleOwner]);
    }
}
