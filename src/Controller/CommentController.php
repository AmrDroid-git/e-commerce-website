<?php
// src/Controller/CommentController.php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Product;
use App\Form\CommentForm;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $em;
    private Security $security;
    private CommentRepository $commentRepo;

    public function __construct(
        EntityManagerInterface $em,
        Security $security,
        CommentRepository $commentRepo
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->commentRepo = $commentRepo;
    }

    #[Route('/new/{product}', name: 'comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Product $product): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to leave a comment.');
            return $this->redirectToRoute('app_login');
        }

        $comment = new Comment();
        $comment->setUser($user);
        $comment->setProduct($product);

        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($comment);
            $this->em->flush();

            $this->addFlash('success', 'Your comment has been posted.');
            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        return $this->render('comment/new.html.twig', [
            'form'    => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/edit/{id}', name: 'comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment): Response
    {
        $user = $this->security->getUser();
        if ($comment->getUser()->getId() !== $user?->getId()) {
            throw $this->createAccessDeniedException('You cannot edit this comment.');
        }

        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Comment updated successfully.');
            return $this->redirectToRoute('product_show', ['id' => $comment->getProduct()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'form'    => $form->createView(),
            'comment' => $comment,
        ]);
    }

    #[Route('/delete/{id}', name: 'comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment): Response
    {
        $user = $this->security->getUser();
        if ($comment->getUser()->getId() !== $user?->getId()) {
            throw $this->createAccessDeniedException('You cannot delete this comment.');
        }

        if (!$this->isCsrfTokenValid('delete-comment' . $comment->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('product_show', ['id' => $comment->getProduct()->getId()]);
        }

        $productId = $comment->getProduct()->getId();
        $this->em->remove($comment);
        $this->em->flush();

        $this->addFlash('success', 'Comment deleted.');
        return $this->redirectToRoute('product_show', ['id' => $productId]);
    }
}
