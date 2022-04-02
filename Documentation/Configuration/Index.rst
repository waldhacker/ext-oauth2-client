.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Prerequisites
=============

As the extension allows 3rd party login providers to authenticate users, the first thing you need is to change the cookie security setting for the backend from `sameSite` to `lax` (if you are using providers hosted on another domain, for example Github or Google).

File: :file:`AdditionalConfiguration.php` (or adjust :file:`LocalConfiguration.php`):

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] = 'lax';
   $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieSameSite'] = 'lax';

Access Rights in Backend Context
================================

To allow non-admin users to register OAuth2 providers, please allow them to access the module `User Settings (user_setup)`.

To allow non-admin users to revoke OAuth2 provider registrations for frontend users, please allow them to modify the table :sql:`tx_oauth2_feuser_provider_configuration`.

Authentication Providers
========================

The extension is based on the OAuth2 client implementation from https://oauth2-client.thephpleague.com - by default, it comes with a
generic provider you can configure for your use case.

The configuration is done in the file :file:`AdditionalConfiguration.php` or directly in `LocalConfiguration.php` in the section :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']`.

.. note::

   If you are using composer, you can ease your configuration by requiring a pre-configured client package for the platform you want.

In the following section both the generic and some more specific examples are shown.

GenericProvider - Configuration
-------------------------------

The generic provider can be used for basically any platform that supports OAuth2.

Gitlab minimal example::

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
       'providers' => [
           'gitlab' => [
               'label' => 'Gitlab',
               'scopes' => [
                  \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                  \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
               ],
               'options' => [
                   'clientId' => '<your-gitlab-client-id>',
                   'clientSecret' => '<your-gitlab-client-secret',
                   'urlAuthorize' => 'https://<your-gitlab-installation.dev>/oauth/authorize',
                   'urlAccessToken' => 'https://<your-gitlab-installation.dev>/oauth/token',
                   'urlResourceOwnerDetails' => 'https://<your-gitlab-installation.dev>/api/v4/user',
                   'scopes' => ['openid', 'read_user'],
                   'scopeSeparator' => ' '
               ],
           ],
       ],
   ];


$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<provider-identifier>]
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. rst-class:: dl-parameters

label
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:` ''
   :sep:`|`

   The label to show both in the login screen as well as in the configuration.

description
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:` ''
   :sep:`|`

   A description for the provider. The description is shown to the user when configuring the provider.

iconIdentifier
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:` 'actions-key'
   :sep:`|`

   A registered icon identifier. If you want to use custom icons, make sure to register them first.

implementationClassName
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:` 'actions-key'
   :sep:`|`

   The provider class name - the default is `\League\OAuth2\Client\Provider\GenericProvider::class` - can be
   replaced for more specific providers, for example `\League\OAuth2\Client\Provider\Github::class` if the github
   provider has been installed.

scopes
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` array
   :sep:`|` :aspect:`Default:` both backend and frontend are enabled
   :sep:`|`

   The scopes where this provider can be used. Can be an array with either `\Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND` for backend only or `\Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND` for frontend only or both.

   Example::

      'scopes' => [
         \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
         \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
      ],


options.[...]
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` array
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The provider options - are given directly to the provider instance as constructor arguments. Please check your
   provider documentation for the concrete values (and see the examples section in this manual).

options.clientId
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The OAuth2 client id.

options.clientSecret
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The OAuth2 client secret.

options.urlAuthorize
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The OAuth2 authorization URL.

options.urlAccessToken
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The OAuth2 access token API URL.

options.urlResourceOwnerDetails
   :sep:`|` :aspect:`Condition:` required
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The OAuth2 REST API URL for getting the resource owner information (for example the user profile REST API route).

options.scopes
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` array of strings
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   The scopes to request for the OAuth provider. May be required depending on the concrete provider (it is required in Gitlab for example).

options.scopeSeparator
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:` `,`
   :sep:`|`

   The scope separator used to separate the different required scopes in the URL.

