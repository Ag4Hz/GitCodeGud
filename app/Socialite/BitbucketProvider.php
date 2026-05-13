<?php

namespace App\Socialite;

use Exception;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Bitbucket\Provider as BaseBitbucketProvider;

class BitbucketProvider extends BaseBitbucketProvider
{
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.bitbucket.org/2.0/user', [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token],
        ]);

        $user = json_decode($response->getBody(), true);

        if (in_array('email', $this->scopes, true)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    protected function getEmailByToken($token)
    {
        try {
            $response = $this->getHttpClient()->get('https://api.bitbucket.org/2.0/user/emails', [
                RequestOptions::HEADERS => ['Authorization' => 'Bearer ' . $token],
            ]);
        } catch (Exception $e) {
            return;
        }

        $emails = json_decode($response->getBody(), true);

        foreach ($emails['values'] as $email) {
            if ($email['type'] === 'email' && $email['is_primary'] && $email['is_confirmed']) {
                return $email['email'];
            }
        }
    }
}
