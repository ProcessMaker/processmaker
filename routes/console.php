<?php

use Illuminate\Foundation\Inspiring;

/*
  |--------------------------------------------------------------------------
  | Console Routes
  |--------------------------------------------------------------------------
  |
  | This file is where you may define all of your Closure based console
  | commands. Each Closure is bound to a command instance allowing a
  | simple approach to interacting with each command's IO methods.
  |
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('notifications:resend {username}', function ($username) {
    $user = ProcessMaker\Models\User::where('username', $username)->firstOrFail();
    $tokens = ProcessMaker\Models\ProcessRequestToken
        ::where('status', 'ACTIVE')
        ->where('user_id', $user->getKey())
        ->get();
    foreach ($tokens as $token) {
        $notification = new ProcessMaker\Notifications\ActivityActivatedNotification($token);
        $user->notify($notification);
    }
})->describe('Resend to user the notifications of his/her active tasks');
