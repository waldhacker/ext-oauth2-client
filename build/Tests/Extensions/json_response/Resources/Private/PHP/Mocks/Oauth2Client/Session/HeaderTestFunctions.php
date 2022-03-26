<?php

declare(strict_types=1);

namespace Waldhacker\Oauth2Client\Session;

use TYPO3\JsonResponse\GlobalStates;

function header($header, $replace = true, $statusCode = null)
{
    if (strpos($header, 'HTTP') === false) {
        [$key, $value] = explode(':', $header, 2);
        GlobalStates::addHeader($key, $value, $replace);
    }

    is_int($statusCode) ? \header($header, $replace, $statusCode) : \header($header, $replace);
}
