<?php

namespace ProcessMaker\Models;

use Laravel\Passport\Client;

class TokenClient extends Client
{
    /**
     * @OA\Schema(
     *   schema="TokenClient",
     *   @OA\Property(property="id", type="integer"),
     *   @OA\Property(property="user_id", type="integer"),
     *   @OA\Property(property="name", type="string"),
     *   @OA\Property(property="provider", type="string"),
     *   @OA\Property(property="redirect", type="string"),
     *   @OA\Property(property="personal_access_client", type="boolean"),
     *   @OA\Property(property="password_client", type="boolean"),
     *   @OA\Property(property="revoked", type="boolean"),
     *   @OA\Property(property="created_at", type="string", format="date-time"),
     *   @OA\Property(property="updated_at", type="string", format="date-time"),
     * )
     */
}
