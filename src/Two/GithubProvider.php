<?php namespace Laravel\Socialite\Two;

class GithubProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user:email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://github.com/login/oauth/authorize', $state);
    }

    /**
     * Get the email for the given access token.
     *
     * @param  string  $token
     * @return null|string
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = 'https://api.github.com/user/emails?access_token='.$token;
        $response = $this->getHttpClient()->get($emailsUrl, $this->getRequestOptions());
        $emails = json_decode($response->getBody(), true);
        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
        return null;
    }

    /**
     * Get options for request
     *
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://github.com/login/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://api.github.com/user?access_token='.$token;
        $response = $this->getHttpClient()->get($userUrl, $this->getRequestOptions());
        $user = json_decode($response->getBody(), true);

        if (in_array('user:email', $this->scopes)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'], 'nickname' => $user['login'], 'name' => array_get($user, 'name'),
            'email' => array_get($user, 'email'), 'avatar' => $user['avatar_url'],
        ]);
    }
}
