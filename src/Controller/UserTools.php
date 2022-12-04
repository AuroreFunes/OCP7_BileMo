<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

trait UserTools
{
    // returns the token from the response, or null if it was not given
    public static function giveToken(Request $request): ?string
    {
        return (empty($request->headers->all()['authorization'][0])) ? 
                    null : $request->headers->all()['authorization'][0];
    }
}