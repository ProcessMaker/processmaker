<?php
namespace ProcessMaker\OAuth2;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use ProcessMaker\Model\OAuth2\OAuthClient;
use ProcessMaker\Models\OauthClientsQuery;

/**
 * Our OAuth2 Client Repository implementation
 * @package ProcessMaker\OAuth2
 * @see ClientRepositoryInterface
 */
class ClientRepository implements ClientRepositoryInterface
{

    /**
     * Retrieves an OAuth2 client from our database
     * @param string $clientIdentifier
     * @param string $grantType
     * @param null $clientSecret
     * @param bool $mustValidateSecret
     * @return \Illuminate\Database\Eloquent\Model|\League\OAuth2\Server\Entities\ClientEntityInterface|null|static
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        $client = OAuthClient::where('id', $clientIdentifier)->first();
        if (!$client) {
            return null;
        }
        if ($mustValidateSecret) {
            if ($client->secret == $clientSecret) {
                return $client;
            }
        } else {
            return $client;
        }
        return null;
    }
}
