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
use ProcessMaker\Models\ProcessRequestToken;

Broadcast::channel('ProcessMaker.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ProcessMaker.Models.ProcessRequest.{id}', function ($user, $id) {
    if ($id === 'undefined' || $user === 'undefined') {
        return;
    }

    if ($user->is_administrator) {
        return true;
    }

    $request = ProcessRequest::find($id);

    return $request->user_id === $user->id
        || !empty($request->participants()->where('users.id', $user->getKey())->first())
        || $request->process?->manager_id === $user->id;
});

Broadcast::channel('ProcessMaker.Models.ProcessRequestToken.{id}', function ($user, $id) {
    if ($user->is_administrator) {
        return true;
    }

    $token = ProcessRequestToken::find($id);

    return $user->getKey() === $token->user_id;
});

Broadcast::channel('test.status', function ($user) {
    return true;
});

Broadcast::channel('ProcessMaker.Models.Process.{processId}.Language.{language}', function ($user, $processId, $language) {
    return true;
});
