<?php


namespace App\Controller;


use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UsersController extends AbstractController
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
            'ignored_attributes' => ['recipes','password','roles','isBlocked']
        ]);
    }

    /**
     * @Route("/api/users",name="users.index")
     */
    public function index(Request $request, UserRepository $userRepository) : Response
    {
       $response = new Response();

        $categories = $userRepository->findAll();

        $response->setContent($this->createJSON($categories));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

    /**
     * @Route("/api/users/{id}",name="users.detail")
     */
    public function detail(Request $request, UserRepository $userRepository, int $id) : Response
    {
        /*$response = new Response();

        $category = $userRepository->find($id);

        $response->setContent($this->createJSON($category));
        $response->headers->set('Content-Type','application/json');

        return $response;*/

        return $this->json([
            'user' => $userRepository->find($id)
        ],200,[],[
            'groups' => ['api']
        ]);
    }

    /**
     * @Route("/api/users/{id}/delete",name="users.detail",methods={"DELETE"})
     */
    public function delete(int $id, Request $request, Security $security, UserRepository $userRepository) : Response
    {
        $currentUser = $security->getUser();
        $userTobeDeleted = $userRepository->find($id);
        $entityManger = $this->getDoctrine()->getManager();

        if($currentUser === $userTobeDeleted)
        {
            $entityManger->remove($userTobeDeleted);

            $entityManger->flush();

            return $this->json([
                'message' => 'User Deleted.'
            ]);
        }

        return $this->json([
            'error' => 'Access Denied'
        ]);
    }

}