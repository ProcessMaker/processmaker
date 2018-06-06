<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\User;

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
        DB::table('oauth_clients')->insert([
            'id' => 'x-pm-local-client',
            'secret' => '179ad45c6ce2cb97cf1029e212046e81',
            'name' => 'ProcessMaker Web Client',
            'description' => 'ProcessMaker Web App',
            'website' => 'www.processmaker.com',
            'redirect_uri' => config('app.url') . '/oauth2/grant',
            'user_id' => User::where('username', 'admin')->first()->id
        ]);
   }
}
