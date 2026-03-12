<?php

namespace App\Socialite;

use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use GuzzleHttp\Client;

class TwitterOAuth2Provider extends AbstractProvider implements ProviderInterface
{
    protected $codeVerifier;
    protected $scopeSeparator = ' '; // Twitter expects spaces, not commas

    /**
     * Determine if the provider uses PKCE.
     *
     * @return bool
     */
    protected function usesPKCE()
    {
        return true;
    }

    /**
     * Override HTTP client for traditional OAuth 2.0
     */
    protected function getHttpClient()
    {
        if ($this->httpClient) {
            return $this->httpClient;
        }

        return $this->httpClient = new Client([
            'timeout' => 30,
        ]);
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://twitter.com/i/oauth2/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://api.twitter.com/2/oauth2/token';
    }

    protected function getUserByToken($token)
    {
        \Log::info('Twitter OAuth: Requesting user data', [
            'api_url' => 'https://api.twitter.com/2/users/me',
            'has_token' => !empty($token),
            'token_length' => strlen($token ?? '')
        ]);
        
        try {
            $response = $this->getHttpClient()->get('https://api.twitter.com/2/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
                'query' => [
                    'user.fields' => 'id,name,username,profile_image_url',
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);
            
            \Log::info('Twitter OAuth: Received user data', [
                'status_code' => $response->getStatusCode(),
                'response_headers' => $response->getHeaders(),
                'raw_response_body' => $responseBody,
                'parsed_response' => $responseData,
                'has_user_data' => !empty($responseData['data']),
                'has_errors' => !empty($responseData['errors'])
            ]);

            if (!empty($responseData['errors'])) {
                \Log::error('Twitter OAuth: API returned error in user data', [
                    'errors' => $responseData['errors'],
                    'error_details' => array_map(function($error) {
                        return [
                            'code' => $error['code'] ?? null,
                            'message' => $error['message'] ?? null,
                            'parameter' => $error['parameter'] ?? null
                        ];
                    }, $responseData['errors']),
                    'full_response' => $responseData
                ]);
            }

            return $responseData;
            
        } catch (\Exception $e) {
            \Log::error('Twitter OAuth: Exception during user data request', [
                'exception_message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'api_url' => 'https://api.twitter.com/2/users/me',
                'has_token' => !empty($token)
            ]);
            
            throw $e;
        }
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['data']['id'],
            'nickname' => $user['data']['username'],
            'name' => $user['data']['name'],
            'avatar' => $user['data']['profile_image_url'] ?? null,
            'email' => null,
        ]);
    }

    protected function getTokenFields($code)
{
    $fields = [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $this->redirectUrl,
    ];

    if ($this->usesPKCE()) {
        $fields['code_verifier'] = $this->request->session()->get('code_verifier');
    }

    return $fields;
}

    public function getAccessTokenResponse($code)
{
    $fields = $this->getTokenFields($code);
    
    $client = new Client([
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Laravel Socialite App/1.0',
        ]
    ]);
    
    $response = $client->post($this->getTokenUrl(), [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'auth' => [$this->clientId, $this->clientSecret],
        'form_params' => $fields,
    ]);

    return json_decode($response->getBody()->getContents(), true);
}

public function refreshAccessToken($refreshToken)
{
    $client = new Client([
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Laravel Socialite App/1.0',
        ]
    ]);
    
    $response = $client->post($this->getTokenUrl(), [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'auth' => [$this->clientId, $this->clientSecret],
        'form_params' => [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
        ],
    ]);

    return json_decode($response->getBody()->getContents(), true);
}
}
