<?php
namespace Hyper\Domain\OAuth;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as Encoder;
/**
* @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\OAuth\DTOAuthClientUserAccessRepository")
* @ORM\Table(name="oauth_client_user_access")
*
*/
class OAuthClientUserAccess
{
    const USER_TYPE_TK = 1;
    const USER_TYPE_AK = 2;
    
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="username", type="string")
     */
    private $username;
    
    /**
     * @ORM\Column(name="user_type", type="string")
     */
    private $userType;
    
    /**
     * @ORM\Column(name="status", type="integer", options={"default" = 1})
     */
    private $status;
    
    /**
     * @ORM\ManyToOne(
     *     targetEntity = "Hyper\Domain\OAuth\OAuthClient",
     *     inversedBy = "clientUserAccess"
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
    
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setUserType($userType)
    {
        $this->userType = $userType;
        return $this;
    }
    
    public function getUserType()
    {
        return $this->userType;
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
    
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
}