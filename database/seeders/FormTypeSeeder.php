<?php

namespace Database\Seeders;


use DB;
use Illuminate\Database\Seeder;

class FormTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ict_form = [
            [
                'name'  => 'ICT Service Request',
                'form_no' => 'FM-USeP-ICT-01',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'ICT Service Job Order',
                'form_no' => 'FM-USeP-ICT-02',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'ICT Service Repair Evaluation Report',
                'form_no' => 'FM-USeP-ICT-03',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'ICT Equipment History Sheet',
                'form_no' => 'FM-USeP-ICT-04',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'Request for Information Systems User Account Credentials',
                'form_no' => 'FM-USeP-ICT-05',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'Request for Official Email Address',
                'form_no' => 'FM-USeP-ICT-06',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'IS Development and Enhancement Request',
                'form_no' => 'FM-USeP-ICT-07',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'IS Development and Enhancement Action',
                'form_no' => 'FM-USeP-ICT-08',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'ICT Service Request Registry',
                'form_no' => 'FM-USeP-ICT-09',
                'type' => 'ICT',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
        ];

        foreach( $ict_form as $value ){
            DB::table('form_types')->insert(
                $value
            );
        }

        $cod_form = [
            [
                'name'  => 'Document Change Request_New Document',
                'form_no' => 'FM-USeP-COD-02',
                'type' => 'COD',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'Document Change Request_New Nullification',
                'form_no' => 'FM-USeP-COD-02',
                'type' => 'COD',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
        ];

        foreach( $cod_form as $value ){
            DB::table('form_types')->insert(
                $value
            );
        }

        $rom_form = [
            [
                'name'  => 'Risk Register rev02',
                'form_no' => 'FM-USeP-ROM-01',
                'type' => 'ROM',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'Opportunity Register rev02',
                'form_no' => 'FM-USeP-ROM-02',
                'type' => 'ROM',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 1,
                'created_at' => now()
            ],
            [
                'name'  => 'Risk Control Action Plan rev02',
                'form_no' => 'FM-USeP-ROM-03',
                'type' => 'ROM',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 1,
                'updated_by' => 0,
                'created_at' => now()
            ],
            [
                'name'  => 'Opportunity Pursuit Plan rev02',
                'form_no' => 'FM-USeP-ROM-04',
                'type' => 'ROM',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 0,
                'updated_by' => 0,
                'created_at' => now()
            ],
            [
                'name'  => 'Monitoring and Assessment Report rev01',
                'form_no' => 'FM-USeP-ROM-05',
                'type' => 'ROM',
                'date_effective' => '2022-01-01',
                'revision_no' => '01',
                'issue_no' => '02',
                'added_by' => 0,
                'updated_by' => 0,
                'created_at' => now()
            ],
        ];

        foreach( $rom_form as $value ){
//            DB::table('form_types')->insert(
//                $value
//            );
        }
    }
}
