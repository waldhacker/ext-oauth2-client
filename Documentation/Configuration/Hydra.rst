.. include:: ../Includes.txt

.. _configuration_hydra:

============================
Configuration with Ory Hydra
============================

Adding the OAuth2 app in Hydra
==============================

Using the Hydra CLI create command
----------------------------------

Use the `hydra` cli command to create a client for the TYPO3 application:

.. code-block:: bash

   hydra clients create                               \
      --endpoint http://hydra-instance-url:4445       \
      --id your-typo3-client-name                     \
      --secret your-typo3-client-secret               \
      --grant-types authorization_code,refresh_token  \
      --response-types code,id_token                  \
      --scope openid,offline                          \
      --token-endpoint-auth-method client_secret_post \
      --callbacks "https://<your-TYPO3-installation>/typo3/oauth2/callback/handle?oauth2-provider=hydra&action=callback,https://<your-TYPO3-installation>/typo3/login?loginProvider=1616569531&oauth2-provider=hydra&login_status=login&commandLI=attempt,https://<your-TYPO3-installation>/_oauth2?oauth2-provider=hydra&tx_oauth2client%5Baction%5D=verify,https://<your-TYPO3-installation>/_oauth2?oauth2-provider=hydra&logintype=login"

Make sure to replace the following variables:
   -  `hydra-instance-url`: Point to your hydra installation
   -  `id`: Choose your Hydra client name for your TYPO3 installation (for example: your site identifier)
   -  `secret`: Set a client secret - this should follow general secret/password rules
   -  `callbacks`: Replace `<your-TYPO3-installation>` with your TYPO3 installation URL - if you have a multi-site setup, add all site urls with the 4 listed paths (so if you use 2 sites, you should have 8 callback URLs configured)

Using the Hydra import capabilities
-----------------------------------

If you prefer to handle your configuration in JSON files, here's an example hydra client json:

.. code-block:: json

   {
       "client_id": "your-hydra-client",
       "grant_types": [
           "authorization_code",
           "refresh_token"
       ],
       "redirect_uris": [
           "https://<your-TYPO3-installation>/typo3/oauth2/callback/handle?oauth2-provider=hydra\u0026action=callback",
           "https://<your-TYPO3-installation>/typo3/login?loginProvider=1616569531\u0026oauth2-provider=hydra\u0026login_status=login\u0026commandLI=attempt",
           "https://<your-TYPO3-installation>/<callback-slug>?oauth2-provider=hydra\u0026tx_oauth2client%5Baction%5D=verify",
           "https://<your-TYPO3-installation>/<callback-slug>?oauth2-provider=hydra\u0026logintype=login"
       ],
       "response_types": [
           "code",
           "id_token"
       ],
       "scope": "openid offline",
       "subject_type": "public",
       "token_endpoint_auth_method": "client_secret_post",
   }


Adding the OAuth2 Hydra client in TYPO3
=======================================

Add the following configuration to your `AdditionalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
       'providers' => [
           'keycloak' => [
               'label' => 'Hydra',
               'iconIdentifier' => 'oauth2-hydra',
               'description' => 'Login with Hydra',
               'scopes' => [
                   \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
               ],
               'options' => [
                   'clientId' => '<your-client-id>',
                   'clientSecret' => '<your-client-secret>',
                   'urlAuthorize' => 'https://<hydra-domain>/oauth2/auth',
                   'urlAccessToken' => 'https://<hydra-domain>/oauth2/token',
                   'urlResourceOwnerDetails' => 'https://<hydra-domain>/oauth2/userinfo',
                   'responseResourceOwnerId' => 'sub',
               ],
           ],
       ],
   ];

Registering the icon (optional)
===============================

If you want to use a custom icon, in your site package `Configuration/Icons.php` register the icon like this:

.. code-block:: php

   <?php
      return [
          'oauth2-hydra' => [
              'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
              'name' => 'cubes',
          ],
      ];

If you want to use the default icon instead, remove the `iconIdentifier` from the configuration.
