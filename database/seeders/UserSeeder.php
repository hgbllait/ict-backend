<?php
namespace Database\Seeders;


use App\Data\Models\Auth\User;
use App\Data\Models\Employee\Employee;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = \Faker\Factory::create();
        $first_name = 'Super Admin';
        $last_name = 'Admin';
        $full_name = $first_name . " " . $last_name;
        $username = 'superadmin';

        $email = $username . "@" . 'usep.edu.ph';
        $status = "active";

        $data = [];
        $user = [];

        $data[ "id" ] = 1;
        $data[ "pmaps_id" ] = 1;
        $data[ "full_name" ] = $full_name;
        $data[ "first_name" ] = $first_name;
        $data[ "last_name" ] = $last_name;
        $data[ "email" ] = $email;
        $data[ "address" ] = $faker->streetAddress () . " " . $faker->address ();
        $data[ "contact_number" ] = $faker->phoneNumber ();
        $data[ "status" ] = $status;
        $data[ "created_at" ] = NOW();
        $data[ "updated_at" ] = NOW();

        $user['employee_id'] = 1;
        $user['email'] = $email;
        $user['username'] = $username;
        $user['password'] = Hash::make('Test@123');
        $user['status'] = $status;
        $user['created_at'] = NOW();
        $user['updated_at'] = NOW();

        $employee_data = $data;
        $user_data = $user;

        Employee::create($employee_data);

        User::create( $user_data );

        $user = User::find(1);

        $role = Role::create(['name' => 'Super Admin']);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);
    }
}
