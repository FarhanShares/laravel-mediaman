<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaManMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('mediaman.tables.media'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('disk');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->json('data')->nullable();
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
        Schema::dropIfExists(config('mediaman.tables.media'));
    }
}
