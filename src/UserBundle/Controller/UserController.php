<?php
/**
* @name UserController Service REST pour la gestion des utilisateurs
* @author IDea Factory (dev-team@ideafactory.fr)
* @package UserBundle\Controller
* @version 1.0.1
* 	Modification de la route pour l'utilisateur anonyme
* @version 1.0.2
*   Gestion de la récupération du mot de passe
*/
namespace UserBundle\Controller;


use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use UserBundle\Entity\User;
use UserBundle\Entity\Groupe;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Common\Util\ClassUtils;
use UserBundle\Service\TokenService;
use UserBundle\Payment\PaymentProcess;
use UserBundle\Entity\Basket;

class UserController extends FOSRestController {
	
	/**
	 * Instance du repo des utilisateurs
	 * @var unknown
	 */
	private $_repository;
	
	/**
	 * Instance d'un utilisateur complet
	 * @var \UserBundle\Entity\User
	 */
	private $_wholeUser;
	

	/**
	 * Instance du gestionnaire de token JWT
	 * @var TokenService
	 */
	private $tokenService;
	
	/**
	 * Constructeur du contrôleur User
	 */
	public function __construct(TokenService $tokenService) {
	    $this->tokenService = $tokenService;
	}
	
	/**
	 * @Rest\Post("/account/{id}")
	 * 
	 * @param Request $request
	 */
	public function updateInfoAction(Request $request) {
	    $user = $this->getDoctrine()
	       ->getManager()
	       ->getRepository("UserBundle:User")
	       ->find($request->get("id"));
	    
	       if ($user) {
	           // Génère le contenu à partir des données se terminant par _
	           $content = [];
	           
	           $datas = $request->request->all();
	           
	           foreach ($datas as $param => $value) {
	               if (substr($param, -1) === "_") {
	                   $param = substr($param, 0, strlen($param) - 1);
	                   $content[$param] = $value;
	               }
	           }
	           $user->setContent(json_encode($content));
	           
	           $entityManager = $this->getDoctrine()->getManager();
	           $entityManager->persist($user);
	           $entityManager->flush();
	           
	           return new View("Vos informations ont bien été mises à jour", Response::HTTP_OK);
	       }
	       
	       return new View("Cet utilisateur n'est pas connu dans notre système", Response::HTTP_FORBIDDEN);
	}
	
	/**
	 * @Rest\Post("/account/password/update")
	 *
	 * @param Request $request
	 */
	public function updatePasswordAction(Request $request) {
	    if ($this->authToken($request)) {
	        // Regénère le mot de passe
	        $this->_wholeUser->setSecurityPass($this->_createPassword($request->get("password"), $this->_wholeUser->getSalt()));
	        
	        $entityManager = $this->getDoctrine()->getManager();
	        $entityManager->persist($this->_wholeUser);
	        $entityManager->flush();
	        
	        return new View("Vos informations ont bien été mises à jour", Response::HTTP_OK);
	    }
	    
	    return new View("Une erreur est survenue lors de la mise à jour de votre mot de passe", Response::HTTP_FORBIDDEN);
	}
	
