<?php

namespace Onetoweb\Instagram;

/**
 * Instagram long lived access token.
 */
class Token
{
    /**
     * @var string
     */
    private $token;
    
    /**
     * @var DateTime
     */
    private $expires;
    
    /**
     * @param string $token
     * @param DateTime $expires
     */
    public function __construct(string $token, \DateTime $expires)
    {
        $this->token = $token;
        $this->expires = $expires;
    }
    
    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
    
    /**
     * @return DateTime
     */
    public function getExpires(): \DateTime
    {
        return $this->expires;
    }
    
    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return ($this->expires > new \DateTime());
    }
}