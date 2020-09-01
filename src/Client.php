<?php

namespace Onetoweb\Instagram;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as GuzzleClient;
use Onetoweb\Instagram\Token;
use Onetoweb\Instagram\Exception\TokenException;

/**
 * Instagram Client
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 */
class Client
{
    /**
     * @var string
     */
    private $appId;
    
    /**
     * @var string
     */
    private $appSecret;
    
    /**
     * @var string
     */
    private $redirectUri;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * Constructor.
     * 
     * @param int $appId
     * @param string $appSecret
     * @param string $redirectUri
     */
    public function __construct(int $appId, string $appSecret, string $redirectUri)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }
    
    /**
     * Get Token.
     * 
     * @return Token
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }
    
    /**
     * Set Token.
     * 
     * @param Token
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * Get authorization link.
     * 
     * @param string $state
     * 
     * @return string
     */
    public function getAuthorizationLink(string $state): string
    {
        return 'https://api.instagram.com/oauth/authorize?' . http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
            'state' => $state,
        ]);
    }
    
    /**
     * Request access token.
     * 
     * @param string $code
     */
    public function requestAccessToken(string $code): void
    {
        $client = new GuzzleClient();
        $response  = $client->request('POST', 'https://api.instagram.com/oauth/access_token', [
            RequestOptions::FORM_PARAMS => [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ],
        ]);
        
        $shortLived = json_decode($response->getBody()->getContents());
        
        // yeet short-lived and get long-lived access token
        
        $response  = $client->request('GET', 'https://graph.instagram.com/access_token?'.http_build_query([
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->appSecret,
            'access_token' => $shortLived->access_token,
        ]));
        
        $data = json_decode($response->getBody()->getContents());
        
        $expires = new \DateTime();
        $expires->setTimestamp(time() - $data->expires_in);
        
        $this->token = new Token($data->access_token, $expires);
    }
    
    /**
     * Refresh access token.
     * 
     * @param bool $force = false
     * 
     * @throws TokenException if no token has been set
     */
    public function refreshAccessToken(bool $force = false): void
    {
        if (!$this->token) {
            throw new TokenException('no token has been set');
        }
        
        if ($force or $this->token->hasExpired()) {
            
            $client = new GuzzleClient();
            $response  = $client->request('GET', 'https://graph.instagram.com/refresh_access_token?'.http_build_query([
                'grant_type' => 'ig_exchange_token',
                'access_token' => $this->token->getToken(),
            ]));
            
            $data = json_decode($response->getBody()->getContents());
            
            $expires = new \DateTime();
            $expires->setTimestamp(time() - $data->expires_in);
            
            $this->token = new Token($data->access_token, $expires);
        }
    }
    
    /**
     * @param array $fields = []
     * 
     * @return stdClass
     */
    public function getUserData(array $fields = []): \stdClass
    {
        $this->refreshAccessToken();
        
        $client = new GuzzleClient();
        $response  = $client->request('GET', "https://graph.instagram.com/me?".http_build_query([
            'fields' => implode(',', $fields),
            'access_token' => $this->token->getToken(),
        ]));
        
        return json_decode($response->getBody()->getContents());
    }
    
    /**
     * @param array $fields = []
     * 
     * @return stdClass
     */
    public function getUserMedia(array $fields = []): \stdClass
    {
        $this->refreshAccessToken();
        
        $client = new GuzzleClient();
        $response  = $client->request('GET', "https://graph.instagram.com/me/media?".http_build_query([
            'fields' => implode(',', $fields),
            'access_token' => $this->token->getToken(),
        ]));
        
        return json_decode($response->getBody()->getContents());
    }
}