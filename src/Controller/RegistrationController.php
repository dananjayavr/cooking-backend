<?php


namespace App\Controller;


use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("api/register",name="registration.index", methods={"POST"})
     */
    public function index(ObjectManager $manager, UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {
        $user = new User();

        $email = $request->request->get("email");
        $password = $request->request->get("password");
        $passwordConfirmation = $request->request->get("password_confirmation");
        $gravatar = 'default.png';
        $roles = ["ROLE_USER"];
        #$roles = ["ROLE_ADMIN","ROLE_USER"];
        #$roles = ["ROLE_ADMIN"];
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');
        $dateCreated = \DateTime::createFromFormat('Y-m-d',date("y-m-d"));
        $isBlocked = false;

        $errors = [];
        if($password != $passwordConfirmation) {
            $errors[] = "Passwords does not match.";
        } elseif (strlen($password) < 6) {
            $errors[]  = "Password should be at least 6 characters.";
        }

        if(!$errors)
        {
            $encodedPassword = $passwordEncoder->encodePassword($user,$password);
            $user->setEmail($email);
            $user->setPassword($encodedPassword);
            $user->setDateCreated($dateCreated);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setGravatar($gravatar);
            $user->setRoles($roles);
            $user->setIsBlocked($isBlocked);

            try
            {
                $manager->persist($user);
                $manager->flush();

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
}