<?php

namespace SiteBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use SiteBundle\Entity\Site;

class DefaultController extends FOSRestController
{
    
    /**
     * @Rest\Get("/site")
     */
    public function getAction(Request $request) {
        
        $site = $this->getDoctrine()
            ->getManager()
            ->getRepository(Site::class)
            ->find(1);
        
        return new View($site->getRawContent(), Response::HTTP_OK);
    }
}
