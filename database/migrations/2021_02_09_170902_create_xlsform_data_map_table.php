<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXlsformDataMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xlsform_data_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xlsform_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_map_id')->constrained('data_maps')->onDelete('cascade');
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
        Schema::dropIfExists('xlsform_data_map');
    }
}
