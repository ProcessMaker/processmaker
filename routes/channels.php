<?php
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use ProcessMaker\Models\ProcessRequest;

Broadcast::channel('ProcessMaker.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('ProcessMaker.Models.ProcessRequest.{id}', function ($user, $id) {
    $request = ProcessRequest::find($id);
    return !empty($request->participants()->where('users.id', $user->getKey())->first());
});
