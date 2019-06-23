<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    /**
     * @Route("/",name="homepage.index")
     */
    public function index(Request $request) : Response
    {
        $response = new Response();
        // un objet JSON test pour tester que la page fonctionne. Eventuellement on peut remplacer cela par une vue Twig.
        // le tableau ['data' => 123] est juste un test
        $response->setContent(json_encode(['data' => 123]));
        $response->headers->set('Content-Type','application/json');
        return $response;
    }
}