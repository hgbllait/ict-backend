<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlowControlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approvers', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->unsignedInteger('employee_id');
         $table->string('approver_unique_id');
         $table->string('name');
         $table->string('email');
         $table->string('type');
         $table->string('description')->nullable();
         $table->string('status')->default("active");
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });
        Schema::create('flow_control_requests', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->unsignedInteger('form_id');
         $table->string('name');
         $table->string('approval_status');
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });
        Schema::create('flow_control_request_approvers', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->string('name');
         $table->unsignedInteger('approver_id');
         $table->unsignedInteger('flow_control_request_id');
         $table->string('approval_status');
         $table->string('override_reject');
         $table->string('override_accept');
         $table->string('required');
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });
        Schema::create('flow_control_request_logs', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->unsignedInteger('flow_control_request_id');
         $table->string('event_description')->nullable();
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });
        Schema::create('flow_control_request_rules', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->string('name');
         $table->unsignedInteger('flow_control_request_id');
         $table->unsignedInteger('rule_id');
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });
        Schema::create('rules', function (Blueprint $table) {
         $table->bigIncrements('id');
         $table->unsignedInteger('employee_id');
         $table->string('title');
         $table->string('description')->nullable();
         $table->string('status')->default("active");
         $table->unsignedInteger('added_by');
         $table->unsignedInteger('updated_by');
         $table->timestamps();
         $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approvers');
        Schema::dropIfExists('flow_control_requests');
        Schema::dropIfExists('flow_control_request_approvers');
        Schema::dropIfExists('flow_control_request_logs');
        Schema::dropIfExists('flow_control_request_rules');
        Schema::dropIfExists('metas');
        Schema::dropIfExists('rules');
    }
}