options.proxy | options.verify
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` string
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   You can use a proxy to debug HTTP calls made to a provider. See https://oauth2-client.thephpleague.com/usage/#using-a-proxy

collaborators
   :sep:`|` :aspect:`Condition:` optional
   :sep:`|` :aspect:`Type:` array
   :sep:`|` :aspect:`Default:`
   :sep:`|`

   If you have advanced requirements, within this property you can change the default `collaborators` from the default implementation. See https://github.com/thephpleague/oauth2-client/blob/8c7498c14959b98d4143a8ef91e895f353381628/src/Provider/AbstractProvider.php#L107

Full example:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
      'providers' => [
           'gitlab' => [
            'label' => 'Gitlab',
            'iconIdentifier' => 'oauth2-gitlab',
            'description' => 'Login with Gitlab',
            'implementationClassName' => \League\OAuth2\Client\Provider\GenericProvider::class,
            'scopes' => [
                \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_BACKEND,
                \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager::SCOPE_FRONTEND,
            ],
            'options' => [
                'clientId' => '<client-id>',
                'clientSecret' => '<client-secret>',
                'urlAuthorize' => 'https://<your-gitlab-url>/oauth/authorize',
                'urlAccessToken' => 'https://<your-gitlab-url>/oauth/token',
                'urlResourceOwnerDetails' => 'https://<your-gitlab-url>/api/v4/user',
                'scopes' => ['openid', 'read_user'],
                'scopeSeparator' => ' ',
                'proxy' => '127.0.0.1:8080',
                'verify' => false,
            ],
            'collaborators' => [
                'httpClient' => \My\Custom\HttpClient::class,
            ],
        ],
      ]
   ];

Frontend user storage
---------------------

You must configure a frontend user storage folder within the site / site language settings.
Only if frontend users are in this storage folder they can login with an OAuth2 provider or register an OAUth2 provider.
Set :guilabel:`Behaviour` -> :guilabel:`Contains Plugin` to :guilabel:`Website Users` to make a frontend user storage folder selectable in the site / site language settings.

   .. figure:: ../Images/Frontend/administrateUserStorageModule.png
      :class: with-shadow
      :alt: TYPO3 user storage folder configuration
      :width: 600px

      Set user storage folder configuration

Now you can set this frontend user storage folder within the site / site language settings

   .. figure:: ../Images/Frontend/administrateUserStorage.png
      :class: with-shadow
      :alt: TYPO3 site language module with configured frontend user storage folder
      :width: 600px

      Manage frontend user storage folder

.. note::

   Currently no recursive lookup within this folder is implemented

Callback configuration (within the remote OAuth2 provider settings)
-------------------------------------------------------------------

The extension provides defined callback URLs to which the OAuth2 providers redirect in case of authorization requests.
In the backend context these are fixed and in the frontend context they can be configured.
For each context (frontend / backend) 2 callback URL's are needed which have to be configured in the OAuth2 providers.

.. note::

   The OAuth2 providers behave differently in the way they validate the callback URLs.
   Some compare only the URL without query parameters and the query paramaters are not relevant and for some you have to configure the complete url including the query parameters.

   In addition, the callback urls to be used for backend logins differ between TYPO3 v10 and TYPO3 v11!

Backend
~~~~~~~

To make OAuth2 backend login authorizations work, the following callback URL must be configured in the OAuth2 provider

TYPO3 v11: :html:`https://your-typo3-site.example.com/typo3/login?loginProvider=1616569531&oauth2-provider=<provider-identifier>&login_status=login&commandLI=attempt`

TYPO3 v10: :html:`https://your-typo3-site.example.com/typo3/index.php?route=%2Flogin&loginProvider=1616569531&oauth2-provider=<provider-identifier>&login_status=login&commandLI=attempt`

