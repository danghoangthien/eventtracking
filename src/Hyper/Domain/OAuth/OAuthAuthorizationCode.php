<?php
namespace Hyper\Domain\OAuth;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as Encoder;
/**
* @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\OAuth\DTOAuthAuthorizationCodeRepository")
* @ORM\Table(name="oauth_authorization_codes")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class OAuthAuthorizationCode
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="client_id", type="string")
     */
    private $clientId;
    
    /**
     * @ORM\Column(name="authorization_code", type="string")
     */
    private $authorizationCode;
    
    /**
     * @ORM\Column(name="expires_at", type="integer")
     */
    private $expiresAt;
    
    /**
     * @ORM\Column(name="scope", type="string")
     */
    private $scope;
    
    /**
     * @ORM\ManyToOne(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthClient",
     *     inversedBy = "authorizeCodes"
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
    
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
    
    public function getClientId()
    {
        return $this->clientId;
    }
    
    public function setAuthorizationCode($authorizationCode)
    {
        $this->authorizationCode = $authorizationCode;
        return $this;
    }
    
    public function getAuthorizationCode()
    {
        return $this->authorizationCode;
    }
    
    public function setExpiresAt($timestamp)
    {
        $this->expiresAt = $timestamp;
        return $this;
    }
    
    public function getExpiresA()
    {
        return $this->expiresAt;
    }
    
    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this->scope;
    }
    
    public function getScope()
    {
        return $this->scope;
    }
}