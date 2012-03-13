``convert_encoding``
====================

.. versionadded:: 1.4
    The ``convert_encoding`` filter was added in Twig 1.4.

The ``convert_encoding`` filter converts a string from one encoding to
another. The first argument is the expected output charset and the second one
is the input charset:

.. code-block:: jinja

    {{ data|convert_encoding('UTF-8', 'iso-2022-jp') }}

.. note::

    This filter relies on the `iconv`_ or `mbstring`_ extension, so one of
    them must be installed. In case both are installed, `iconv`_ is used
    by default.

.. _`iconv`:    http://php.net/iconv
.. _`mbstring`: http://php.net/mbstring
