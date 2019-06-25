<?php


namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
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
            'ignored_attributes' => ['password','roles','isBlocked']
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
     * @Route("/api/users/{id}",name="users.detail",methods={"GET"})
     */
    public function detail(UserRepository $userRepository, int $id) : Response
    {
        $response = new Response();

        $category = $userRepository->find($id);

        $response->setContent($this->createJSON($category));
        $response->headers->set('Content-Type','application/json');

        return $response;

        /*return $this->json([
            'user' => $userRepository->find($id)
        ],200,[],[
            'groups' => ['api']
        ]);*/
    }

    /**
     * @Route("/api/users/{id}/delete",name="users.delete",methods={"DELETE"})
     * @\Sensio\Bundle\FrameworkExtraBundle\Configuration\Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
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

    /**
     * @Route("/api/users/{id}/update",name="users.update",methods={"PUT"})
     * @\Sensio\Bundle\FrameworkExtraBundle\Configuration\Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
     */
    public function update(int $id, Request $request, Security $security, ObjectManager $objectManager, UserPasswordEncoderInterface $passwordEncoder) : Response
    {
        $user = $objectManager->getRepository(User::class)->find($id);

        if(is_null($user)) {
            return $this->json([
                'error' => 'User Not Found.'
            ]);
        }

        if ($security->getUser() === $user) {
            $entityManager = $this->getDoctrine()->getManager();

            $firstname = $request->get('firstname');
            $lastname = $request->get('lastname');
            $password = $request->get('password');
            $passwordConfirmation = $request->get('password_confirmation');
            $gravatar = $request->get('gravatar');

            $errors = [];
            if($password != $passwordConfirmation) {
                $errors[] = "Passwords does not match.";
            } elseif (strlen($password) < 6) {
                $errors[]  = "Password should be at least 6 characters.";
            }

            if(!$errors)
            {
                $encodedPassword = $passwordEncoder->encodePassword($user,$password);
                $user->setPassword($encodedPassword);
                $user->setFirstname($firstname);
                $user->setLastname($lastname);
                $user->setGravatar($gravatar);

                try
                {
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->json([
                        'user' => $user
                    ]);

                } catch (UniqueConstraintViolationException $exception)
                {
                    $errors[] = "The email provided already has an account.";
                }
                catch (\Exception $exception)
                {
                    $errors[] = "Unable to save new user at this time.";
                }

            }

            return $this->json([
               'errors' => $errors
            ]);
        }

        return $this->json([
            'error' => 'Access Denied'
        ]);
    }

}