	/**
	 * @Rest\Put("/register")
	 */
	public function registerAction(Request $request) {
		
	    if (!$request->get("suckrobot")) {
    		if (!$this->_alreadyExists($request->get("email"))) {
    			$this->_wholeUser = new User();
    			
    			// Génère le sel de renforcement du mot de passe
    			$salt = $this->_makeSalt();
    			
    			// Génère le contenu à partir des données se terminant par _
    			$content = [];
    			
    			$datas = $request->request->all();
    			
    			foreach ($datas as $param => $value) {
    			    if (substr($param, -1) === "_") {
    			        $param = substr($param, 0, strlen($param) - 1);
    			        $content[$param] = $value;
    			    }
    			}
    
    			
    			$this->_wholeUser
    				->setLogin($request->get("email"))
    				->setSecurityPass($this->_createPassword($request->get("password"), $salt))
    				->setSalt($salt)
    				->setIsValid(true)
    				->setCreatedAt(new \DateTime())
    				->setLastLogin(new \DateTime())
    				->setValidatedAt(new \DateTime())
    				->setContent(json_encode($content))
    				->setGroup($this->_getCustomerGroup());
    			
    			// Fait persister la donnée
    			$entityManager = $this->getDoctrine()->getManager();
    			$entityManager->persist($this->_wholeUser);
    			$entityManager->flush();
    			
    			return new View($this->_format($this->_wholeUser), Response::HTTP_CREATED);
    		}
    		
    		return new View("Un compte avec cet email existe déjà sur ce site", Response::HTTP_CONFLICT);
	    } else {
	        return new View("L'accès à cette boutique n'est pas autorisée aux robots", Response::HTTP_FORBIDDEN);
	    }
	}
	
	/**
	 * @Rest\Post("/signin")
	 * @param Request $request Requête envoyée
	 */
	public function signinAction(Request $request) {
		
		if ($request) {
			if (!$this->_checkLogin($request->get("login"))) {
				return new View("L'adresse e-mail est inconnue ou votre compte a été invalidé", Response::HTTP_FORBIDDEN);
			}
			
			if (!$this->_validPassword($request->get("password"))) {
				return new View("Votre mot de passe est incorrect, veuillez réessayer s'il vous plaît", Response::HTTP_FORBIDDEN);
			}
			
			
			return new View($this->_format($this->_wholeUser), Response::HTTP_OK);
		}
	}

	/**
	 * @Rest\Get("/user/email/{login}/{username}", defaults={"username"=""})
	 * @param Request $request Requête envoyée
	 */
	public function checkEMailAction(Request $request): View {
	    if (!$this->_checkLogin($request->get("login"))) {
	        return new View("L'adresse e-mail est inconnue ou votre compte a été invalidé", Response::HTTP_FORBIDDEN);
	    } else {
	        if ($request->get("username") !== "") {
	            $userDetail = $this->_wholeUser->getContent();
	            if (strtoupper($userDetail->lastName) !== strtoupper($request->get("username"))) {
	                return new View("Ce nom ne correspond pas à l'adresse e-mail saisie", Response::HTTP_FORBIDDEN);
	            }
	        }
	    }
	    return new View("Adresse d'utilisateur valide", Response::HTTP_OK);
	}
	
	/**
	 * @Rest\Post("/user/recover")
	 * @param Request $request Requête envoyée
	 */
	public function passwordRecoveringAction(Request $request): View {
	    if ($this->_checkLogin($request->get("login"))) {
	       // Générer un token à expiration courte...
	        $token = $this->tokenService->generate($this->_wholeUser);
	        
	        $userDetail = $this->_wholeUser->getContent();
	        
	        // Générer l'e-mail pour l'envoi du lien de récupération
	        $emailContent = $this->renderView(
	            "@User/Email/recovering.html.twig",
	            [
	                "user" => $userDetail->firstName . " " . $userDetail->lastName,
	                "link" => "https://api.lessoeurstheiere.com/user/recover/" . $token
	            ]
	        );
	        $this->_sendMailToCustomer($emailContent);
	        return new View("Vérifiez votre boîte mail et cliquez sur le lien de réinitialisation pour récupérer votre nouveau mot de passe.", Response::HTTP_OK);
	        
	    }
	    return new View("L'adresse e-mail est inconnue ou votre compte a été invalidé", Response::HTTP_FORBIDDEN);
	}
	
