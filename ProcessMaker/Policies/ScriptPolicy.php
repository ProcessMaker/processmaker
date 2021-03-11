<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Script;
use ProcessMaker\PolicyExtension;

class ScriptPolicy
{
    use HandlesAuthorization;

    public function execute(User $user, Script $script)
    {
        $policyExtension = app(PolicyExtension::class);
        
        if ($policyExtension->has('execute', Script::class)) {
            return $policyExtension->authorize('execute', $user, $script);
        }
        
        return !$user->isAnonymous;
    }
}
