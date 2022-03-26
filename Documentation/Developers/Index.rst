.. include:: ../Includes.txt

.. _developer_information:

=====================
Developer information
=====================

Basic backend authentication flows
==================================

Login flow
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

   "TYPO3 Backend" -> "BackendAuthenticationService": Request OAuth2 login
   "BackendAuthenticationService" -> "BackendAuthenticationService": Generate / set state (session)
   "BackendAuthenticationService" -> "OAuth2 Provider": Authentication request (redirect)
   note left of "OAuth2 Provider"
       User enters their provider credentials
       and grants access.
   end note
   "OAuth2 Provider" -> "BackendAuthenticationService": Redirect to callback-url and provide access code & state
   "BackendAuthenticationService" -> "BackendAuthenticationService": Validate state (session)
   "BackendAuthenticationService" -> "OAuth2 Provider": Fetch AccessToken by access code
   "OAuth2 Provider" -> "BackendAuthenticationService": Provide AccessToken
   "BackendAuthenticationService" -> "OAuth2 Provider": Fetch ResourceOwner by AccessToken
   "OAuth2 Provider" -> "BackendAuthenticationService": Provide ResourceOwner
   "BackendAuthenticationService" -> "BackendAuthenticationService": Match by ResourceOwner ID & Provider ID
   "BackendAuthenticationService" -> "BackendAuthenticationService": Fire BackendUserLookupEvent
   "BackendAuthenticationService" -> "TYPO3 Login": Return found user
   "TYPO3 Login" -> "TYPO3 Login": (optional) Evaluate MFA
   "TYPO3 Login" -> "TYPO3 Backend": Redirect to the backend

   @enduml

Registering new OAuth2 provider for backend user
------------------------------------------------

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

   "TYPO3 Backend" -> "AuthorizeController (OAuth2 Popup)": User setup module opens a popup
   "AuthorizeController (OAuth2 Popup)" -> "AuthorizeController (OAuth2 Popup)": Generate / set state (session)
   "AuthorizeController (OAuth2 Popup)" -> "OAuth2 Provider": Request for application registration (redirect)
   note left of "OAuth2 Provider"
       User enters their provider credentials
       and grants access.
   end note
   "OAuth2 Provider" -> "AuthorizeController (OAuth2 Popup)": Redirect to callback-url and provide access code & state
   "AuthorizeController (OAuth2 Popup)" -> "VerifyController": Post message with access code & state for evaluation
   "VerifyController" -> "VerifyController": Validate state (session)
   "VerifyController" -> "OAuth2 Provider": Fetch AccessToken by access code
   "OAuth2 Provider" -> "VerifyController": Provide AccessToken
   "VerifyController" -> "OAuth2 Provider": Fetch ResourceOwner by AccessToken
   "OAuth2 Provider" -> "VerifyController": Provide ResourceOwner
   "VerifyController" -> "VerifyController": Store provider id and user id in database
   "VerifyController" -> "TYPO3 Backend": Redirect to user setup module
   @enduml

Creating backend users
----------------------

This extension does not provide the possibility to create backend users on the fly itself.
Its purpose is to provide OAuth2 authentication only. To allow backend users to register
directly via OAuth2, the extension comes with a PSR-14 event
:php:`Waldhacker\Oauth2Client\Events\BackendUserLookupEvent`
that can be used to create the backend users.
With the next release of this extension this documentation will explain
how an implementation can look like. Furthermore, a reference implementation
for user registration via Gitlab will be published soon.

Basic frontend authentication flows
===================================

Login flow
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

   "TYPO3 Frontend" -> "FrontendAuthenticationService": Request OAuth2 login
   "FrontendAuthenticationService" -> "FrontendAuthenticationService": Generate / set state (session)
   "FrontendAuthenticationService" -> "OAuth2 Provider": Authentication request (redirect)
   note left of "OAuth2 Provider"
       User enters their provider credentials
       and grants access.
   end note
   "OAuth2 Provider" -> "FrontendAuthenticationService": Redirect to callback-url and provide access code & state
   "FrontendAuthenticationService" -> "FrontendAuthenticationService": Validate state (session)
   "FrontendAuthenticationService" -> "OAuth2 Provider": Fetch AccessToken by access code
   "OAuth2 Provider" -> "FrontendAuthenticationService": Provide AccessToken
   "FrontendAuthenticationService" -> "OAuth2 Provider": Fetch ResourceOwner by AccessToken
   "OAuth2 Provider" -> "FrontendAuthenticationService": Provide ResourceOwner
   "FrontendAuthenticationService" -> "FrontendAuthenticationService": Match by ResourceOwner ID & Provider ID
   "FrontendAuthenticationService" -> "FrontendAuthenticationService": Fire FrontendUserLookupEvent
   "FrontendAuthenticationService" -> "TYPO3 Login": Return found user
   "TYPO3 Login" -> "TYPO3 Frontend": Return to the location from which the OAuth2 login was made
   @enduml

Registering new OAuth2 provider for frontend user
-------------------------------------------------

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

   "TYPO3 Frontend" -> "RegistrationController": Request OAuth2 registration
   "RegistrationController" -> "RegistrationController": Generate / set state (session)
   "RegistrationController" -> "OAuth2 Provider": Request for application registration (redirect)
   note left of "OAuth2 Provider"
       User enters their provider credentials
       and grants access.
   end note
   "OAuth2 Provider" -> "RegistrationController": Redirect to callback-url and provide access code & state
   "RegistrationController" -> "RegistrationController": Validate state (session)
   "RegistrationController" -> "OAuth2 Provider": Fetch AccessToken by access code
   "OAuth2 Provider" -> "RegistrationController": Provide AccessToken
   "RegistrationController" -> "OAuth2 Provider": Fetch ResourceOwner by AccessToken
   "OAuth2 Provider" -> "RegistrationController": Provide ResourceOwner
   "RegistrationController" -> "RegistrationController": Store ResourceOwner ID and TYPO3 user ID in database
   "RegistrationController" -> "TYPO3 Frontend": Return to the location from which the OAuth2 registration was made
   @enduml

Creating frontend users
-----------------------

This extension does not provide the possibility to create frontend users on the fly itself.
Its purpose is to provide OAuth2 authentication only. To allow frontend users to register
directly via OAuth2, the extension comes with a PSR-14 event
:php:`Waldhacker\Oauth2Client\Events\FrontendUserLookupEvent`
that can be used to create the frontend users.
With the next release of this extension this documentation will explain
how an implementation can look like. Furthermore, a reference implementation
for frontend user registration via Gitlab will be published soon.

Migration from version 1.x to 2.x
=================================

The table :sql:`tx_oauth2_client_configs` that used in version 1.x to contain the registered OAuth2 providers for backend users has been renamed to :sql:`tx_oauth2_beuser_provider_configuration` in version 2.x.
To migrate the data there is an upgrade wizard named :guilabel:`Migrate OAuth2 table tx_oauth2_client_configs to tx_oauth2_beuser_provider_configuration`.
Please run this wizard after you have updated to version 2.x.

