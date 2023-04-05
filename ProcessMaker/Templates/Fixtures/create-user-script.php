<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;

/*
if(isset ($config["demo"]) && $config["demo"] == true){
    return [
        "newUser" => [
            "id" => 10
        ],
        "groupAdded" => true,
        "userErrors" => "no",
        "passwordConfirm" => null,
        "password" => null
    ];
}
*/
$groupId = $config['groupId']; // update value in process config

$pmheaders = [
    'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
    'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');

$email = isset($data['email']) ? $data['email'] : $data['contact']['email'];
$firstName = isset($data['firstName']) ? $data['firstName'] : $data['contact']['firstName'];
$lastName = isset($data['lastName']) ? $data['lastName'] : $data['contact']['lastName'];
$phone = isset($data['phone']) ? $data['phone'] : $data['contact']['phone'];
$mobile = isset($data['mobile']) ? $data['mobile'] : $data['contact']['mobile'];
$username = $data['username'];
$password = $data['password'];

// Create the new user from a form
$client = new GuzzleHttp\Client(['verify' => false]);

if (isset($data['pointOfContact'])) {
    if (($data['contact']['email'] != $data['pointOfContact']['email'])) {
        return [
            'userErrors' => 'yes',
            'errorMessage' => 'Incorrect email. Please check that email entered matches the email address recieving notifications.',
            'passwordConfirm' => null,
            'password' => null,
        ];
    }
}

$method = 'POST';
$url = $apiHost . '/users';

try {
    $res = $client->request($method, $apiHost . '/users', [
        'headers' => $pmheaders,
        'json' => [
            //You require the following fields for the endpoint to work
            'email' => $email,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'username' => $username,
            'is_administrator' => true, // temporary fix for permission issues
            'password' => $password,
            'phone' => $phone,
            'cell' => $mobile,
            'status' => 'ACTIVE',
        ],
    ]);

    if ($res->getStatusCode() == 201) { // user created
        $newUser = json_decode($res->getBody(), true);
        // add user to group
        $res = $client->request($method, $apiHost . '/group_members', [
            'headers' => $pmheaders,
            'json' => [
                'group_id' => $groupId,
                'member_type' => "ProcessMaker\Models\User",
                'member_id' => $newUser['id'],
            ],
        ]);
    }
    if ($res->getStatusCode() == 201) {
        $groupAdded = true;
    }

    return [
        'newUser' => $newUser,
        'groupAdded' => $groupAdded,
        'userErrors' => 'no',
        'passwordConfirm' => null,
        'password' => null,
    ];
} catch(ClientException $e) {
    $error = Psr7\str($e->getResponse());
    $error = substr($error, strpos($error, 'message') - 2);
    $error = json_decode($error, true);

    return [
        'userErrors' => 'yes',
        'errorMessage' => $error,
        'passwordConfirm' => null,
        'password' => null,
    ];
}
