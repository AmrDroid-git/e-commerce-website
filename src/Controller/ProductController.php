<?php

namespace App\Controller;
use App\Repository\CommentRepository;
use Symfony\Component\Filesystem\Filesystem;
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
    public function index(
        ProductRepository $productsRepo,
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $user       = $security->getUser();
        $searchTerm = trim((string)$request->query->get('search', ''));
        $categoryId = $request->query->getInt('category', 0);

        $categories = $em->getRepository(Category::class)->findAll();
        $qb = $em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);


        if ($searchTerm !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($searchTerm) . '%');
        }

        if ($categoryId > 0) {
            // Fetch the Category entity so we can get its name
            $category = $em->getRepository(Category::class)->find($categoryId);
            if ($category) {
                $qb->andWhere('p.category = :catName')
                    ->setParameter('catName', $category->getName());
            }
        }

        $qb->orderBy('p.name', 'ASC');

        $filteredProducts = $qb->getQuery()->getResult();

        $productsWithRating = [];
        foreach ($filteredProducts as $product) {
            $ratings       = $product->getRatings();
            $averageRating = null;
            $countRatings  = count($ratings);

            if ($countRatings > 0) {
                $sum           = array_sum(
                    array_map(fn($r) => $r->getValue(), $ratings->toArray())
                );
                $averageRating = round($sum / $countRatings, 1);
            }

            $productsWithRating[] = [
                'product'     => $product,
                'rating'      => $averageRating,
                'ratingCount' => $countRatings,
            ];
        }

        return $this->render('product/index.html.twig', [
            'user'               => $user,
            'categories'         => $categories,
            'productsWithRating' => $productsWithRating,
            'currentSearch'      => $searchTerm,
            'currentCategory'    => $categoryId,
            'ratingCount' => count($ratings),
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
            // Gestion de l'upload d'image
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
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image');
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès!');
            return $this->redirectToRoute('product');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAll()
        ]);
    }
    #[Route('/admin/product/{id}/delete', name: 'admin_product_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $product->setIsActive(false);
        $entityManager->flush();

        return $this->redirectToRoute('product');
    }


    #[Route('/admin/product/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, CommentRepository $commentRepository,CategoryRepository $categoryRepository): Response
    {
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

//        $comments = $commentRepository->findBy(['product' => $product]);
//
//        $ratings = array_map(fn($c) => $c->getRating(), $comments);
//        $averageRating = 0;
//        if (count($ratings) > 0) {
//            $averageRating = array_sum($ratings) / count($ratings);
//        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');

            return $this->redirectToRoute('product', ['id' => $product->getId()]);
        }
        $categories = $categoryRepository->findAll();

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'categories' => $categories,
        ]);

    }}


