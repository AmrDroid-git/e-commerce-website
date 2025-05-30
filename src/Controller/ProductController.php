<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductForm;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product')]
    public function index(ProductRepository $products, Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user       = $security->getUser();
        $searchTerm = trim((string) $request->query->get('search', ''));    
        $categoryId = $request->query->getInt('category', 0);

        $allProducts = $products->findAll();

        $productsWithRating = [];

        foreach ($allProducts as $product) {
            $ratings = $product->getRatings();
            $averageRating = null;

            if (count($ratings) > 0) {
                $sum = array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray()));
                $averageRating = round($sum / count($ratings), 1);
            }

            $productsWithRating[] = [
                'product' => $product,
                'rating' => $averageRating,
            ];
        }

        $categories = $entityManager->getRepository(Category::class)->findAll();    

        $qb = $entityManager->getRepository(Product::class)->createQueryBuilder('p'); // [CHANGED]

        if ($searchTerm !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($searchTerm) . '%');
        }

        if ($categoryId > 0) {                                                     
            $category = $entityManager->getRepository(Category::class)->find($categoryId); 
            if ($category) {                                                       
                $qb->andWhere('p.category = :catName')                             
                ->setParameter('catName', $category->getName());                
            }                                                                       
        }                                                                           

        $qb->orderBy('p.name', 'ASC');                                              

        $products = $qb->getQuery()->getResult();

        return $this->render('product/index.html.twig', [
            'products'       => $products,
            'categories'     => $categories,       
            'user'           => $user,
            'currentSearch'   => $searchTerm,       
            'currentCategory' => $categoryId,
            'productsWithRating' => $productsWithRating,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em, Security $security): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $user = $security->getUser();

        $ratings = $product->getRatings();
        $averageRating = 0;
        if (count($ratings) > 0) {
            $sum           = array_sum(array_map(fn($r) => $r->getValue(), $ratings->toArray()));
            $averageRating = round($sum / count($ratings), 2);
        }

        return $this->render('product/show.html.twig', [
            'product'        => $product,
            'comments'       => $product->getComments(),
            'ratings'        => $ratings,
            'average_rating' => $averageRating,
            'user'           => $user,
        ]);
    }

    #[Route('/admin/product/new', name: 'admin_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ParameterBagInterface $params,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $params->get('product_images_directory'),
                        $newFilename
                    );
                    $product->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload image: '.$e->getMessage());
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('admin_product_list');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAll()
        ]);
    }
}
