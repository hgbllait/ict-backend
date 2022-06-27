<?php

namespace Database\Seeders;


use DB;
use Illuminate\Database\Seeder;

class ApproverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $approvers = [
            [
                'employee_id'  => 2021,
                'approver_unique_id' => '92841029372193',
                'name' => 'Llait, Harryn Glyde',
                'email' => 'hgbllait@usep.edu.ph',
                'type' => 'End User',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'employee_id'  => 306,
                'approver_unique_id' => '56231762963412',
                'name' => 'Reyes, Ariel Roy',
                'email' => 'ariel.reyes@usep.edu.ph',
                'type' => 'SDMD Director',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'employee_id'  => 2068,
                'approver_unique_id' => '73412128902698',
                'name' => 'Adorable, Theresa',
                'email' => 'tvadorable@usep.edu.ph',
                'type' => 'SDMD Personnel',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'employee_id'  => 306,
                'approver_unique_id' => '72689128513890',
                'name' => 'Pepito, Geremiah',
                'email' => 'gery.pepito@usep.edu.ph',
                'type' => 'SDMD Personnel',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'employee_id'  => 939,
                'approver_unique_id' => '961239128513890',
                'name' => 'Gumapac, Oliver',
                'email' => 'olivergumapac@usep.edu.ph',
                'type' => 'SDMD Personnel',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'employee_id'  => 692,
                'approver_unique_id' => '57243128513890',
                'name' => 'Icban, Meill Frolidan',
                'email' => 'froyvhonndhann@gmail.com',
                'type' => 'SDMD Personnel',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
        ];

        foreach( $approvers as $value ){
            DB::table('approvers')->insert(
                $value
            );
        }


        $assign = [
            [
                'user_id'  => 2,
                'approver_id' => 1,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'user_id'  => 3,
                'approver_id' => 3,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'user_id'  => 4,
                'approver_id' => 4,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'user_id'  => 5,
                'approver_id' => 2,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'user_id'  => 6,
                'approver_id' => 5,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'user_id'  => 7,
                'approver_id' => 6,
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
        ];

        foreach( $assign as $value ){
            DB::table('user_assigns')->insert(
                $value
            );
        }

    }
}
