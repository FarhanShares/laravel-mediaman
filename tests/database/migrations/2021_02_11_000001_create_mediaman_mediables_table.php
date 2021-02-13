<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediamanMediablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('mediaman.tables.mediables'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id')->index();
            $table->unsignedBigInteger('mediable_id')->index();
            $table->string('mediable_type');
            $table->string('channel');

            $table->foreign('media_id')
                ->references('id')
                ->on(config('mediaman.tables.files'))
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('mediaman.tables.mediables'));
    }
}