	/**
	 * @Rest\Get("/user/recover/{token}")
	 * @param Request $request Requête envoyée
	 */
	public function passwordRecoverAction(Request $request) {
	    $authGuard = $this->tokenService->tokenAuthentication($request);
	    
	    if ($authGuard["code"] === Response::HTTP_OK) {
	        $this->_wholeUser = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($authGuard["user"]);
	        
	        // Générer le nouveau mot de passe à transmettre
	        $password = $this->_makePassword();
	        
	        $this->_wholeUser->setSecurityPass($this->_createPassword($password, $this->_wholeUser->getSalt()));
	        
	        // Fait persister la donnée
	        $entityManager = $this->getDoctrine()->getManager();
	        $entityManager->persist($this->_wholeUser);
	        $entityManager->flush();
	        
	        $userDetail = $this->_wholeUser->getContent();
	        
	        // Prépare le mail à envoyer
	        $emailContent = $this->renderView(
	            "@User/Email/password.html.twig",
	            [
	                "user" => $userDetail->firstName . " " . $userDetail->lastName,
	                "password" => $password
	            ]
	        );
	        
	        $this->_sendMailToCustomer($emailContent, "Votre nouveau mot de passe sur Les Soeurs Théière");
	        
	        return new View($this->_format($this->_wholeUser, $request->get("token")));
	    }
	    
	    return new View("Token non valide ou expiré", $authGuard["code"]);
	}
	
	/**
	 * @Rest\Post("/account/address/billing")
	 * @param Request $request Requête envoyée
	 */
	public function addBillingAddress(Request $request){
	    if ($this->authToken($request)) {
	        
	        $account = $this->_wholeUser;
	        
    	    // Définit une nouvelle adresse de facturation
    	    $billingAddress = [
    	        "address" => $request->get("address"),
    	        "zipcode" => $request->get("zipcode"),
    	        "city" => $request->get("city"),
    	        "country" => $request->get("country"),
    	        "sameAsDelivery" => $request->get("asDelivery") ? true : false
    	    ];
    	    
    	    if ($request->get("asDelivery")) {
    	        $deliveryAddress = [
    	            "name" => "Principale",
    	            "address" => $request->get("address"),
    	            "zipcode" => $request->get("zipcode"),
    	            "city" => $request->get("city"),
    	            "country" => $request->get("country"),
    	        ];
    	        $account->addDeliveryAddress($deliveryAddress);
    	    }
    	    
    	    $account->addBillingAddress($billingAddress);
    	    
    	    
    	    $entityManager = $this->getDoctrine()->getManager();
    	    $entityManager->persist($account);
    	    
    	    $entityManager->flush();
    	    
    	    // Recharge la ligne utilisateur
    	    $user = $entityManager
    	       ->getRepository("UserBundle:User")
    	       ->find($this->_wholeUser->getId());

    	    
    	    return new View($user->getRawContent()["addresses"], Response::HTTP_OK);
	    }
	    return new View("Le compte n'est plus actif, ou a été invalidé", Response::HTTP_FORBIDDEN);
	}

	/**
	 * @Rest\Put("/account/address/billing")
	 * @param Request $request Requête envoyée
	 */
	public function updBillingAddress(Request $request){
	    if ($this->authToken($request)) {
	        
	        $account = $this->_wholeUser;
	        
	        $billingAddress = $account->getRawContent()["addresses"]["billing"];
	        
	        // Définit une nouvelle adresse de facturation
	        $billingAddress["address"] = $request->get("address");
	        $billingAddress["zipcode"] = $request->get("zipcode");
	        $billingAddress["city"] = $request->get("city");
	        $billingAddress["country"] = $request->get("country");
	        
	        $account->addBillingAddress($billingAddress);
	        
	        
	        $entityManager = $this->getDoctrine()->getManager();
	        $entityManager->persist($account);
	        
	        $entityManager->flush();
	        
	        // Recharge la ligne utilisateur
	        $user = $entityManager
	           ->getRepository("UserBundle:User")
	           ->find($this->_wholeUser->getId());
	        
	        
	        return new View($user->getRawContent()["addresses"], Response::HTTP_OK);
	    }
	    return new View("Le compte n'est plus actif, ou a été invalidé", Response::HTTP_FORBIDDEN);
	}
	
