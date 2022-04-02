.. include:: ../Includes.txt

.. _configuration_google:

=========================
Configuration with Google
=========================

.. note::

   This example contains the callback URLs which are required for TYPO3 v11.

Adding the OAuth2 app in Google
===============================

-  Login to Google Developers Console (https://console.developers.google.com)
-  (Create a project if you have not done so already)
-  Click "Credentials" > "Create Credentials"

   -  Choose "Application type" > "Web Application"
   -  Enter a name for your application
   -  Add the redirect URIs (for backend logins):

      -  `https://<your-TYPO3-installation>/typo3/login?loginProvider=1616569531&oauth2-provider=google&login_status=login&commandLI=attempt`
      -  `https://<your-TYPO3-installation>/typo3/oauth2/callback/handle?oauth2-provider=google&action=callback`

   -  Add the redirect URIs (for frontend):

      -  `https://<your-TYPO3-installation>/<callback-slug>?oauth2-provider=google&tx_oauth2client%5Baction%5D=verify`
      -  `https://<your-TYPO3-installation>/<callback-slug>?oauth2-provider=google&logintype=login`

-  Save the application
-  Copy the client secret and client id


.. figure:: ../Images/configuration_Google.png
   :class: with-shadow float-left
   :alt: TYPO3 OAuth2 Google App Configuration

Adding the OAuth2 Google app in TYPO3
=====================================

.. warning::

   Please use composer to install the Github provider: `composer require "league/oauth2-google:^4.0"`.
   If you did not install the specific provider, you can still use the `GenericProvider` - however, you
   will need to add the URL configuration yourself.

Add the following configuration to your `AdditionalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
       'providers' => [
           'google' => [
               'label' => 'Google',
               'iconIdentifier' => 'oauth2-google',
               'description' => 'Login with your Google user account.',
               'implementationClassName' => \League\OAuth2\Client\Provider\Google::class,
               'scopes' => [
                   \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
               ],
               'options' => [
                   'clientId' => '<client-id>',
                   'clientSecret' => '<client-secret>',
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
          'oauth2-google' => [
              'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
              'name' => 'google',
          ],
      ];

If you want to use the default icon instead, remove the `iconIdentifier` from the configuration.
