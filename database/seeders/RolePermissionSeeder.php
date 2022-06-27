<?php

namespace Database\Seeders;


use DB;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_permission = [
            [
                'permission_id'  => 4,
                'role_id'        => 1,
            ],
            [
                'permission_id'  => 5,
                'role_id'        => 1,
            ],
            [
                'permission_id'  => 6,
                'role_id'        => 1,
            ],
            [
                'permission_id'  => 7,
                'role_id'        => 1,
            ],
        ];

        foreach( $role_permission as $value ){
            DB::table('role_has_permissions')->insert(
                $value
            );
        }
    }
}
