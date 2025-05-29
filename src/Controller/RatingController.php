<?php
namespace App\Controller;

use App\Entity\Product;
use App\Entity\Rating;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Comment;

#[Route('/rating')]
class RatingController extends AbstractController
{
    private EntityManagerInterface $em;
    private Security $security;
    private RatingRepository $ratingRepo;

    public function __construct(
        EntityManagerInterface $em,
        Security $security,
        RatingRepository $ratingRepo
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->ratingRepo = $ratingRepo;
    }

    #[Route('/upsert', name: 'rating_upsert', methods: ['POST'])]
    public function upsert(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $productId = $data['product_id'] ?? null;
        $value = $data['value'] ?? null;
        $commentContent = trim($data['comment'] ?? '');
        error_log('Comment content: ' . $commentContent);

        if ($productId === null || $value === null) {
            return $this->json(['error' => 'Both product_id and value are required'], 400);
        }
        if (!\is_int($value) || $value < 0 || $value > 5) {
            return $this->json(['error' => 'Rating value must be an integer between 0 and 5'], 400);
        }

        $product = $this->em->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        $existingRating = $this->ratingRepo->findOneByUserAndProduct($user, $product);

        if ($existingRating) {
            $existingRating->setValue($value);
            $rating = $existingRating;
        } else {
            $rating = new Rating();
            $rating->setUser($user);
            $rating->setProduct($product);
            $rating->setValue($value);
            $this->em->persist($rating);
        }

        if ($commentContent !== '') {
            $comment = new Comment();
            $comment->setUser($user);
            $comment->setProduct($product);
            $comment->setContent($commentContent);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($comment);
        }

        $this->em->flush();

        $response = [
            'status' => $existingRating ? 'updated' : 'created',
            'rating_id' => $rating->getId(),
            'value' => $rating->getValue(),
        ];

        // If a comment was created, render it and send back HTML
        if (isset($comment)) {
            $commentHtml = $this->renderView('comment/_single_comment.html.twig', [
                'comment' => $comment
            ]);
            $response['comment_html'] = $commentHtml;
        }

        return $this->json($response);
    }


    #[Route('/product/{id}', name: 'rating_list_by_product', methods: ['GET'])]
    public function listByProduct(Product $product): JsonResponse
    {
        $ratings = $this->ratingRepo->findBy(['product' => $product]);
        $payload = array_map(fn(Rating $rating) => [
            'user' => $rating->getUser()->getUsername(),
            'value' => $rating->getValue(),
        ], $ratings);

        return $this->json($payload);
    }

    #[Route('/user/{id}', name: 'rating_list_by_user', methods: ['GET'])]
    public function listByUser(\App\Entity\User $user): JsonResponse
    {
        $ratings = $this->ratingRepo->findBy(['user' => $user]);
        $payload = array_map(fn(Rating $rating) => [
            'product' => $rating->getProduct()->getName(),
            'value' => $rating->getValue(),
        ], $ratings);

        return $this->json($payload);
    }
}
