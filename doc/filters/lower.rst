``lower``
=========

The ``lower`` filter converts a value to lowercase:

.. code-block:: twig

    {{ 'WELCOME'|lower }}

    {# outputs 'welcome' #}

.. note::

    Internally, Twig uses the PHP `mb_strtolower`_ function.

.. _`mb_strtolower`: https://www.php.net/manual/fr/function.mb-strtolower.php
