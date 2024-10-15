<?php

namespace ProcessMaker\Http\Resources\V1_1;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use ProcessMaker\Http\Resources\ApiResource;

class CaseResource extends ApiResource
{
    /**
     * The users collection.
     */
    private static Collection $users;

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

        foreach (static::$defaultFields as $field) {
            if ($field === 'participants') {
                $participants = $this->$field->toArray();
                $data[$field] = $this->getParticipantData($participants);

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
     * @return array The transformed participants.
     */
    private function getParticipantData(array $participants): array
    {
        return array_map(fn($participant) => self::$users->get($participant), $participants);
    }

    /**
     * New resource collection method that accepts a users collection.
     *
     * @param mixed $resource The resource.
     * @param Collection $users The users collection.
     * @return AnonymousResourceCollection The anonymous resource collection.
     */
    public static function customCollection($resource, $users): AnonymousResourceCollection
    {
        self::$users = $users;

        return parent::collection($resource);
    }
}
