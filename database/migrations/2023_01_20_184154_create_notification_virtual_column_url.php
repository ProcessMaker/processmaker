<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationVirtualColumnUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('url')->nullable()->index();
        });

        DB::table('notifications')->whereNull('read_at')
            ->lazyById()->each(function ($notification) {
                $data = json_decode($notification->data, true);
                $url = $data['url'] ?? null;
                if ($url) {
                    DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update(['url' => $url]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
}
