<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\Signup\Request;

class SignupClientAppTitleRequest
{
    private $id;
    private $title;
    private $s3Folder;
    private $appPlatform;

    public function __construct(array $clientAppTitle)
    {
        $this->setId($clientAppTitle);
        $this->setTitle($clientAppTitle);
        $this->setS3Folder($clientAppTitle);
        $this->setAppPlatform($clientAppTitle);

        return $this;
    }

    protected function setId($clientAppTitle)
    {
        $this->id = $clientAppTitle['id'];

        return $this;
    }

    protected function setTitle($clientAppTitle)
    {
        if (empty($clientAppTitle['title'])) {
            throw new \Exception('app_title[title] must be a value.');
        }
        $this->title = $clientAppTitle['title'];

        return $this;
    }

    protected function setS3Folder($clientAppTitle)
    {
        if (empty($clientAppTitle['s3_folder'])) {
            throw new \Exception('app_title[s3_folder] must be a value.');
        }
        $this->s3Folder = $clientAppTitle['s3_folder'];

        return $this;
    }

    protected function setAppPlatform($clientAppTitle)
    {
        if (empty($clientAppTitle['app_platform'])) {
            throw new \Exception('app_title[app_platform] must be a value.');
        }
        $this->appPlatform = $clientAppTitle['app_platform'];

        return $this;
    }

    public function id()
    {
        return $this->id;
    }

    public function title()
    {
        return $this->title;
    }

    public function s3Folder()
    {
        return $this->s3Folder;
    }

    public function appPlatform()
    {
        return $this->appPlatform;
    }
}