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
})->purpose('Display an inspiring quote');

Artisan::command('notifications:resend {username}', function ($username) {
    $user = ProcessMaker\Models\User::where('username', $username)->firstOrFail();
    $tokens = ProcessMaker\Models\ProcessRequestToken
        ::where('status', 'ACTIVE')
        ->where('user_id', $user->getKey())
        ->get();
    foreach ($tokens as $token) {
        dump($token->id);
        $notification = new ProcessMaker\Notifications\ActivityActivatedNotification($token);
        $user->notify($notification);
    }
})->purpose('Resend to user the notifications of his/her active tasks');

Artisan::command('check {path}', function ($path) {
    $dom = new DOMDocument;
    $dom->load($path);
    $query = new DOMXPath($dom);
    $nodes = $query->evaluate('//*[@bpmnElement]');
    foreach ($nodes as $node) {
        $id = $node->getAttribute('bpmnElement');
        $elem = $query->evaluate("//*[@id='$id']")->item(0);
        dump($elem);
    }
})->purpose('Display an inspiring quote');
