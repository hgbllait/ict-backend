<?php

namespace Database\Seeders;


use DB;
use Illuminate\Database\Seeder;

class MetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = [
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'type_request',
                'meta_value' => 1,
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'type_document',
                'meta_value' => 2,
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'justification',
                'meta_value' => 1,
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'document_title',
                'meta_value' => 'ICT Development, Management and Support',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'document_number',
                'meta_value' => 'PM-USeP-ICT',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'revision_status_from',
                'meta_value' => '2022-01-01',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'revision_status_to',
                'meta_value' => '2022-01-02',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'issue_status_from',
                'meta_value' => '2022-01-01',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'issue_status_to',
                'meta_value' => '2022-01-02',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'document_change_from',
                'meta_value' => '',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'document_change_to',
                'meta_value' => 'Information and Communications Technology (ICT) Development, Management and Support is defined as the process for receiving, evaluating, validation, provision, and service acceptance of ICT-related requests, thereby establishing a documented audit trail for the movement of the request.


This covers requests from university entities relative to ICT Hardware and Software Services, Provision of Information Systems’ (IS) User Credentials and Update, Provision of Official University Email Address, and Information Systems Development and Enhancement to ensure that all validated request for ICT systems development, management and support are served
',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'prepared_by',
                'meta_value' => 'Ariel Roy L. Reyes',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'division',
                'meta_value' => 'Systems and Data Management Division',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'contact_number',
                'meta_value' => '(082) 227-8192 Local 271',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 1,
                'meta_key' => 'date_accomplished',
                'meta_value' => '2022-01-01',
                'added_by' => 1,
                'updated_by' => 1
            ],
        ];

        foreach( $forms as $value ){
            DB::table('metas')->insert(
                $value
            );
        }

        $forms = [
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'last_name',
                'meta_value' => 'LLait',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'first_name',
                'meta_value' => 'Harryn Glyde',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'middle_initial',
                'meta_value' => 'B.',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'date_prepared',
                'meta_value' => '2022-01-01',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'position',
                'meta_value' => 'Programmer',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'college',
                'meta_value' => 'System and Data Management Division',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'remarks_1',
                'meta_value' => '',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'remarks_2',
                'meta_value' => '',
                'added_by' => 1,
                'updated_by' => 1
            ],
            [
                'target_type'  => 'forms',
                'target_id' => 2,
                'meta_key' => 'remarks_3',
                'meta_value' => '',
                'added_by' => 1,
                'updated_by' => 0
            ],
        ];

        foreach( $forms as $value ){
            DB::table('metas')->insert(
                $value
            );
        }
    }
}
