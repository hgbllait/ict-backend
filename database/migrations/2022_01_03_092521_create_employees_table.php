<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->text('address')->nullable();
            $table->string('email');
            $table->string('position')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('image')->nullable();
            $table->string('status')->default('active');
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
        Schema::dropIfExists('employees');
    }
}
