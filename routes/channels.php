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

// Tenant-aware channel authorizations
Broadcast::channel('tenant_{tenantId}.ProcessMaker.Models.User.{id}', function ($user, $tenantId, $id) {
    // Handle anonymous users - they should not have access to user-specific channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant_{tenantId}.ProcessMaker.Models.ProcessRequest.{id}', function ($user, $tenantId, $id) {
    if ($id === 'undefined' || $user === 'undefined') {
        return false;
    }

    // Handle anonymous users - they should not have access to process request channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    if ($user->is_administrator) {
        return true;
    }

    $request = ProcessRequest::find($id);

    if (!$request) {
        return false;
    }

    return $request->user_id === $user->id
        || !empty($request->participants()->where('users.id', $user->getKey())->first())
        || $request->process?->manager_id === $user->id;
});

Broadcast::channel('tenant_{tenantId}.ProcessMaker.Models.ProcessRequestToken.{id}', function ($user, $tenantId, $id) {
    // Handle anonymous users - they should not have access to process request token channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    if ($user->is_administrator) {
        return true;
    }

    $token = ProcessRequestToken::find($id);

    if (!$token) {
        return false;
    }

    return $user->getKey() === $token->user_id;
});

Broadcast::channel('tenant_{tenantId}.test.status', function ($user, $tenantId) {
    return true;
});

Broadcast::channel('tenant_{tenantId}.ProcessMaker.Models.Process.{processId}.Language.{language}', function ($user, $tenantId, $processId, $language) {
    return true;
});

// Private tenant-aware channel authorizations
Broadcast::channel('private-tenant_{tenantId}.ProcessMaker.Models.User.{id}', function ($user, $tenantId, $id) {
    // Handle anonymous users - they should not have access to private channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    return (int) $user->id === (int) $id;
});

Broadcast::channel('private-tenant_{tenantId}.ProcessMaker.Models.ProcessRequest.{id}', function ($user, $tenantId, $id) {
    if ($id === 'undefined' || $user === 'undefined') {
        return false;
    }

    // Handle anonymous users - they should not have access to private channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    if ($user->is_administrator) {
        return true;
    }

    $request = ProcessRequest::find($id);

    if (!$request) {
        return false;
    }

    return $request->user_id === $user->id
        || !empty($request->participants()->where('users.id', $user->getKey())->first())
        || $request->process?->manager_id === $user->id;
});

Broadcast::channel('private-tenant_{tenantId}.ProcessMaker.Models.ProcessRequestToken.{id}', function ($user, $tenantId, $id) {
    // Handle anonymous users - they should not have access to private channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    if ($user->is_administrator) {
        return true;
    }

    $token = ProcessRequestToken::find($id);

    if (!$token) {
        return false;
    }

    return $user->getKey() === $token->user_id;
});

Broadcast::channel('private-tenant_{tenantId}.test.status', function ($user, $tenantId) {
    return true;
});

Broadcast::channel('private-tenant_{tenantId}.ProcessMaker.Models.Process.{processId}.Language.{language}', function ($user, $tenantId, $processId, $language) {
    return true;
});

// Generic package channel authorizations - allow authenticated users to access package channels
Broadcast::channel('private-tenant_{tenantId}.ProcessMaker.Package.{packageName}.{model}.{id}', function ($user, $tenantId, $packageName, $model, $id) {
    // Handle anonymous users - they should not have access to private package channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    // Allow authenticated users to access package channels
    return true;
});

Broadcast::channel('tenant_{tenantId}.ProcessMaker.Package.{packageName}.{model}.{id}', function ($user, $tenantId, $packageName, $model, $id) {
    // Handle anonymous users - they should not have access to package channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    // Allow authenticated users to access package channels
    return true;
});

// Generic package channel authorizations without tenant prefix
Broadcast::channel('private-ProcessMaker.Package.{packageName}.{model}.{id}', function ($user, $packageName, $model, $id) {
    // Handle anonymous users - they should not have access to private package channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    // Allow authenticated users to access package channels
    return true;
});

Broadcast::channel('ProcessMaker.Package.{packageName}.{model}.{id}', function ($user, $packageName, $model, $id) {
    // Handle anonymous users - they should not have access to package channels
    if (isset($user->isAnonymous) && $user->isAnonymous) {
        return false;
    }

    // Allow authenticated users to access package channels
    return true;
});
