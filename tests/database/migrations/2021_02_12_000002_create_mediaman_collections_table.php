<?php

use App\Models\MediaCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaManCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('mediaman.tables.collections'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Default seed
        $collection = resolve(config('mediaman.models.collection'));
        $collection->name = 'Default';
        $collection->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('mediaman.tables.collections'));
    }
}
