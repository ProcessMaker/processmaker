<?php
namespace ProcessMaker;

use Laravel\Passport\ClientRepository;

class GenerateAccessToken {

    private $token;
    private $oauth_access_token;

    public function __construct($user)
    {
        $token_result = $user->createToken('script-runner');
        $this->oauth_access_token = $token_result->token;
        $this->token = $token_result->accessToken;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function delete()
    {
        $this->oauth_access_token->delete();
    }
}