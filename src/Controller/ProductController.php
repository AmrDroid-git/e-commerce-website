<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\ProductForm;
use App\Repository\CategoryRepository;
use App\Service\ProductDisplayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        ProductDisplayService $productDisplay
    ): Response {
        $searchTerm = trim((string) $request->query->get('search', ''));
        $categoryId = $request->query->getInt('category', 0);
        $sort = (string) $request->query->get('sort', 'name');

        $categories = $em->getRepository(Category::class)->findBy([], ['name' => 'ASC']);
        $categoryName = null;

        if ($categoryId > 0) {
            $category = $em->getRepository(Category::class)->find($categoryId);
            $categoryName = $category?->getName();
        }

        $products = $productDisplay->findVisibleProducts($searchTerm, $categoryName, $sort);

        return $this->render('product/index.html.twig', [
            'categories' => $categories,
            'productsWithRating' => $productDisplay->decorateProducts($products),
            'currentSearch' => $searchTerm,
            'currentCategory' => $categoryId,
            'currentSort' => $sort,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Product $product, ProductDisplayService $productDisplay): Response
    {
        if (!$product->isActive() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'comments' => $product->getComments(),
            'ratings' => $product->getRatings(),
            'average_rating' => $productDisplay->getAverageRating($product) ?? 0,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/admin/product/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
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
                $product->setImageUrl($this->uploadProductImage($imageFile, $slugger, $params));
            } else {
                $product->setImageUrl('');
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('product');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/admin/product/{id}/edit', name: 'admin_product_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ParameterBagInterface $params,
        CategoryRepository $categoryRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $oldImage = $product->getImageUrl();
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $product->setImageUrl($this->uploadProductImage($imageFile, $slugger, $params));
                $this->removeProductImage($oldImage, $params);
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/admin/product/{id}/delete', name: 'admin_product_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        $product->setIsActive(false);
        $em->flush();

        $this->addFlash('success', 'Product removed from the public catalog.');
        return $this->redirectToRoute('product');
    }

    private function uploadProductImage(mixed $imageFile, SluggerInterface $slugger, ParameterBagInterface $params): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename)->lower();
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $imageFile->guessExtension());

        try {
            $imageFile->move($params->get('product_images_directory'), $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Unable to upload product image.', 0, $e);
        }

        return $newFilename;
    }

    private function removeProductImage(?string $filename, ParameterBagInterface $params): void
    {
        if (!$filename) {
            return;
        }

        $path = rtrim((string) $params->get('product_images_directory'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $filesystem = new Filesystem();
        if ($filesystem->exists($path)) {
            $filesystem->remove($path);
        }
    }
}

# backdated-commit: 2025-08-31 00:00:00
