<?php

namespace Hyper\DomainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('HyperDomainBundle:Default:index.html.twig', array('name' => $name));
    }
}
