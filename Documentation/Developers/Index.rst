.. include:: ../Includes.txt

.. _developer_information:

=====================
Developer Information
=====================

Basic Authentication Flows
==========================

Login Flow
----------

.. uml::

   skinparam backgroundColor #EEE
   skinparam handwritten false
   skinparam sequence {
       ArrowColor DarkGreen
       ArrowFontSize 18
       ParticipantBorderColor ForestGreen
       ParticipantFontSize 20
       ParticipantBackgroundColor TECHNOLOGY
   }

   autonumber

   "TYPO3 Login" -> "OAuth2 App": Authentication Request
   "OAuth2 App" -> "TYPO3 Login": Authentication Response: Callback URL
   "TYPO3 Login" -> "TYPO3 Login": Validate Session State
   "TYPO3 Login" -> "OAuth2 App": Fetch Access Token
   "OAuth2 App" -> "TYPO3 Login": Respond with AccessToken and ResourceOwner
   "TYPO3 Login" -> "TYPO3 Login": Match by ResourceOwner ID & Provider ID
   "TYPO3 Login" -> "TYPO3 Login": (optional) Evaluate MFA
   "TYPO3 Login" -> "TYPO3 Backend": Return found user and login

Registering new Provider for User
---------------------------------

.. uml::

   skinparam backgroundColor #EEE
   skinparam handwritten false
   skinparam sequence {
       ArrowColor DarkGreen
       ArrowFontSize 18
       ParticipantBorderColor ForestGreen
       ParticipantFontSize 20
       ParticipantBackgroundColor TECHNOLOGY
       NoteBackgroundColor ForestGreen
       NoteBorderColor ForestGreen
       NoteShadowing false
   }

   autonumber

   "TYPO3 Backend" -> "OAuth2 Popup": Open Popup for Authentication
   "OAuth2 Popup" -> "OAuth2 Provider": Redirect for Application Registration
   note left of "OAuth2 Provider"
       User enters their provider credentials
       and grants access.
   end note
   "OAuth2 Provider" -> "OAuth2 Popup": Callback with code & state
   "OAuth2 Popup" -> "TYPO3 Backend": Post message with code & state for evaluation
   "TYPO3 Backend" -> "TYPO3 Backend": Fetch access token and resource owner
   "TYPO3 Backend" -> "TYPO3 Backend": Store provider id and user id in database
   @enduml

Creating Users
==============

This extension does not provide the possibility to create users on the fly itself.
Its purpose is to provide OAuth2 authentication only. To allow users to register
directly via OAuth, the extension comes with a PSR-14 event that can be used to
create the users.

As user creation and their respective access rights is most likely specific to
your custom domain and provider, you should implement this individually. Here is
an overview of how you can achieve that:

Create the class
----------------

Create a class that will listen to the event and create a user:

.. code-block:: php

   class UserCreationListener {
      public function __invoke(UserLookupEvent $event): ?array {
         if ($event->getProviderId !== 'github') {
            // make sure you only react to "your" provider
            return null;
         }

         // get the current user record from the event - in case another listener
         // already provided data or the user existed in TYPO3
         // if it is null, the user does not exist yet.
         $userRecord = $event->getUserRecord() ?? [];

         // fetch the properties you want from the resource owner
         if ($event->getResourceOwner() instanceof GithubResourceOwner) {
            $resourceOwner = $event->getResourceOwner();

            // IMPORTANT: implement a check if the resource owner is allowed to access
            // this TYPO3 - for example by Github Organization. Otherwise _all_
            // Github users will be created and can log in.
            if (!$this->yourServiceClass->checkStuff($event)) {
               return null;
            }

            // depending on the specific resource owner, there are different sub-properties available
            $userRecord['email'] = $resourceOwner['email'];
            $userRecord['username'] = $resourceOwner['username'];
            // ...
         }

         // persist or update the $userRecord row in the be_users table
         // and enrich $userRecord with row data (TYPO3 record row with uid, TCA
         // control fields etc)
         // make sure to persist the oauth2 connection, too
         // @see \Waldhacker\Oauth2Client\Repository\BackendUserRepository::persistIdentityForUser
         $userRecord = $this->yourBackendRepository->addOrUpdate($userRecord);

         // set the user record for use in further authentication handling
         $event->setUserRecord($userRecord);
      }
   }


Register Event Listener
-----------------------

In your :file:`Configuration/Services.yaml` add a listener for the event:

.. code-block:: yaml

   services:
     MyCompany\MyPackage\EventListener\UserCreationListener:
       tags:
         - name: event.listener
           identifier: 'myListener'
           event: Waldhacker\Oauth2Client\Events\UserLookupEvent


.. note::

   We plan on developing further extensions for generic user creation in the
   future as add-ons to this one, however, user creation will not become a
   part of this extension.

API Methods
-----------

To make it easier for you to work with your chosen provider, you can use
the `Oauth2ProviderManager` to create an instance of your provider and use that
to fetch access tokens or query the API.

.. code-block:: php

   use \Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

   class YourServiceClass {

      private AbstractProvider $provider;

      public function __construct(Oauth2ProviderManager $manager) {
         $this->provider = $manager->createProvider('github', $yourCallbackUrl);
      }

      public function checkStuff(UserLookupEvent $event): bool {
         // you can get code and state properties from the event
         $state = $event->getState();
         $code = $event->getCode();
         if (!isset($_SESSION['oauth2-state']) || $_SESSION['oauth2-state'] !== $state) {
            return null;
         }
         // ...
         $accessToken = $this->provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code,
                ]
            );
         if ($accessToken instanceof AccessToken) {
            $request = $this->getAuthenticatedRequest(AbstractProvider::METHOD_GET, $url, $token);
            $response = $this->getParsedResponse($request);
         }
         // ...
         return false;
      }
   }


Other Use Cases
===============

The event shown above is only triggered once a resource owner (user) has been
successfully authenticated against the external service. Besides user creation,
the event could for example additionally be used for updating user details on login
to keep data in sync.
