<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        $employee_data = [];
        $user_data = [];
        $i = 1;

        $users = [
            [
                'first_name'  => 'Harryn Glyde',
                'last_name' => 'Llait',
                'email' => 'hgbllait@usep.edu.ph',
                'contact_number' => '09665447517'
            ],
            [
                'first_name'  => 'Theresa',
                'last_name' => 'Adorable',
                'email' => 'tvadorable@usep.edu.ph',
                'contact_number' => "09427223340"
            ],
            [
                'first_name'  => 'Geremiah',
                'last_name' => 'Pepito',
                'email' => 'gery.pepito@usep.edu.ph',
                'contact_number' => "09283052652"
            ],
            [
                'first_name'  => 'Ariel Roy',
                'last_name' => 'Reyes',
                'email' => 'ariel.reyes@usep.edu.ph',
                'contact_number' => "09198763561"
            ],
            [
                'first_name'  => 'Oliver',
                'last_name' => 'Gumapac',
                'email' => 'olivergumapac@usep.edu.ph',
                'contact_number' => "09275534969"
            ],
            [
                'first_name'  => 'Meill Frolidan',
                'last_name' => 'Icban',
                'email' => 'froyvhonndhann@gmail.com',
                'contact_number' => "09984914256"
            ],
        ];

        foreach( $users as $key => $value ){
            $i = $i + 1;
            $full_name = $value["first_name"] . " " . $value["last_name"];
            $username =
                strtolower(
                    substr( $value["first_name"], 0, 1) . str_replace(" ", "_", $value["last_name"] )
                );
            $date = Carbon::now()->format('Y-m-d H:i:s');
            $status = "active";

            $data = [];
            $user = [];

            $data[ "id" ] = $i;
            $data[ "full_name" ] = $full_name;
            $data[ "first_name" ] = $value["first_name"];
            $data[ "last_name" ] = $value["last_name"];
            $data[ "email" ] = $value["email"];
            $data[ "address" ] = $faker->streetAddress () . " " . $faker->address ();
            $data[ "contact_number" ] = $value["contact_number"];
            $data[ "status" ] = $status;
            $data[ "created_at" ] = $date;
            $data[ "updated_at" ] = $date;
            $data[ "added_by" ] = 1;
            $data[ "updated_by" ] = 0;

            $user['employee_id'] = $i;
            $user['email'] = $value["email"];
            $user['username'] = $username;
            $user['password'] = bcrypt('Test@123');
            $user['status'] = $status;
            $user['created_at'] = $date;
            $user['updated_at'] = $date;

            $employee_data[] = $data;
            $user_data[] = $user;
        }

        \DB::table('employees')->insert($employee_data);
        \DB::table('users')->insert($user_data);

    }
}
