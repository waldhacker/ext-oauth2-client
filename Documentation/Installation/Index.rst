.. include:: ../Includes.txt

.. _installation:

============
Installation
============

With Composer
=============

Require the extension with composer:

.. code-block:: bash

   composer require waldhacker/typo3-oauth2-client

Activate it either via command line or in the extension manager

.. code-block:: bash

   ./bin/typo3 extension:activate oauth2_client


Without Composer
================

Download the extension from TER and activate it in the extension manager.

.. note::

   Please note that the TER version comes with only the `GenericProvider` -
   as additional providers cannot be required by composer, if you want to use
   a more specific provider to ease configuration, please load it yourself.
