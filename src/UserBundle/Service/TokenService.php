<?php
/**
 * @name TokenService Service de génération, contrôle des Token d'authentification JWT
 * @author IDea Factory (dev-team@ideafactory.fr) - Nov. 2018
 * @package UserBundle\Service
 * @version 1.0.0
 */
namespace UserBundle\Service;

use ReallySimpleJWT\Token;
use ReallySimpleJWT\TokenValidator;
use UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ReallySimpleJWT\Exception\TokenValidatorException;

class TokenService {

    /**
     * Clé de chiffrement du token
     * @var string
     */
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }
    /**
     * Génère un token à partir de l'identifiant de l'utilisateur
     * @return string
     */
    public function generate(User $user): string {
        $expirationDate = new \DateTime();
	    $expirationDate->add(new \DateInterval("P1D"));
	    return Token::getToken(
	       $user->getId(),
	       $this->secret,
	       $expirationDate->format('Y-m-d H:i:s'),
	        $user->getId()
	    );
    }

    /**
     * Authentifie une requête et retourne un tableau avec le code et l'identifiant
     * de l'utilisateur concerné.
     */
    public function authenticate(Request $request): array {
        return $this->requestAuthentication($request);
    }

    /**
     * Authentification à l'aide du Token
     * @param Request $request
     * @return array
     */
    public function tokenAuthentication(Request $request): array {

        try {
            $authTokenHeader = $request->get("token", $request->headers->get('X-Auth-Token'));
                
                
            $isValid = Token::validate($authTokenHeader, $this->secret);
                
            if ($isValid) {
                $validator = new TokenValidator();
                    
                $validator->splitToken($authTokenHeader)
                    ->validateExpiration()
                    ->validateSignature($this->secret);
                    
                $payload = json_decode($validator->getPayload());
                    
                    
                $now = new \DateTime();
                $expiration = \DateTime::createFromFormat("Y-m-d H:i:s", $payload->exp);
                    
                if ($now > $expiration) {
                    return [
                        "code" => Response::HTTP_PRECONDITION_FAILED,
                        "user" => $payload->user_id
                    ];
                } else {
                    return [
                        "code" => Response::HTTP_OK,
                        "user" => $payload->user_id
                    ];
                }
            }
        } catch(TokenValidatorException $tokenException) {
            return [
                "code" => Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED,
                "user" => 0
            ];
        }
    }
    
    /**
     * Authentifie une requête
     */
    private function requestAuthentication(Request $request): array {
        $authTokenHeader = $request->headers->get('X-Auth-Token');
        
        // $isValid = Token::validate($authTokenHeader, $this->secret);
	    
	    //if ($isValid) {
	        $validator = new TokenValidator();
	        $validator->splitToken($authTokenHeader)
	           ->validateSignature($this->secret);
	        
            $payload = json_decode($validator->getPayload(), true);

            
            $now = new \DateTime();
            $expiration = \DateTime::createFromFormat("Y-m-d H:i:s", $payload["exp"]);

            if ($now > $expiration) {
                return [
                    "code" => Response::HTTP_PRECONDITION_FAILED,
                    "user" => $payload["user_id"]
                ];
            } else {
                return [
                    "code" => Response::HTTP_OK,
                    "user" => $payload["user_id"]
                ];
            }


	    //}
	    
	    return [
            "code" => Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED,
            "user" => 0
        ];
    }
}