.. note::

   Replace `your-typo3-site.example.com` with the domain of your project and `<provider-identifier>` with your OAuth2 provider configuration identifier (:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<provider-identifier>]`).

For the registration of OAuth2 providers for backend users the following callback URL must be configured in the OAuth2 provider

TYPO3 v11: :html:`https://your-typo3-site.example.com/typo3/oauth2/callback/handle?oauth2-provider=<provider-identifier>&action=callback`

TYPO3 v10: :html:`https://your-typo3-site.example.com/typo3/index.php?route=%2Foauth2%2Fcallback%2Fhandle&oauth2-provider=<provider-identifier>&action=callback`

.. note::

   Replace `your-typo3-site.example.com` with the domain of your project and `<provider-identifier>` with your OAuth2 provider configuration identifier (:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<provider-identifier>]`).

Frontend
~~~~~~~~

In the fontend context the callback-slug can be configured within the site / site language settings.
If this setting is empty, the default callback-slug `_oauth2` is used.
Make sure you use a callback-slug that does not match any slug of a TYPO3 page in your project.

   .. figure:: ../Images/Frontend/administrateCallbackSlug.png
      :class: with-shadow
      :alt: TYPO3 site language module with configured callback-slug
      :width: 600px

      Manage frontend OAuth2 callback-slug

To make OAuth2 frontend login authorizations work, the following callback URL must be configured in the OAuth2 provider

:html:`https://your-typo3-site.example.com/<callback-slug>?oauth2-provider=<provider-identifier>&logintype=login`

.. note::

   Replace `your-typo3-site.example.com` with the domain of your project and `<provider-identifier>` with your OAuth2 provider configuration identifier (:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<provider-identifier>]`).
   Replace `<callback-slug>` with the configured callback-slug within your site / site language settings (or with `_oauth2` if you have not configured anything).

For the registration of OAuth2 providers for frontend users the following callback URL must be configured in the OAuth2 provider

:html:`https://your-typo3-site.example.com/<callback-slug>?oauth2-provider=<provider-identifier>&tx_oauth2client%5Baction%5D=verify`

.. note::

   Replace `your-typo3-site.example.com` with the domain of your project and `<provider-identifier>` with your OAuth2 provider configuration identifier (:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<provider-identifier>]`).
   Replace `<callback-slug>` with the configured callback-slug within your site / site language settings (or with `_oauth2` if you have not configured anything).

Frontend redirect behavior
--------------------------

By default, after an OAuth2 login or OAuth2 provider registration, the extension redirects to the page where the OAuth2 login or OAuth2 provider registration took place.
But the page to which you are redirected after a login or registration can be specially defined if required.

Be aware that you have to create the links for an OAuth2 login or OAuth2 provider registration manually.
If you append :html:`after-oauth2-redirect-uri=https://your-typo3-site.example.com/sales/` to the links, the extension will redirect to the URL `https://your-typo3-site.example.com/sales/` after an OAuth2 login or OAuth2 provider registration.

.. note::
   The custom redirect url must not be relative to the current page and must point to the same host on which the project is accessible.
   You cannot redirect to external pages or to pages of other page trees (sites).

An example of an Oauth2 login URL would look like this: :html:`https://your-typo3-site.example.com/some-site?oauth2-provider=<provider-identifier>&logintype=login&after-oauth2-redirect-uri=https://your-typo3-site.example.com/sales/`.

.. note::
   You do not need to include the :html:`after-oauth2-redirect-uri=https://your-typo3-site.example.com/sales/` part in the callback URL configuration of your OAuth2 provider.

However, this feature is disabled by default for security reasons.
If you want to use it you have to turn on the feature flag :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['oauth2.frontend.login.afterOauth2RedirectUriFromQuery'] = true`; within the file :file:`AdditionalConfiguration.php` (or :file:`LocalConfiguration.php`):

Specific Providers (Examples)
-----------------------------

.. toctree::
   :maxdepth: 1

   Gitlab
   Github
   Google
   Keycloak
   Hydra
