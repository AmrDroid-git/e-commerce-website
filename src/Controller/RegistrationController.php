<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\AuthAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/registeration', name: 'app_registeration')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $user   = new User();
        $panier = new Panier();
        $form   = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        // collect validation errors as flash messages
        if ($form->isSubmitted() && ! $form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPanier($panier);
            $panier->setUser($user);
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            try {
                $entityManager->persist($user);
                $entityManager->persist($panier);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'There is already an account with this username or email.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // send confirmation email
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('tsourirakia88@gmail.com', 'Webify Team'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $request->getSession()->getFlashBag()->set('success', [
                'Registration successful! Please log in.'
            ]);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans($exception->getReason(), [], 'VerifyEmailBundle')
            );

            return $this->redirectToRoute('app_registeration');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_registeration');
    }
}
