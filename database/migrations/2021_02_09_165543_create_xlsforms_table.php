<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateXlsformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xlsforms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('xlsfile');
            $table->text('description')->nullable();
            $table->text('media')->nullable();
            $table->json('csv_lookups')->nullable();
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
        Schema::dropIfExists('xlsforms');
    }
}
