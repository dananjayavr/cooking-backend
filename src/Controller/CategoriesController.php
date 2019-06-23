<?php


namespace App\Controller;


use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CategoriesController extends AbstractController
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
            'ignored_attributes' => ['recipes']
        ]);
    }

    /**
     * @Route("/api/categories",name="categories.index")
     */
    public function index(Request $request, CategoryRepository $categoryRepository) : Response
    {
        $response = new Response();

        $categories = $categoryRepository->findAll();

        $response->setContent($this->createJSON($categories));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

    /**
     * @Route("/api/categories/{id}",name="categories.detail")
     */
    public function detail(Request $request, CategoryRepository $categoryRepository, int $id) : Response
    {
        $response = new Response();

        $category = $categoryRepository->find($id);

        $response->setContent($this->createJSON($category));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

}