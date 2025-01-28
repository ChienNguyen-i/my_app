<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'type' => 0,
            'status' => 1
        ]);
        User::factory()->create([
            'name' => 'user',
            'email' => 'user@gmail.com',
            'type' => 1,
            'status' => 1
        ]);
        User::factory(500)->create();

        Category::create(['name' => 'Home', 'url' => '/', 'order' => 1, 'type' => 0, 'status' => 1]);
        Category::create(['name' => 'About', 'url' => '/about', 'order' => 2, 'type' => 0, 'status' => 1]);
        Category::create(['name' => 'Services', 'url' => '/services', 'order' => 3, 'type' => 0, 'status' => 1]);
        Category::create(['name' => 'Contact', 'url' => '/contact', 'order' => 4, 'type' => 0, 'status' => 1]);
        $services = Category::where('name', 'Services')->first();
        Category::create(['name' => 'Web Development', 'url' => '/services/web-development', 'parent_id' => $services->id, 'order' => 1, 'type' => 0, 'status' => 1]);
        Category::create(['name' => 'SEO', 'url' => '/services/seo', 'parent_id' => $services->id, 'order' => 2, 'type' => 0, 'status' => 1]);

        Role::create(['name' => 'Admin']);

        Permission::create(['name' => 'User', 'order' => $i = 0]);
        $services = Permission::where('name', 'User')->first();
        Permission::create(['name' => 'User trashed', 'key_code' => 'user-trashed', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User index', 'key_code' => 'user-index', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User store', 'key_code' => 'user-store', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User show', 'key_code' => 'user-show', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User update', 'key_code' => 'user-update', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User destroy', 'key_code' => 'user-destroy', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User restore', 'key_code' => 'user-restore', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User delete completely', 'key_code' => 'user-delete-completely', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User import excel', 'key_code' => 'user-import-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User export excel', 'key_code' => 'user-export-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'User export pdf', 'key_code' => 'user-export-pdf', 'parent_id' => $services->id, 'order' => ++$i]);

        Permission::create(['name' => 'Role', 'order' => $i = 0]);
        $services = Permission::where('name', 'Role')->first();
        Permission::create(['name' => 'Role trashed', 'key_code' => 'role-trashed', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role index', 'key_code' => 'role-index', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role store', 'key_code' => 'role-store', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role show', 'key_code' => 'role-show', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role update', 'key_code' => 'role-update', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role destroy', 'key_code' => 'role-destroy', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role restore', 'key_code' => 'role-restore', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role delete completely', 'key_code' => 'role-delete-completely', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role import excel', 'key_code' => 'role-import-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role export excel', 'key_code' => 'role-export-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role export pdf', 'key_code' => 'role-export-pdf', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role assign role', 'key_code' => 'role-assign-role', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Role revoke role', 'key_code' => 'role-revoke-role', 'parent_id' => $services->id, 'order' => ++$i]);

        Permission::create(['name' => 'Permission', 'order' => $i = 0]);
        $services = Permission::where('name', 'Permission')->first();
        Permission::create(['name' => 'Permission trashed', 'key_code' => 'permission-trashed', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission index', 'key_code' => 'permission-index', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission store', 'key_code' => 'permission-store', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission show', 'key_code' => 'permission-show', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission update', 'key_code' => 'permission-update', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission destroy', 'key_code' => 'permission-destroy', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission restore', 'key_code' => 'permission-restore', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission delete completely', 'key_code' => 'permission-delete-completely', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission import excel', 'key_code' => 'permission-import-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission export excel', 'key_code' => 'permission-export-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission export pdf', 'key_code' => 'permission-export-pdf', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission assign permission', 'key_code' => 'permission-assign-permission', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Permission revoke permission', 'key_code' => 'permission-revoke-permission', 'parent_id' => $services->id, 'order' => ++$i]);

        Permission::create(['name' => 'Category', 'order' => $i = 0]);
        $services = Permission::where('name', 'Category')->first();
        Permission::create(['name' => 'Category trashed', 'key_code' => 'category-trashed', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category index', 'key_code' => 'category-index', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category store', 'key_code' => 'category-store', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category show', 'key_code' => 'category-show', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category update', 'key_code' => 'category-update', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category destroy', 'key_code' => 'category-destroy', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category restore', 'key_code' => 'category-restore', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category delete completely', 'key_code' => 'category-delete-completely', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category import excel', 'key_code' => 'category-import-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category export excel', 'key_code' => 'category-export-excel', 'parent_id' => $services->id, 'order' => ++$i]);
        Permission::create(['name' => 'Category export pdf', 'key_code' => 'category-export-pdf', 'parent_id' => $services->id, 'order' => ++$i]);

        DB::table('role_user')->insert(['role_id' => 1, 'user_id' => 1]);

        $p = 2;
        for ($i = 0; $i <= 10; $i++) {
            DB::table('permission_role')->insert(['permission_id' => $p++, 'role_id' => 1]);
        }
        $p++;
        for ($i = 0; $i <= 12; $i++) {
            DB::table('permission_role')->insert(['permission_id' => $p++, 'role_id' => 1]);
        }
        $p++;
        for ($i = 0; $i <= 12; $i++) {
            DB::table('permission_role')->insert(['permission_id' => $p++, 'role_id' => 1]);
        }
        $p++;
        for ($i = 0; $i <= 10; $i++) {
            DB::table('permission_role')->insert(['permission_id' => $p++, 'role_id' => 1]);
        }
    }
}
