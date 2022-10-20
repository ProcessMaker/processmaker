<?php

namespace ProcessMaker\Models;

use Laravel\Passport\Token;

/**
 * @OA\Schema(
 *   schema="UserToken",
 *   @OA\Property(property="id", type="string"),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="client_id", type="integer"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="scopes", type="object"),
 *   @OA\Property(property="revoked", type="boolean"),
 *   @OA\Property(property="client", type="object", ref="#/components/schemas/TokenClient"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 *   @OA\Property(property="expires_at", type="string", format="date-time"),
 * )
 */
class UserToken extends Token
{
}
