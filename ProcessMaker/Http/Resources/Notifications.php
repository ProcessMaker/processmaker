<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use ProcessMaker\Models\User;

class Notifications extends ApiResource
{
    public function toArray($request)
    {
        $array = parent::toArray($request);
        $array['data'] = json_decode($this->data, true);

        $include = explode(',', $request->input('include', ''));
        $userId = Arr::get($array, 'data.user_id');
        if (in_array('user', $include) && $userId) {
            $fromUser = User::find($userId);
            $array['data']['user'] = new Users($fromUser);
        }

        return $array;
    }
}
