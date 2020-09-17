<?php

use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\AnonymousUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAnonymousUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system')->after('is_administrator')->default(false);
        });

        $user = User::updateOrCreate(
            ['username' => AnonymousUser::ANONYMOUS_USERNAME],
            [
                'firstname' => 'Anonymous',
                'lastname' => 'User',
                'email' => 'anonymous-pm4-user@processmaker.com',
                'status' => 'ACTIVE',
                'password' => Hash::make(bin2hex(random_bytes(16))),
            ]
        );

        $user->is_system = true;
        $user->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->forceDelete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
}
