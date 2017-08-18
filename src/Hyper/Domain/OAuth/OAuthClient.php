<?php
namespace Hyper\Domain\OAuth;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as Encoder;
/**
* @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\OAuth\DTOAuthClientRepository")
* @ORM\Table(name="oauth_clients")
*
*/
class OAuthClient
{
    const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';
    const GRANT_TYPE_CLIENT_CREDENTIAL = 'client_credentials';
    
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="name", type="string")
     */
    private $name;
    
    /**
     * @ORM\Column(name="description", type="string", nullable=true)
     */
    private $description;
    
    /**
     * @ORM\Column(name="oauth_client_id", type="string")
     */
    private $oauthClientId;
    
    /**
     * @ORM\Column(name="client_secret", type="string")
     */
    private $clientSecret;
    
    /**
     * @ORM\Column(name="grant_type", type="string")
     */
    private $grantType;
    
    /**
     * @ORM\Column(name="redirect_uri", type="string", nullable=true)
     */
    private $redirectUri;
    
    /**
     * @ORM\OneToMany(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthAccessToken",
     *     mappedBy = "client"
     * )
     */
    private $accessTokens;
    
    /**
     * @ORM\OneToMany(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthAuthorizationCode",
     *     mappedBy = "client"
     * )
     */
    private $authorizeCodes;
    
    /**
     * @ORM\OneToMany(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthClientUserAccess",
     *     mappedBy = "client"
     * )
     */
    private $clientUserAccess;
    
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
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
    public function setOauthClientId($oauthClientId)
    {
        $this->oauthClientId = $oauthClientId;
        return $this;
    }
    
    public function getOauthClientId()
    {
        return $this->oauthClientId;
    }
    
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }
    
    public function getClientSecret()
    {
        return $this->clientSecret;
    }
    
    public function setGrantType($grantType)
    {
        $this->grantType = $grantType;
        return $this;
    }
    
    public function getGrantType()
    {
        return $this->grantType;
    }
    
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
        return $this;
    }
    
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }
    
    public function getAccessTokens()
    {
        return $this->accessTokens;
    }
    
}