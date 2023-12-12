``upper``
=========

The ``upper`` filter converts a value to uppercase:

.. code-block:: twig

    {{ 'welcome'|upper }}

    {# outputs 'WELCOME' #}

.. note::

    Internally, Twig uses the PHP `mb_strtoupper`_ function.

.. _`mb_strtoupper`: https://www.php.net/mb_strtoupper
