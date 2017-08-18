<?php
namespace Hyper\Domain\OAuth;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as Encoder;
/**
* @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\OAuth\DTOAuthAccessTokenRepository")
* @ORM\Table(name="oauth_access_tokens")
*
*/
class OAuthAccessToken
{
    const TOKEN_EXPIRY = 3600;
    const COST = 4; // must be from 4 to 31 (the higher cost the longer encoding)
    const ANONYMOUS_USER = 0; // hard code
    
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="token", type="string")
     */
    private $token;
    
    /**
     * @ORM\Column(name="refresh_token", type="string")
     */
    private $refreshToken;
    
    /**
     * @ORM\Column(name="expires_at", type="integer")
     */
    private $expiresAt;
    
    /**
     * @ORM\Column(name="scope", type="string")
     */
    private $scope;
    
    /**
     * @ORM\Column(name="username", type="string")
     */
    private $username;
    
    /**
     * @ORM\Column(name="status", type="integer", options={"default" = 1})
     */
    private $status;
    
    /**
     * @ORM\Column(name="client_id", type="string")
     */
    private $clientId;
    
    /**
     * @ORM\ManyToOne(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthClient",
     *     inversedBy = "accessTokens"
     * )
     *
     * @ORM\JoinColumn(
     *     name = "client_id",
     *     referencedColumnName = "id"
     * )
     */
    private $client;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
    
    public function getToken()
    {
        return $this->token;
    }
    
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }
    
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
    
    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
        return $this;
    }
    
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
    
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }
    
    public function getScope()
    {
        return $this->scope;
    }
    
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }
    
    public function getClientId()
    {
        return $this->clientId;
    }
    
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
    public function initData()
    {
        $expriesAt = time()+self::TOKEN_EXPIRY;
        $this->setExpiresAt($expriesAt);
        $this->setToken($this->hashToken());
        $this->setRefreshToken($this->hashToken());
    }
    
    public function hashToken()
    {
        $tokenRaw = $this->tokenRaw();
        $token = $this->getEncoder()->encodePassword($tokenRaw, md5('token-'.rand().microtime()));
        // remove all non-alphanumeric characters
        return preg_replace('/[^A-Za-z0-9 ]/', '', $token);
    }
    
    public function hashRefreshToken()
    {
        $tokenRaw = $this->tokenRaw();
        $token = $this->getEncoder()->encodePassword($tokenRaw, md5('refresh-token-'.rand().microtime()));
        // remove all non-alphanumeric characters
        return preg_replace('/[^A-Za-z0-9 ]/', '', $token);
    }
    
    public function tokenRaw()
    {
        if (null == $this->username) {
            $this->username = 'guest';
        }
        return 'token-'.$this->username.'-'.rand().microtime();
    }
    
    public function getEncoder()
    {
        return new Encoder(self::COST);
    }
    
}