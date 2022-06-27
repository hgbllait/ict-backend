<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUtilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('target_id');
            $table->string('target_type');
            $table->string('name');
            $table->string('full_path');
            $table->unsignedInteger('added_by');
            $table->unsignedInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target_type');
            $table->unsignedInteger('target_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
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
        Schema::dropIfExists('files');
        Schema::dropIfExists('metas');
    }
}
