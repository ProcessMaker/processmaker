<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessRequests extends ApiResource
{
    public function toArray($request)
    {
        $array = parent::toArray($request);
        $include = explode(',', $request->input('include', ''));

        if (in_array('user', $include)) {
            $array['user'] = new Users($this->user);
        }
        if (in_array('participantTokens', $include)) {
            $array['participant_tokens'] = $this->participantTokens()->get();
        }
        return $array;
    }
}