	/**
	 * @Rest\Post("/account/address/delivery")
	 * @param Request $request Requête envoyée
	 */
	public function addDeliveryAddress(Request $request){
	    if ($this->authToken($request)) {
	        
	        $account = $this->_wholeUser;
	        
	        // Définit une nouvelle adresse de facturation
	        $billingAddress = [
	            "name" => $request->get("name"),
	            "address" => $request->get("address"),
	            "zipcode" => $request->get("zipcode"),
	            "city" => $request->get("city"),
	            "country" => $request->get("country"),
	        ];
	        

	        
	        $account->addDeliveryAddress($billingAddress);
	        
	        $entityManager = $this->getDoctrine()->getManager();
	        $entityManager->persist($account);
	        
	        $entityManager->flush();
	        
	        $account = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($this->_wholeUser->getId());
	        
            $deliveryAddresses = $account->getRawContent()["addresses"]["delivery"];
	        $length = count($deliveryAddresses);
	        
	        // Retourne la dernière adresse de livraison créée
	        return new View($deliveryAddresses[$length-1], Response::HTTP_OK);
	    }
	    
	    return new View("Le compte n'est plus actif, ou a été invalidé", Response::HTTP_FORBIDDEN);
	    
	}
	
	/**
	 * @Rest\Get("/nouser")
	 * @param void
	 */
	public function loginAction() {
		
		if (!$this->_checkLogin("anonymous")) {
			return new View("L'accès anonyme n'est pas autorisé sur cette application", Response::HTTP_FORBIDDEN);
		}
			
			
		return new View($this->_format($this->_wholeUser), Response::HTTP_OK);
	}
	
	/**
	 * @Rest\Get("/token/{token}")
	 * @param Request $request
	 */
	public function getUserFromToken(Request $request) {
	    $authGuard = $this->tokenService->tokenAuthentication($request);
	    
	    if ($authGuard["code"] === Response::HTTP_OK) {
	        $this->_wholeUser = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($authGuard["user"]);
	        return new View($this->_format($this->_wholeUser, $request->get("token")));
	    }
	    
	    return new View("Token non valide ou expiré", $authGuard["code"]);
	}

