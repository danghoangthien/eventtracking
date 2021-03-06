<?php

namespace Hyper\DomainBundle\Repository\Content;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Content\InCategoryContentRepository;
use Hyper\Domain\Content\InCategoryContent;

/**
 * InCategoryContentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTInCategoryContentRepository extends EntityRepository implements InCategoryContentRepository
{
    public function save(InCategoryContent $inCategoryContent){
        $this->_em->persist($inCategoryContent);
        //$this->_em->flush();
        //echo "<hr/>"."$inCategoryContent persisted";
    }
    
    public function getByIdentifier($identifier){
        if (
            !array_key_exists('content_id',$identifier)
             ||
            !array_key_exists('category_id',$identifier)
        ) {
            throw new \Exception('invalid content in category identifier');
        }
        $inCategoryContent = $this->getByCategoryAndContent(
            $identifier['category_id'],
            $identifier['content_id']
        );
        return $inCategoryContent;
    }
    
    public function getByCategoryAndContent($categoryId,$contentId){
        return $this->findOneBy(
                    array(
                        'content' => $contentId,
                        'category' => $categoryId
                    )
                );
    }
    
}