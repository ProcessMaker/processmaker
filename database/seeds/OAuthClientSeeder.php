<?php

use Illuminate\Database\Seeder;

/**
 * Generates the required OAuth2 Client for ProcessMaker Web to function
 */
class OAuthClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('OAUTH_CLIENTS')->insert([
            'CLIENT_ID' => 'x-pm-local-client',
            'CLIENT_SECRET' => '179ad45c6ce2cb97cf1029e212046e81',
            'CLIENT_NAME' => 'PM Web Designer',
            'CLIENT_DESCRIPTION' => 'ProcessMaker Web App',
            'CLIENT_WEBSITE' => 'www.processmaker.com',
            'REDIRECT_URI' => config('app.url') . '/oauth2/grant',
            'USR_UID' => '00000000000000000000000000000001'
        ]);
        DB::table('OAUTH_ACCESS_TOKENS')->insert([
            'ACCESS_TOKEN' => '39704d17049f5aef45e884e7b769989269502f83',
            'CLIENT_ID' => 'x-pm-local-client',
            'USER_ID' => '00000000000000000000000000000001',
            'EXPIRES' => '2017-06-15 17:55:19',
            'SCOPE' => 'view_processes edit_processes *'
        ]);
    }
}