	/**
	 * @Rest\Post("/checkout/process")
	 *
	 * @param Request $request
	 */
	public function processPaymentAction(Request $request) {
	    $authGuard = $this->tokenService->tokenAuthentication($request);
	    
	    if ($authGuard["code"] === Response::HTTP_OK) {
	        $user = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($authGuard["user"]);

	        // Récupère le nombre de commandes réalisées
	        $repository = $this
	           ->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:Basket");
	         

	        $nextOrderNum = $repository->getNextOrderNum();

	          
	       // Créer une nouvelle instance de panier
	       try {
	           $date = new \DateTime();
	           
	           $orderNum = $date->format('Ymd') . "-" . sprintf("%'.05d\n", $nextOrderNum);
	           $order = new Basket();
    	       $order->setUser($user)
    	           ->setReference($request->get("paymentMode") === "cc" ? $request->get("transId") : $orderNum)
    	           ->setConvertDate(new \DateTime())
    	           ->setConvertTime(new \DateTime())
    	           ->setFullTaxTotal($request->get("amount"))
    	           ->setPaymentMode($request->get("paymentMode"))
    	           ->setContent($request);
    	       // Dans tous les cas, on génère l'email final
    	       $emailContent = $this->renderView(
    	               "@User/Email/order.html.twig",
    	               [
    	                   "order" => $order
    	               ]
    	       );
	       } catch(\Exception $e) {
	           return new View("Erreur d'instanciation : " . $e->getMessage() . "Commande : " . $orderNum . " Montant : " . $request->get("amount"), 500);
	       }
	        // Gérer l'appel à l'API de paiement
	        //echo "Chargement du module de paiement<br>\n";
	        if ($request->get("paymentMode") === "cc") {
    	        /**
	            $processing = new PaymentProcess();
    	        
    	        $processing->setAmount($request->get("amount"))
    	           ->setCardNumber($request->get("cardnumber"))
    	           ->setExpiryMonth($request->get("expirationmonth"))
    	           ->setExpiryYear($request->get("expirationyear"))
    	           ->setCsc($request->get("cvv"))
    	           ->setScheme($request->get("scheme", "visa"))
    	           ->setOrderId($request->get("token", $request->headers->get('X-Auth-Token')));
    	        
    	        try {
    	           $response = $processing->process("simple");
    	           $info = $response->createPaymentResult->commonResponse;
    	           $responseContent = [
    	               "code" => $info->responseCode,
    	               "status" => $info->transactionStatusLabel
    	           ];
    	               
    	           return new View(json_encode($responseContent), Response::HTTP_OK);
    	       } catch(\Exception $exception) {
    	           return new View("An error occured while payment processing : " . $exception, Response::HTTP_SERVICE_UNAVAILABLE);

    	       }
    	       **/
	            // Persistence de la commande
	            $entityManager = $this->getDoctrine()->getManager();
	            $entityManager->persist($order);
	            
	            $entityManager->flush();
	            
	            return new View("Pré commande enregistrée", Response::HTTP_OK);
	        } else {
	            if ($request->get("paymentMode") === "ch") {
	                // Persistence de la commande
	                $entityManager = $this->getDoctrine()->getManager();
	                $entityManager->persist($order);
	                
	                $entityManager->flush();
	                
	                // Génère l'e-mail à l'attention des administrateurs
	                $message = "Votre commande a bien été enregistrée.";
	                if (!$this->_sendMail($emailContent)) {
	                    $message .= "Une erreur est survenue lors de l'envoi de l'email vers notre boutique.";
	                }
	                
	                // Mode de paiement asynchrone
	                return new View($message, Response::HTTP_OK);
	            }
	        }
	    }
	    
	    return new View("Token non valide ou expiré", Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED);
	}
	
	/**
	 * @Rest\Post("/checkout/payment/test")
	 *
	 * @param Request $request
	 */
	public function checkoutPaymentTestAction(Request $request) {
	    // Récupère la commande par son numéro de transaction
	    $transId = $request->get("vads_trans_id");
	    $transStatus = $request->get("vads_trans_status");
	    $userId = $request->get("vads_cust_id");
	    
	    if ($transStatus === "AUTHORISED") {
	        $user = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($userId);
	        
	        $basketRepository = $this
	           ->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:Basket");
	        
	       // Récupérer la commande à partir du numéro de transaction et de l'identifiant du client
	       $order = $basketRepository->findOneBy(
	           [
	               "user" => $user,
	               "reference" => $transId
	           ]
	       );
	       
	       // Mise à jour de la commande et envoi du mail de confirmation
	        $nextOrderNum = $basketRepository->getNextOrderNum();
	        
	        $date = new \DateTime();
	        
	        $orderNum = $date->format('Ymd') . "-" . sprintf("%'.05d\n", $nextOrderNum);
	        $order
	           ->setReference($orderNum)
	           ->setValidationDate($date);
	        
	       // Persistence de la commande
	       $entityManager = $this->getDoctrine()->getManager();
	       $entityManager->persist($order);
	           
	       $entityManager->flush();
	       
	       // Dans tous les cas, on génère l'email final
	       $emailContent = $this->renderView(
	           "@User/Email/order.html.twig",
	           [
	               "order" => $order
	           ]
	       );
	       
	       $this->_sendMail($emailContent);
	    }
	}
	
	/**
	 * @Rest\Post("/checkout/payment/done")
	 *
	 * @param Request $request
	 */
	public function checkoutPaymentDoneAction(Request $request) {
	    return $this->checkoutPaymentTestAction($request);
	}
	
