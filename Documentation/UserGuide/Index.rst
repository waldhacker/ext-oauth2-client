.. include:: ../Includes.txt

.. _user_guide:

==========
User guide
==========

Backend
=======

Register an OAuth2 provider as backend user
-------------------------------------------

.. rst-class:: bignums-xxl

1. Go to :guilabel:`User Settings`

   To register with an OAuth2 provider, go to your user settings and click :guilabel:`Setup OAuth2 Providers`.

   .. figure:: ../Images/Backend/userSettings_setup.png
      :class: with-shadow
      :alt: TYPO3 user setup module
      :width: 600px

      Setup OAuth2 provider configuration

2. Activate an OAuth2 provider

   On the next page, click on :guilabel:`Activate` for the OAuth2 provider you want to login with.

   .. figure:: ../Images/Backend/configuredProviders.png
      :class: with-shadow
      :alt: TYPO3 user setup module with configured OAuth2 providers
      :width: 600px

      Manage OAuth2 provider configuration

3. Login and grant access

   In the popup that opens, enter your OAuth2 provider user credentials and confirm
   that you are granting access to TYPO3.

   .. note::

      If the popup does not open, make sure you are not using a popup blocker.

Login as backend user with an OAuth2 provider
---------------------------------------------

.. rst-class:: bignums-xxl

1. Switch to OAuth2 providers

   On the TYPO3 login screen click the link :guilabel:`Login with OAuth2 providers (Social login)`

   .. figure:: ../Images/Backend/login_step1.png
      :class: with-shadow
      :alt: TYPO3 login form
      :width: 600px

      Choose OAuth2 provider

2. Choose an OAuth2 provider

   Choose the OAuth2 provider you want to use for logging in. Remember: you can only
   use a provider that you registered for your backend user in the previous step.

   .. figure:: ../Images/Backend/loginScreen.png
      :class: with-shadow
      :alt: TYPO3 backend login screen with configured OAuth2 providers
      :width: 600px

      Choose an OAuth2 provider

Frontend
========

Register an OAuth2 provider as frontend user
--------------------------------------------

.. rst-class:: bignums-xxl

1. Go into your user settings

   To register with an OAuth2 provider, go to your user settings.

   .. figure:: ../Images/Frontend/configuredProviders.png
      :class: with-shadow
      :alt: TYPO3 frontend plugin with configured OAuth2 providers
      :width: 600px

      Setup OAuth2 provider configuration

2. Activate an OAuth2 provider

   Click on :guilabel:`Activate` for the OAuth2 provider you want to login with.

   .. figure:: ../Images/Frontend/configuredProviders.png
      :class: with-shadow
      :alt: TYPO3 frontend plugin with configured OAuth2 providers
      :width: 600px

      Manage OAuth2 provider configuration

3. Login and grant access

   Enter your OAuth2 provider user credentials and confirm
   that you are granting access to TYPO3.

Login as frontend user with an OAuth2 provider
----------------------------------------------

.. rst-class:: bignums-xxl

1. Switch to OAuth2 providers

   On the TYPO3 login screen, choose the OAuth2 provider you want to use for logging in.
   Remember: you can only use a provider that you registered for your frontend user in the previous step.

   .. figure:: ../Images/Frontend/loginScreen.png
      :class: with-shadow
      :alt: TYPO3 frontend login screen
      :width: 600px

      Choose OAuth2 provider
