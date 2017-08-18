<?php

namespace Hyper\DomainBundle\Repository\Content;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Content\ContentRepository;
use Hyper\Domain\Content\Content;
use Hyper\Domain\Category\Category;

/**
 * ContentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTContentRepository extends EntityRepository implements ContentRepository
{
    public function save(Content $content){
        $this->_em->persist($content);
        //$this->_em->flush();
    }
    
    public function getByIdentifier($identifier) {
         if (
            !array_key_exists('title',$identifier) || 
            !array_key_exists('app_id',$identifier)
        ) {
            throw new \Exception('invalid content identifier');
        }
        $item = $this->getByAppIdTitle(
            $identifier['title'],
            $identifier['app_id']
        );
        return $item;
    }
    
    public function getByAppIdTitle($title,$appId){
        return $this->findOneBy(
                    array(
                        'title' => $title,
                        'appId' => $appId
                    )
                );
    }
}