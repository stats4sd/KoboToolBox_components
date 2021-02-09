<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamXlsformTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_xlsform', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('xlsform_id')->constrained()->onDelete('cascade');
            $table->string('kobo_id')->nullable();
            $table->string('kobo_version_id')->nullable();
            $table->tinyInteger('is_active')->nullable();
            $table->string('enketo_url')->nullable();
            $table->string('link_page')->nullable();
            $table->tinyInteger('available')->nullable();
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
        Schema::dropIfExists('team_xlsform');
    }
}