	/**
	 * Retourne l'utilisateur courant à partir du token JWT
	 * @param Request $request
	 * @return bool
	 */
	private function authToken(Request $request): bool {
	    $authGuard = $this->tokenService->tokenAuthentication($request);
	    
	    if ($authGuard["code"] === Response::HTTP_OK) {
	        $this->_wholeUser = $this->getDoctrine()
	           ->getManager()
	           ->getRepository("UserBundle:User")
	           ->find($authGuard["user"]);
	        return true;
	    }
	    
	    return false;
	}
	
	/**
	 * Détermine si le login saisi n'existe pas déjà
	 * @param string $login
	 * @return bool
	 */
	private function _alreadyExists(string $login): bool {
		$this->_wholeUser = $this->getDoctrine()
			->getManager()
			->getRepository("UserBundle:User")
			->findOneBy(["login" => $login]);
		
		if ($this->_wholeUser) {
			return true;
		}
		
		return false;
	}
	/**
	 * Vérifie l'existence du login et sa validité
	 * @return boolean
	 */
	private function _checkLogin(string $login): bool {
		$this->_wholeUser = $this->getDoctrine()
			->getManager()
			->getRepository("UserBundle:User")
			->findOneBy(["login" => $login]);
		
		if ($this->_wholeUser) {
			if ($this->_wholeUser->getIsValid()) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Vérifie le mot de passe avec la clé de renforcement
	 * @param string $password
	 * @return boolean
	 */
	private function _validPassword(string $password): bool {
		$saltedPassword = $this->_wholeUser->getSalt() . $password . $this->_wholeUser->getSalt();
		
		if (md5($saltedPassword) === $this->_wholeUser->getSecurityPass()) {
			return true;
		}
		
		return false;
	}

	/**
	 * Récupère ou crée le groupe de l'utilisateur identifié
	 * @param int $id
	 * @return \UserBundle\Controller\UserBundle\Entity\Groupe
	 */
	private function _getCustomerGroup(int $id = null) {
		if (is_null($id)) {
			$group = $this->getDoctrine()
				->getManager()
				->getRepository("UserBundle:Groupe")
				->findOneBy(["libelle" => "customer"]);
			
			if (!$group) {
				$group = new UserBundle\Entity\Groupe();
				$group
					->setLibelle("customer")
					->setCanBeDeleted(false);
				// Assurer la persistence du groupe
				$entityManager = $this->getDoctrine()->getManager();
				$entityManager->persist($group);
				$entityManager->flush();
			}
		} else {
			// Retourne le groupe de l'utilisateur à partir de son id
			$group = $this->getDoctrine()
				->getManager()
				->getRepository("UserBundle:Groupe")
				->find($id);
		}
		
		return $group;
		
	}
	
	/**
	 * Génère un sel aléatoire
	 * @return string
	 * @todo Créer un service pour gérer ce type de traitement
	 */
	private function _makeSalt(): string {
		$chars = [
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
			"A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
			"0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
			"*", "-", "+", "/", "#", "@", "^", "|"
		];
		
		$saltChars = [];
		
		for ($i = 0; $i < 10; $i++) {
			$random = rand(0, 69);
			$saltChars[$i] = $chars[$random];
		}
		
		return join("",$saltChars);
	}
	
	/**
	 * Retourne le mot de passe renforcé en hash md5
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	private function _createPassword(string $password, string $salt): string {
		return md5($salt.$password.$salt);
	}
	
	/**
	 * Génère aléatoirement un mot de passe avec 8 caractères
	 * @return string
	 */
	private function _makePassword(): string {
	    $_mins = [
	        "a","b","c","d","e","f","g",'h',"i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"
	    ];
	    $_majs = [
	        "A","B","B","D","E","F","G",'H',"I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"
	    ];
	    
	    $_nums = [
	        "0","1","2","3","4","5","6","7","8","9"
	    ];
	    
	    $_puncs = [
	        "&","(","-","/","@",")","+","*"
	    ];
	    
	    $_all = [
	        $_mins, $_majs, $_nums, $_puncs
	    ];
	    
	    $passwordChars = [];
	    
	    for ($i = 0; $i < 8; $i++) {
	        // Extrait une des séries aléatoirement
	        $serialIndice = random_int(0, 3);
	        $serial = $_all[$serialIndice];
	        
	        // Extrait aléatoirement un caractère de la série sélectionnée
	        $serialIndice = random_int(0, count($serial) - 1);
	        
	        $passwordChars[$i] = $serial[$serialIndice];
	    }
	    
	    return join("",$passwordChars);
	}
	
	/**
	 * Retourne le formatage d'un utilisateur complet
	 * @param Entity $userEntity
	 * @return array
	 */
	private function _format($userEntity) {
		$datas = [];
		
		$datas["id"] = $userEntity->getId();
		
		$datas["login"] = $userEntity->getLogin();
		
		$datas["token"] = $this->tokenService->generate($this->_wholeUser);
		
		// Traite le contenu, pour récupérer les données cohérentes
		$jsonContent = $userEntity->getContent();
		
		if ($jsonContent !== null) {
			$datas["name"] = $jsonContent->firstName .
				" " . $jsonContent->lastName;
			
			$datas["userDetails"] = $userEntity->getRawContent();
		}
		
		// Traite les options de menu
		$group = $userEntity->getGroup();
		
		$menus = [];
		if ($group->getMenus()) {
			foreach($group->getMenus() as $menu) {
			    if ($this->_isDynamic($menu)) {
			        $menus[] = [
			            "id" => $menu->getId(),
			            "slug" => $menu->getSlug(),
			            "region" => $menu->getRegion(),
			            "content" => $menu->getContent(),
			            "options" => $menu->categoriesToArray()
			        ];
			    }
			}
		}
		
		$datas["menus"] = $menus;
		
		return $datas;
	}
	
	private function _isDynamic($menu) {
	    if (
	        $menu->getRegion() === "_top_bottom" ||
	        $menu->getRegion() === "_footer_left" ||
	        $menu->getRegion() === "_footer_right"
	    ) {
	            return false;
	    }
	    return true;
	}
	
	private function _sendMail(string $content) {
	    $mailer = $this->get("mailer");
	    
	    $message = (new \Swift_Message("Une nouvelle commande vient d'être effectuée"))
	       ->setFrom("hello@lessoeurstheiere.com")
	       ->setTo([
	        
	        "natacha@lessoeurstheiere.com" => "e-Shop - Les soeurs théière",
	        "hello@lessoeurstheiere.com" => "e-Shop - Les Soeurs Théière",
	        //"jean-luc.a@web-projet.com" => "e-Shop - Les soeurs théière"
	       ])
	       ->setBcc([
	            "jean-luc.a@web-projet.com" => "eShop - Les Soeurs Théière"
	        ])
	        ->setCharset("utf-8")
	        ->setBody(
                $content,
	            "text/html"
	         );
	    // Envoi le mail proprement dit
	    if (($recipients = $mailer->send($message)) !== 0) {
	        // Retourne le message au client
	        return true;
	    }
	    return false;
	}
	
	private function _sendMailToCustomer(string $content, string $title = "Vous avez demandé la réinitialisation du mot de passe sur Les Soeurs Théière") {
	    $mailer = $this->get("mailer");
	    
	    $userDetail = $this->_wholeUser->getContent();
	    
	    $message = (new \Swift_Message($title))
	    ->setFrom("hello@lessoeurstheiere.com")
	    ->setTo([
	        $this->_wholeUser->getLogin() => $userDetail->firstName . " " . $userDetail->lastName,
	    ])
	    ->setBcc([
	        "jean-luc.a@web-projet.com" => "Supervision les Soeurs Théière"
	    ])
	    ->setCharset("utf-8")
	    ->setBody(
	        $content,
	        "text/html"
	        );
	    // Envoi le mail proprement dit
	    if (($recipients = $mailer->send($message)) !== 0) {
	        // Retourne le message au client
	        return true;
	    }
	    return false;
	}
}
