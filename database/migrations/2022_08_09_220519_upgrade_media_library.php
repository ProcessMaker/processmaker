<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add conversions_disk field to the media table ( varchar 255 nullable )
        Schema::table('media', function (Blueprint $table) {
            $table->string('conversions_disk', 255)->nullable()->after('disk');
        });

        // Add a uuid field to the media table ( char 36 nullable)
        Schema::table('media', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->after('id');
        });

        // Populate the uuid field with a new uuid, chunked by 1000 records
        DB::table('media')->orderBy('id')->chunkById(1000, function ($media) {
            foreach ($media as $item) {
                DB::table('media')->where('id', $item->id)->update(['uuid' => Str::orderedUuid()]);
            }
        });

        Schema::table('media', function (Blueprint $table) {
            $table->json('generated_conversions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // remove conversions_disk field from the media table
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('conversions_disk');
        });

        // remove uuid field from the media table
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('generated_conversions');
        });
    }
};
