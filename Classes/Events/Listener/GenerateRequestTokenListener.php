<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Oauth2Client\Events\Listener;

use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Security\RequestToken;
use Waldhacker\Oauth2Client\Backend\LoginProvider\Oauth2LoginProvider;

class GenerateRequestTokenListener
{
    public function __construct(protected readonly Context $context)
    {
    }

    public function __invoke(BeforeRequestTokenProcessedEvent $event): void
    {
        // todo
        // wenn oauth cookie => get time from token from cookie
        // wenn request within 5 sec => generate and set token

        $requestToken = RequestToken::create('core/user-auth/' . strtolower($event->getUser()->loginType));
        $event->setRequestToken($requestToken);

        $request = $event->getRequest();

        $getParameters = $request->getQueryParams();
        $postParameters = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];

        $loginProvider = $getParameters['loginProvider'] ?? null;
        if ($loginProvider !== Oauth2LoginProvider::PROVIDER_ID) {
            return;
        }

        $code = (string)($getParameters['code'] ?? '');
        $state = (string)($getParameters['state'] ?? '');
        $providerIdFromPost = (string)($postParameters['oauth2-provider'] ?? '');
        $providerIdFromGet = (string)($getParameters['oauth2-provider'] ?? '');

        $action = !empty($providerIdFromPost) && empty($code) && empty($state)
            ? 'authorize'
            : (!empty($providerIdFromGet) && !empty($code) && !empty($state) ? 'verify' : 'invalid');

        if ($action !== 'verify') {
            return;
        }

        $requestToken = $event->getRequestToken();

        if (!($requestToken instanceof RequestToken)) {
            $event->setRequestToken(null);
            return;
        }

        // check time of request within 5 sec
        $now = $this->context->getAspect('date')->getDateTime();
        $interval = new \DateInterval(sprintf('PT%dS', 5));

        $moreThan5Seconds = $now > $requestToken->time->add($interval);
        if ($moreThan5Seconds) {
            $event->setRequestToken(null);
            return;
        }
    }
}
