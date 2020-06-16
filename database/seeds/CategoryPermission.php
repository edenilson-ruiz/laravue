<?php

use Illuminate\Database\Seeder;
use App\Laravue\Models\Role;
use App\Laravue\Models\Permission;

class CategoryPermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::findOrCreate('view category', 'api');
        Permission::findOrCreate('manage category', 'api');

        // Assign new permissions to admin group
        $adminRole = Role::findByName(App\Laravue\Acl::ROLE_ADMIN);
        $adminRole->givePermissionTo(['view category', 'manage category']);
    }
}
