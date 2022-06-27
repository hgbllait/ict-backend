<?php

namespace Database\Seeders;


use DB;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $form = [
            [
                'name'  => 'DOCUMENT CHANGE REQUEST',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'type_id' => 10,
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'name'  => 'REQUEST FOR OFFICIAL EMAIL ADDRESS',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'type_id' => 6,
                'added_by' => 1,
                'updated_by' => 1
            ],
        ];

        foreach( $form as $value ){
            DB::table('forms')->insert(
                $value
            );
        }
    }
}
