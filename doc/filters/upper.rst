``upper``
=========

The ``upper`` filter converts a value to uppercase:

.. code-block:: twig

    {{ 'welcome'|upper }}

    {# outputs 'WELCOME' #}

.. note::

    Internally, Twig uses the PHP `mb_strtoupper`_ function.

.. _`array_keys`: https://www.php.net/mb_strtoupper
