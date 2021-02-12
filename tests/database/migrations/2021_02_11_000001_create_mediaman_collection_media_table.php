<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaManCollectionMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('mediaman.tables.collection_media'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('collection_id');
            $table->integer('media_id');

            $table->foreign('collection_id')
                ->references('id')
                ->on(config('mediaman.tables.collections'))
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('mediaman.tables.collection_media'));
    }
}
