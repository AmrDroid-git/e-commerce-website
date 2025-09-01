<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Product;
use App\Entity\Rating;
use App\Entity\User;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rating')]
class RatingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly RatingRepository $ratingRepo
    ) {
    }

    #[Route('/upsert', name: 'rating_upsert', methods: ['POST'])]
    public function upsert(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->isCsrfTokenValid('rating_upsert', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Invalid security token'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $productId = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);
        $value = filter_var($data['value'] ?? null, FILTER_VALIDATE_INT);
        $commentContent = trim((string) ($data['comment'] ?? ''));

        if (!$productId || $value === false || $value < 1 || $value > 5) {
            return $this->json(['error' => 'Rating value must be between 1 and 5'], 400);
        }

        $product = $this->em->getRepository(Product::class)->find($productId);
        if (!$product || !$product->isActive()) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $existingRating = $this->ratingRepo->findOneByUserAndProduct($user, $product);
        if ($existingRating) {
            $rating = $existingRating->setValue($value);
        } else {
            $rating = (new Rating())
                ->setUser($user)
                ->setProduct($product)
                ->setValue($value);
            $this->em->persist($rating);
        }

        $comment = null;
        if ($commentContent !== '') {
            $comment = (new Comment())
                ->setUser($user)
                ->setProduct($product)
                ->setContent(mb_substr($commentContent, 0, 255));
            $this->em->persist($comment);
        }

        $this->em->flush();

        $response = [
            'status' => $existingRating ? 'updated' : 'created',
            'rating_id' => $rating->getId(),
            'value' => $rating->getValue(),
        ];

        if ($comment) {
            $response['comment_html'] = $this->renderView('comment/_single_comment.html.twig', [
                'comment' => $comment,
            ]);
        }

        return $this->json($response);
    }

    #[Route('/product/{id}', name: 'rating_list_by_product', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function listByProduct(Product $product): JsonResponse
    {
        $ratings = $this->ratingRepo->findBy(['product' => $product]);

        return $this->json(array_map(fn (Rating $rating) => [
            'user' => $rating->getUser()?->getUsername(),
            'value' => $rating->getValue(),
        ], $ratings));
    }

    #[Route('/user/{id}', name: 'rating_list_by_user', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function listByUser(User $user): JsonResponse
    {
        $ratings = $this->ratingRepo->findBy(['user' => $user]);

        return $this->json(array_map(fn (Rating $rating) => [
            'product' => $rating->getProduct()?->getName(),
            'value' => $rating->getValue(),
        ], $ratings));
    }
}

# backdated-commit: 2025-09-01 00:00:00
