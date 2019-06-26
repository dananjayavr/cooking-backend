<?php


namespace App\Controller;


use App\Entity\Category;
use App\Repository\CategoryRepository;
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

class CategoriesController extends AbstractController
{

    protected function createJSON($object)
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        return $serializer->serialize($object, 'json', [
            'circular_reference_handler' => function ($obj) {
                return $obj->getId();
            },
            'ignored_attributes' => ['recipes']
        ]);
    }

    /**
     * @Route("/api/categories",name="categories.index")
     */
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $response = new Response();

        $categories = $categoryRepository->findAll();

        $response->setContent($this->createJSON($categories));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/categories/{id}",name="categories.detail", methods={"GET"})
     */
    public function detail(Request $request, CategoryRepository $categoryRepository, int $id): Response
    {
        $response = new Response();

        $category = $categoryRepository->find($id);

        $response->setContent($this->createJSON($category));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/categories/add",name="categories.add",methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function add(Request $request, ObjectManager $manager): Response
    {
        $category = new Category();
        $response = new Response();

        $categoryName = $request->get('categoryName');

        // TODO: add more server side data verification here
        $errors = [];
        if (empty($categoryName)) {
            $errors[] = "name cannot be empty.";
        }


        if (!$errors) {

            $category->setName($categoryName);

//    dump($category); die();

            try {
                $manager->persist($category);
//                $manager->persist($category);

                $manager->flush();

                /*return $this->json([
                    'recipe' => $recipe
                ],200);*/
                $response->setContent($this->createJSON($category));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            } /*catch (UniqueConstraintViolationException $exception)
            {
                $errors[] = "The email provided already has an account.";
            }*/
            catch (\Exception $exception) {
                dd($exception);
                $errors[] = "Unable to save new recipe at this time.";
            }

        }

        return $this->json([
            'errors' => $errors
        ], 400);
    }

    /**
     * @Route("/api/categories/{id}/delete",name="category.delete",methods={"DELETE"})
     * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request, int $id, CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->find($id);
        $entityManger = $this->getDoctrine()->getManager();

        if ($category) {
            $entityManger->remove($category);
            $entityManger->flush();

            return $this->json([
                'message' => 'Category Deleted'
            ]);

        } else {
            return $this->json([
                'error' => 'Problem'
            ]);
        }
    }

    /**
     * @Route("/api/categories/{id}/update",name="categories.update",methods={"PUT"})
     * @IsGranted("ROLE_USER")
     */
    public function update(Request $request, int $id, ObjectManager $manager): Response
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);

        if (is_null($category)) {
            return $this->json([
                'error' => 'Category Not Found.'
            ]);
        } else {
            $entityManager = $this->getDoctrine()->getManager();

            if (!$category) {
                return $this->json([
                    'error' => 'Category Not Found.'
                ], 400);
            }
            //$category->setName('New product name!');
            $category->setName($request->get('name'));
            //dd($category);

            $entityManager->flush();


            return $this->json([
                'message' => 'Category Updated.'
            ]);
        }

    }


}