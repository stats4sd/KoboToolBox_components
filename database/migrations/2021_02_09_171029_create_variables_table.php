<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_map_id')->constrained('data_maps')->onDelete('cascade');
            $table->string('xlsform_varname');
            $table->string('db_varname');
            $table->tinyInteger('is_db');
            $table->string('type');
            $table->string('model');
            $table->tinyInteger('is_json');
            $table->string('linked_other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variables');
    }
}
