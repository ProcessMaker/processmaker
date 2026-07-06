<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Request;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;

class UserTokenResource extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Handle PersonalAccessTokenResult (from token creation)
        if ($this->resource instanceof PersonalAccessTokenResult) {
            $token = $this->resource->getToken();

            return [
                'accessToken' => $this->resource->accessToken,
                'token' => $this->formatTokenModel($token),
            ];
        }

        // Handle Token model (from token retrieval)
        if ($this->resource instanceof Token) {
            return $this->formatTokenModel($this->resource);
        }

        // Fallback to parent implementation
        return parent::toArray($request);
    }

    /**
     * Format the token model into the expected array structure.
     *
     * @param  Token  $token
     * @return array
     */
    protected function formatTokenModel(Token $token): array
    {
        // Ensure scopes is always an array
        $scopes = $token->scopes;
        if (is_string($scopes)) {
            $scopes = json_decode($scopes, true) ?? [];
        }
        if (!is_array($scopes)) {
            $scopes = [];
        }

        return [
            'id' => $token->id,
            'user_id' => $token->user_id,
            'client_id' => $token->client_id,
            'name' => $token->name,
            'scopes' => $scopes,
            'revoked' => (bool) $token->revoked,
            'created_at' => $token->created_at ? $token->created_at->toIso8601String() : null,
            'updated_at' => $token->updated_at ? $token->updated_at->toIso8601String() : null,
            'expires_at' => $token->expires_at ? $token->expires_at->toIso8601String() : null,
        ];
    }
}
