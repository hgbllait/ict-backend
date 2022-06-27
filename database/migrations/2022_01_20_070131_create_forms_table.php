<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date_effective');
            $table->string('revision_no');
            $table->string('issue_no');
            $table->string('form_type');
            $table->string('form_link')->default('N/A')->nullable();
            $table->unsignedInteger('type_id');
            $table->unsignedInteger('added_by');
            $table->unsignedInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('form_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('form_no');
            $table->string('type')->default('COD');
            $table->date('date_effective');
            $table->string('revision_no');
            $table->string('issue_no');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('forms');
        Schema::dropIfExists('form_types');
    }
}
