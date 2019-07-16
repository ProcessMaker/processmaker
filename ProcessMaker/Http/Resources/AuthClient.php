<?php
namespace ProcessMaker\Http\Resources;

class AuthClient extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $types = [];
        if (!empty($this->redirect)) {
            $types[] = 'authorization_code_grant';
        }
        if ($this->personal_access_client) {
            $types[] = 'personal_access_client';
        }
        if ($this->password_client) {
            $types[] = 'password_client';
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'secret' => $this->secret,
            'redirect' => $this->redirect,
            'revoked' => $this->revoked,
            'types' => $types,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
