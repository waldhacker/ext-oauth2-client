<?php

namespace Waldhacker\Oauth2Client\EventListener;

use TYPO3\CMS\FrontendLogin\Event\ModifyLoginFormViewEvent;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

class ModifyLoginFormViewListener
{
    private Oauth2ProviderManager $oauth2ProviderManager;

    public function __construct(Oauth2ProviderManager $oauth2ProviderManager)
    {
        $this->oauth2ProviderManager = $oauth2ProviderManager;
    }

    public function __invoke(ModifyLoginFormViewEvent $event)
    {
        $event->getView()->assign('providers', $this->oauth2ProviderManager->getConfiguredProviders());
    }
}
