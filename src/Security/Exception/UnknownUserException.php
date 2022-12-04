<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UnknownUserException extends CustomUserMessageAuthenticationException
{
    public function __construct(
        string $message = "Cet utilisateur n'est pas connu.",
        array $messageData = [],
        int $code = 0,
        \Throwable $previous = null
    )
    {
        parent::__construct($message, $messageData, $code, $previous);
    }
}