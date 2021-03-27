.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Prerequisites
=============

As the extension allows 3rd party login providers to authenticate users, the first thing you need is to change the cookie security setting for the backend from `sameSite` to `lax` (if you are using providers hosted on another domain, for example Github or Google).

File: :file:`AdditionalConfiguration.php` (or adjust :file:`Localconfiguration.php`):

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] = 'lax';

Access Rights
=============

To allow non-admin users to add OAuth2 Login Providers, please allow them to modify the table :sql:`tx_oauth2_client_configs`.

Authentication Providers
========================

The extension is based on the oauth2 client implementation from https://oauth2-client.thephpleague.com - by default, it comes with a
generic provider you can configure for your use case.

The configuration is done in the file :file:`AdditionalConfiguration.php` or directly in `LocalConfiguration.php` in the section :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']`.

.. note::

   If you are using composer, you can ease your configuration by requiring a pre-configured client package for the platform you want.

In the following section both the generic and some more specific examples are shown.

GenericProvider - Configuration
-------------------------------

The generic provider can be used for basically any platform that supports oauth2.

Gitlab minimal example::

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
       'providers' => [
           'gitlab' => [
               'label' => 'Gitlab',
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


$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client']['providers'][<identifier>]
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

Full example:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['oauth2_client'] = [
      'providers' => [
           'gitlab' => [
            'label' => 'Gitlab',
            'iconIdentifier' => 'oauth2-gitlab',
            'description' => 'Login with Gitlab',
            'implementationClassName' => \League\OAuth2\Client\Provider\GenericProvider::class,
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
        ],
      ]
   ];

Specific Providers (Examples)
-----------------------------

.. toctree::
   :maxdepth: 1

   Gitlab
   Github
   Google
   Keycloak
