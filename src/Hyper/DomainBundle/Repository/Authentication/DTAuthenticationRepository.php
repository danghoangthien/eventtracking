<?php

namespace Hyper\DomainBundle\Repository\Authentication;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Authentication\AuthenticationRepository;
use Hyper\Domain\Authentication\Authentication;
/**
 * AuthenticationRepository
 * 
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTAuthenticationRepository extends EntityRepository implements AuthenticationRepository
{
    public function save(Authentication $authentication){
        $this->_em->persist($authentication);
        //$this->_em->flush();
    }
    
    public function completeTransaction(){        
        $this->_em->flush();        
    }
    
    public function retrieveRecords()
    {
        $record = $this->findAll();
        return $record;
    }
        
    public function findUserPassword($user, $pass)
    {
        $user = $this->findOneBy(
            array('username' => $user, 'password' => $pass)
         );
        
        return $user;
    }
    
    public function findbyCriteria($field, $value)
    {
        $record = $this->findOneBy(array($field => $value));
        return $record;
    }
    
    public function findUser($user, $email)
    {
        $user = $this->findOneBy(
            array('username' => $user, 'email' => $email)
         );
        
        return $user;
    }
    
    public function findApiKey($user, $api_key)
    {
        $user = $this->findOneBy(
            array('username' => $user, 'apiKey' => $api_key)
         );
        
        return $user;
    }
    
    /*
    public function findByUsernameOrEmail($username,$email){
        //using query builder
    }
    */
    
    public function updatePassword($username, $password, $new_pass)
    {
        $user = $this->findOneBy(
            array('username' => $username, 'password' => $password)
         );
        
        $count = count($user);
        
        if($count > 0)
        {
            $user->setPassword(md5("$new_pass"));
            $this->_em->flush();
            
            return "success";
        }        
        else
        {
            return "failed";
        }
    }
    
    public function displayApplication()
    {        
        $em = $this->_em;
        $qb = $em->createQueryBuilder();
        
        $qb->select(array('DISTINCT(a.appId), a.appName','a'))
           ->from('Hyper\Domain\Application\Application', 'a');           
        //$qb->select(array('a'))->from('Hyper\Domain\Application\Application', 'a');           
        
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        return $results;
    }
    
    public function displayListing()
    {
//        $em = $this->getEntityManager();
        $em = $this->_em;
        $qb = $em->createQueryBuilder();

        $qb->select(array('a', 'ap'))
           ->from('Hyper\Domain\Authentication\Authentication', 'a')
//           ->innerJoin('Hyper\Domain\Application\Application', 'ap', 'WITH', 'a.application_id = ap.appId');
           ->innerJoin('Hyper\Domain\Application\Application', 'ap', 'IN', 'a.application_id = ap.appId');
        
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        return $results;
    }
    
    public function updatePasswordAppId($username, $name, $fileName, $password, $client_id, $email, $user_type, $api_key = null)
    {
        $user = $this->findOneBy(
            array('username' => $username)
        );
         
        $other = $this->findOneBy(
            array('email' => $email)
        );
        
        if(count($other) > 0)
        {
            if($other->getEmail() == $email && $user->getUsername() != $other->getUsername())
            {
                return "email_used";
            }
            else
            {
                //return "email updated";
            }
        }
        
        //return "nothing";
        
        $count = count($user);
        
        if($count > 0)
        {
            if($client_id == "" && $user_type == "")
            {
                if($password == "" && $fileName == "")
                {          
                    $user->setName("$name");
                    //$user->setImgPath("$fileName");
                    //$user->setClientId("$client_id");
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }
                else if($password != "" && $fileName == "")
                {          
                    $user->setName("$name");
                    //$user->setImgPath("$fileName");
                    //$user->setClientId("$client_id");
                    $user->setPassword(md5("$password"));
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }
                else
                {
                    $user->setName("$name");
                    $user->setImgPath("$fileName");
                    $user->setPassword(md5("$password"));
                    //$user->setClientId("$client_id");
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }            
                
                return "success";
            }
            else
            {
                if($password == "" && $fileName == "")
                {          
                    $user->setName("$name");
                    //$user->setImgPath("$fileName");
                    $user->setClientId("$client_id");
                    $user->setUserType("$user_type");
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }
                else if($password != "" && $fileName == "")
                {          
                    $user->setName("$name");
                    //$user->setImgPath("$fileName");
                    $user->setPassword(md5("$password"));
                    $user->setClientId("$client_id");
                    $user->setUserType("$user_type");
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }
                else
                {
                    $user->setName("$name");
                    $user->setImgPath("$fileName");
                    $user->setPassword(md5("$password"));
                    $user->setClientId("$client_id");
                    $user->setUserType("$user_type");
                    $user->setEmail("$email");
                    $user->setApiKey("$api_key");
                    $this->_em->flush();
                }            
                
                return "success";
            }
        }        
        else
        {
            return "failed";
        }
    }
    
    public function getResultAndCount($page, $rpp)
    {
        //$countQuery = $this->createQueryBuilder('au')->select('count(au.id)')->where($where)->getQuery();
        $countQuery = $this->createQueryBuilder('au')->select('count(au.id)')->getQuery();
        $totalRows = $countQuery->getSingleScalarResult();

        // $query = $this->createQueryBuilder('au')->select('au')->where($where)->getQuery();
        $query = $this->createQueryBuilder('au')->select('au')->orderBy('au.created', 'DESC')->getQuery();
        $offset = $rpp*($page-1);
        $rows = $query->setFirstResult($offset)->setMaxResults($rpp)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }
    
    public function resetPassword($email, $password)
    {
        $user = $this->findOneBy(
            array('email' => $email)
         );
        
        $count = count($user);
        
        if($count > 0)
        {
            $user->setPassword(md5("$password"));
            $this->_em->flush();
            
            return "success";
        }        
        else
        {
            return "failed";
        }
    }
    
    public function updateResetPasswordToken($email, $passwordToken, $passwordExpired) 
    {
        $auth = $this->findOneBy(
            array('email' => $email)
        );
        if ($auth instanceof Authentication) {
            $auth->setResetPasswordToken($passwordToken);
            $auth->setResetPasswordExpired($passwordExpired);
            $this->_em->flush();
        }
        
        return $auth;
    }
    
    public function resetPasswordByToken($token, $password)
    {
        $auth = $this->findOneBy(
            array('resetPasswordToken' => $token)
        );
        if ($auth instanceof Authentication) {
            $auth->setPassword(md5($password));
            $this->_em->flush();
        }
        
        return $auth;
    }
    
    public function deleteUser($id)
    {
        $user = $this->findOneBy(
            array('id' => $id)
         );
        
        $count = count($user);
        
        if($count > 0)
        {
            $qb = $this->_em->createQueryBuilder();
            $qb->delete('Hyper\Domain\Authentication\Authentication', 'a');
            $qb->andWhere($qb->expr()->eq('a.id', ':id'));
            $qb->setParameter(':id', $id);
            $qb->getQuery()->execute();
            
            return "success";
        }
        else
        {
            return "failed";
        }                        
    }
    
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function getPaginateData(
        $pageNumber, 
        $perpageNumber = 10, 
        $searchterm = '',
        $sort, 
        $direction = 'ASC'
    ) {
        $qbTotal = $this->createQueryBuilder('au')
                        ->select('count(au.id)');
                        
        $offset = $perpageNumber * ($pageNumber - 1);
        $qb = $this->createQueryBuilder('au')
                    ->select('au.id,au.username,au.clientId,au.email,au.lastLogin')
                    ->addSelect('au.ip,au.location,au.browserName,au.osName')
                    ->addSelect('au.osVersion,au.deviceType')
                    ->setFirstResult($offset)
                    ->setMaxResults($perpageNumber);
        if ($searchterm) {
            $qbTotal->where($qb->expr()->orX(
               $qb->expr()->like('au.username', $qbTotal->expr()->literal('%' . $searchterm . '%')),
               $qb->expr()->like('au.email', $qbTotal->expr()->literal('%' . $searchterm . '%'))
            ));
            
            $qb->where($qb->expr()->orX(
               $qb->expr()->like('au.username', $qbTotal->expr()->literal('%' . $searchterm . '%')),
               $qb->expr()->like('au.email', $qbTotal->expr()->literal('%' . $searchterm . '%'))
            ));
        }            
                    
        if ($sort) {
            $qb->orderBy($sort, strtoupper($direction));
        } 
        $qb->addOrderBy('au.created', 'DESC');
        
        $rows = $qb->getQuery()
                    //->getSQL();
                    ->getResult();
        //echo $rows;exit;         
                    
        $totalRows =  $qbTotal->getQuery()->getSingleScalarResult();           
                    
        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }
    
    public function deleteAuth($id)
    {
        return $this->_em->createQuery('DELETE FROM Hyper\Domain\Authentication\Authentication au WHERE au.id = ?1')
            ->setParameter(1, $id)
            ->execute();
    }
}