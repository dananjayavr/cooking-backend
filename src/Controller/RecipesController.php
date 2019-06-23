<?php


namespace App\Controller;



use App\Entity\Category;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RecipeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
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
     * @Route("/api/recipes",name="recipes.index",methods={"GET"})
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

    /**
     * @Route("/api/recipes",name="recipes.add",methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function add(ObjectManager $manager, Request $request, Security $security, UserInterface $user) : Response
    {
        $recipe = new Recipe();
        $category = new Category();

        $currentUser = $security->getUser();


        // TODO: refactor this bit using a for-loop and an array
        $userId = $currentUser->getId();

        $categoryName = $request->get('categoryName');
        $title = $request->get('title');
        $image = $request->get('image');
        $preparation = $request->get('preparation');
        $ingredient = $request->get('ingredient');
        $color = $request->get('color');
        $intro = $request->get('intro');

        $dateCreated = \DateTime::createFromFormat('Y-m-d',date("y-m-d"));

        $cookingTime = $request->get('cookingTime');
        $preparationTime = $request->get('preparationTime');
        $difficulty = $request->get('difficulty');
        $price = $request->get('price');

        // TODO: add more server side data verification here
        $errors = [];
        if(empty($categoryName)) {
            $errors[] = "Category cannot be empty.";
        } elseif (empty($price)) {
            $errors[]  = "Price cannot be empty.";
        }

        if(!$errors)
        {

            $recipe->setDateCreated($dateCreated);
            $recipe->setCategory($category->setName($categoryName));
            $recipe->setColor($color);
            $recipe->setCookingTime($cookingTime);
            $recipe->setTitle($title);
            $recipe->setImage($image);
            $recipe->setPreparation($preparation);
            $recipe->setIngredient($ingredient);
            $recipe->setPreparationTime($preparationTime);
            $recipe->setDifficulty($difficulty);
            $recipe->setIntro($intro);
            //TODO: correct this
            $recipe->setUser($userId);
            $recipe->setPrice($price);

            try
            {
                $manager->persist($recipe);
                $manager->persist($category);

                $manager->flush();

                return $this->json([
                    'recipe' => $recipe
                ],200);
            } /*catch (UniqueConstraintViolationException $exception)
            {
                $errors[] = "The email provided already has an account.";
            }*/
            catch (\Exception $exception)
            {
                dd($exception);
                $errors[] = "Unable to save new recipe at this time.";
            }

        }

        return $this->json([
            'errors' => $errors
        ],400);
    }

}