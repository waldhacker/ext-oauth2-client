.. include:: ../Includes.txt

.. _configuration_gitlab:

=======================================
Configuration with Gitlab (self-hosted)
=======================================

Adding the OAuth2 App in Gitlab
===============================

-  Login to your Gitlab instance
-  Go to "User Settings" > "Applications"
-  Add a new application

   -  Add the redirect URIs: `https://<your-TYPO3-installation>/typo3/login` and `https://<your-TYPO3-installation>/typo3/oauth2/callback/handle`
   - Set the application to "confidential"
   - Set the scopes "openid" and "read_user"
- Save the application
- Copy the client secret and client id

.. figure:: ../Images/configuration_GitlabOauth2App.png
   :class: with-shadow float-left
   :alt: TYPO3 Oauth2 Gitlab App Configuration

.. figure:: ../Images/configuration_GitlabOauth2AppOverview.png
   :class: with-shadow float-left
   :alt: TYPO3 Oauth2 Gitlab App Overview


Adding the OAuth2 Gitlab App in TYPO3
=====================================

Add the following configuration to your `AdditionalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
       'providers' => [
           'gitlab' => [
               'label' => 'Gitlab',
               'iconIdentifier' => 'oauth2-gitlab',
               'description' => 'Login with Gitlab',
               'implementationClassName' => \League\OAuth2\Client\Provider\GenericProvider::class,
               'options' => [
                   'clientId' => '<your-client-id-from-gitlab>',
                   'clientSecret' => '<your-client-secret-from-gitlab>',
                   'urlAuthorize' => 'https://<url-to-your-gitlab>/oauth/authorize',
                   'urlAccessToken' => 'https://<url-to-your-gitlab>/oauth/token',
                   'urlResourceOwnerDetails' => 'https://<url-to-your-gitlab>/api/v4/user',
                   'scopes' => ['openid', 'read_user'],
                   'scopeSeparator' => ' '
               ],
           ],
       ],
   ];

Registering the icon (optional)
===============================

If you want to use the Gitlab icon, in your site package `ext_localconf.php` register the icon like this:

.. code-block:: php

   $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
       \TYPO3\CMS\Core\Imaging\IconRegistry::class
   );

   $iconRegistry->registerIcon(
       'oauth2-gitlab',
       \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
       ['name' => 'gitlab']
   );

If you want to use the default icon instead, remove the `iconIdentifier` from the configuration.
