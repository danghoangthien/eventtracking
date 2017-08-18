<?php

namespace Hyper\Domain\Client;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * ClientAppTitle
 *
 * @ORM\Table(name="client_app_title")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Client\DTClientAppTitleRepository")
 * @ExclusionPolicy("all")
 */
class ClientAppTitle
{
    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="ClientAppTitle")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Application\ApplicationTitle", inversedBy="ClientAppTitle")
     * @ORM\JoinColumn(name="app_title_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $appTitle;

    /**
     * Set client
     *
     * @param \Hyper\Domain\Client\Client $client
     * @return ClientAppTitle
     */
    public function setClient(\Hyper\Domain\Client\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \Hyper\Domain\Client\Client 
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set appTitle
     *
     * @param \Hyper\Domain\Application\ApplicationTitle $appTitle
     * @return ClientAppTitle
     */
    public function setAppTitle(\Hyper\Domain\Application\ApplicationTitle $appTitle)
    {
        $this->appTitle = $appTitle;

        return $this;
    }

    /**
     * Get appTitle
     *
     * @return \Hyper\Domain\Application\ApplicationTitle 
     */
    public function getAppTitle()
    {
        return $this->appTitle;
    }
}
