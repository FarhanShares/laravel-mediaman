<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaManTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Collections table
        Schema::create(config('mediaman.tables.collections'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        // Default seed for collections
        $collection = resolve(config('mediaman.models.collection'));
        $collection->name = 'Default';
        $collection->save();


        // Media table
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


        // Collection & Media pivot table
        Schema::create(config('mediaman.tables.collection_media'), function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id')
                ->constraint(config('mediaman.tables.collections'))
                ->cascadeOnDelete();

            $table->unsignedBigInteger('media_id')
                ->constraint(config('mediaman.tables.media'))
                ->cascadeOnDelete();;

            $table->primary(['collection_id', 'media_id']);
        });


        // Mediable table
        Schema::create(config('mediaman.tables.mediables'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('media_id')->index();
            $table->unsignedBigInteger('mediable_id')->index();
            $table->string('mediable_type');
            $table->string('channel');

            $table->foreign('media_id')
                ->references('id')
                ->on(config('mediaman.tables.media'))
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
        Schema::dropIfExists(config('mediaman.tables.collection_media'));
        Schema::dropIfExists(config('mediaman.tables.mediables'));
        Schema::dropIfExists(config('mediaman.tables.collections'));
        Schema::dropIfExists(config('mediaman.tables.media'));
    }
}
