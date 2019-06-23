<?php


namespace App\Controller;


use App\Repository\CategoryRepository;
use App\Repository\RecipeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RecipesController extends AbstractController
{

    protected function createJSON($object)
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($object,'json',[
            'circular_reference_handler' => function($obj) {
                return $obj->getId();
            },
            'ignored_attributes' => ['user','category']
        ]);
    }

    /**
     * @Route("/api/recipes",name="recipes.index")
     */
    public function index(Request $request, RecipeRepository $recipeRepository) : Response
    {
        $response = new Response();

        $categories = $recipeRepository->findAll();

        $response->setContent($this->createJSON($categories));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

    /**
     * @Route("/api/recipes/{id}",name="recipes.detail")
     */
    public function detail(Request $request, RecipeRepository $recipeRepository, int $id) : Response
    {
        $response = new Response();

        $category = $recipeRepository->find($id);

        $response->setContent($this->createJSON($category));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

}