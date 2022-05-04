<?php


namespace ProcessMaker\Models;


use Laravel\Passport\Token;

class UserToken extends Token
{

    /**
     *
     * @OA\Schema(
     *   schema="UserToken",
     *   @OA\Property(property="id", type="string"),
     *   @OA\Property(property="name", type="string"),
     *   @OA\Property(property="revoked", type="boolean"),
     *   @OA\Property(property="created_at", type="string", format="date-time"),
     *   @OA\Property(property="updated_at", type="string", format="date-time"),
     *   @OA\Property(property="expires_at", type="string", format="date-time"),
     * )
     */

}