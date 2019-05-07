<?php
namespace ContentBundle\Controller;

/**
* @name BasketController
* @author IDea Factory - Déc. 2018 (dev-team@ideafactory.fr)
* @package ContentBundle\Controller
* @version 1.0.0
*/

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Service\TokenService;
use FOS\RestBundle\View\View;




class BasketController extends FOSRestController {}