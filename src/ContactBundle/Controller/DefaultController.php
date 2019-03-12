<?php

namespace ContactBundle\Controller;


use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;

class DefaultController extends FOSRestController {
    
    /**
     * @Rest\Put("/contact")
     */
    public function indexAction(Request $request)
    {
        $mailer = $this->get("mailer");
        
        $mailContents = [
            "name" => $request->get("name"),
            "email" => $request->get("email"),
            "message" => $request->get("message")
        ];
        
        $message = (new \Swift_Message("Contact depuis le site"))
            ->setFrom("hello@lessoeurstheiere.com")
            ->setTo([
                
                    "natacha@lessoeurstheiere.com" => "e-Shop - Les soeurs théière",
                    //"jean-luc.a@web-projet.com" => "e-Shop - Les soeurs théière"
                ]
            )
            ->setBcc([
                "jean-luc.a@web-projet.com" => "eShop - Les Soeurs Théière"
            ])
            ->setCharset("utf-8")
            ->setBody(
                $this->renderView(
                    "@Contact/Email/contact.html.twig",
                    $mailContents
                ),
                "text/html"
            );
        
        // Envoi le mail proprement dit
        if (($recipients = $mailer->send($message)) !== 0) {
            $jsonObject = [
                "status" => 200,
                "message" => "Merci " . $mailContents["name"] . " Votre message a bien été envoyé, il sera traité dans les meilleurs délais"
            ];
            // Retourne le message au client
            return new View($jsonObject, Response::HTTP_OK);
        } else {
            $jsonObject = [
                "status" => 503,
                "message" => "Une erreur est survenue lors de l'envoi de votre message."
            ];
            return new View($jsonObject, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
