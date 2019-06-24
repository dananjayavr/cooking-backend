<?php


namespace App\Controller;


use App\Entity\Category;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\RecipeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
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
            'ignored_attributes' => ['user','category','recipes'] // removed user
        ]);
    }

    /**
     * @Route("/api/recipes",name="recipes.index",methods={"GET"})
     */
    public function index(Request $request, RecipeRepository $recipeRepository) : Response
    {
        $response = new Response();

        $recipes = $recipeRepository->findAll();

        /*$response->setContent($this->createJSON($categories));
        $response->headers->set('Content-Type','application/json');*/

        //return $response;

        return $this->json([
            'user' => $recipes
        ],200,[],[
            'groups' => ['api']
        ]);
    }

    /**
     * @Route("/api/recipes/{id}",name="recipes.detail")
     */
    public function detail(Request $request, RecipeRepository $recipeRepository, int $id) : Response
    {
        $response = new Response();

        $recipe = $recipeRepository->find($id);

        $response->setContent($this->createJSON($recipe));
        $response->headers->set('Content-Type','application/json');

        return $response;
    }

    /**
     * @Route("/api/recipes",name="recipes.add",methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function add(ObjectManager $manager, Request $request, Security $security) : Response
    {
        $recipe = new Recipe();
        $category = $this->getDoctrine()->getRepository(Category::class);
        $response = new Response();

        $currentUser = $security->getUser();


        // TODO: refactor this bit using a for-loop and an array
        $userId = $currentUser->getId();
        $user = $manager->find(User::class,$userId);
        //dd($user);

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
            $recipe->setCategory($category->findOneBy(['name' => $categoryName]));
            $recipe->setColor($color);
            $recipe->setCookingTime($cookingTime);
            $recipe->setTitle($title);
            $recipe->setImage($image);
            $recipe->setPreparation($preparation);
            $recipe->setIngredient($ingredient);
            $recipe->setPreparationTime($preparationTime);
            $recipe->setDifficulty($difficulty);
            $recipe->setIntro($intro);
            $recipe->setUser($user);
            $recipe->setPrice($price);

//    dump($recipe); die();
            try
            {
                $manager->persist($recipe);
//                $manager->persist($category);

                $manager->flush();

                /*return $this->json([
                    'recipe' => $recipe
                ],200);*/
                $response->setContent($this->createJSON($recipe));
                $response->headers->set('Content-Type','application/json');

                return $response;
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

    /**
     * @Route("/api/recipes/{id}/delete",name="recipes.delete",methods={"DELETE"})
     * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request, int $id, Security $security) : Response
    {
        $recipe = $this->getDoctrine()->getRepository(Recipe::class)->find($id);
        $entityManger = $this->getDoctrine()->getManager();

        if ($recipe->getUser() === $security->getUser()) {
            $entityManger->remove($recipe);
            $entityManger->flush();

            return $this->json([
                'message' => 'Recipe Deleted'
            ]);

        } else {
            return $this->json([
                'error' => 'Access Denied'
            ]);
        }
    }

    /**
     * @Route("/api/recipes/{id}/update",name="recipes.update",methods={"PUT"})
     * @IsGranted("ROLE_USER")
     */
    public function update(Request $request, int $id, Security $security, ObjectManager $manager) : Response
    {
        $recipe = $this->getDoctrine()->getRepository(Recipe::class)->find($id);
        $userId = $security->getUser()->getId();
        $dateCreated = \DateTime::createFromFormat('Y-m-d',date("y-m-d"));

        $category = $this->getDoctrine()->getRepository(Category::class)->find($recipe->getId());

        $user = $manager->find(User::class,$userId);

        if (is_null($recipe)) {
            return $this->json([
                'error' => 'Product Not Found.'
            ]);
        }
        if ($recipe->getUser() === $security->getUser()) {

            $entityManager = $this->getDoctrine()->getManager();

            if (!$recipe) {
                return $this->json([
                    'error' => 'Product Not Found.'
                ],400);
            }

            //$recipe->setName('New product name!');
            $recipe->setTitle($request->get('title'));
            $recipe->setIntro($request->get('intro'));
            $recipe->setUser($user);
            $recipe->setCategory($category->setName($request->get('categoryName')));
            $recipe->setImage($request->get('image'));
            $recipe->setPreparation($request->get('preparation'));
            $recipe->setIngredient($request->get('ingredient'));
            $recipe->setColor($request->get('color'));
            $recipe->setDateCreated($dateCreated);
            $recipe->setCookingTime($request->get('cookingTime'));
            $recipe->setPreparationTime($request->get('preparationTime'));
            $recipe->setDifficulty($request->get('difficulty'));
            $recipe->setPrice($request->get('price'));

            //dd($recipe);

            $entityManager->flush();

            return $this->redirectToRoute('recipes.detail', [
                'id' => $recipe->getId()
            ]);

            /*return $this->json([
                'route' => 'update'
            ]);*/
        } else {
            return $this->json([
                'error' => 'Access Denied.'
            ]);
        }
    }

}