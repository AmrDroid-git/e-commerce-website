<?php
namespace App\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class AccessDeniedException extends CustomUserMessageAccountStatusException
{
public function __construct(string $message = "Accès refusé : cette zone est réservée aux administrateurs", array $messageData = [], int $code = 403, \Throwable $previous = null)
{
parent::__construct($message, $messageData, $code, $previous);
}
}