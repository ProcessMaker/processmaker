<?php

namespace ProcessMaker\Http\Resources\V1_1;

use Illuminate\Support\Collection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\User;

class CaseResource extends ApiResource
{
    /**
     * Default fields that will be included in the response.
     */
    protected static $defaultFields = [
        'case_number',
        'user_id',
        'case_title',
        'case_title_formatted',
        'case_status',
        'processes',
        'requests',
        'request_tokens',
        'tasks',
        'participants',
        'initiated_at',
        'completed_at',
    ];

    public function toArray($request): array
    {
        $data = [];

        $users = User::select('id', 'firstname', 'lastname', 'title', 'avatar')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->fullname,
                    'title' => $user->title,
                    'avatar' => $user->avatar,
                ];
            })
            ->keyBy('id');

        foreach (static::$defaultFields as $field) {
            if ($field === 'participants') {
                $participants = $this->$field->toArray();
                $data[$field] = $this->getParticipanData($participants, $users);

                continue;
            }

            $data[$field] = $this->$field;
        }

        return $data;
    }

    /**
     * Transform participants using the users collection.
     *
     * @param array $participants The participants array.
     * @param Collection $users The users collection.
     * @return array The transformed participants.
     */
    private function getParticipanData(array $participants, Collection $users): array
    {
        return array_map(fn($participant) => $users->get($participant), $participants);
    }
}
