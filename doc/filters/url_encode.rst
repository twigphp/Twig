``url_encode``
==============

.. versionadded:: 1.12.3

    Support for encoding an array as query string was added in Twig 1.12.3.

.. versionadded:: 1.16.0

    The ``raw`` argument was removed in Twig 1.16.0. Twig now always encodes
    according to RFC 3986.

The ``url_encode`` filter percent encodes a given string as URL segment
or an array as query string:

.. code-block:: twig

    {{ "path-seg*ment"|url_encode }}
    {# outputs "path-seg%2Ament" #}

    {{ "string with spaces"|url_encode }}
    {# outputs "string%20with%20spaces" #}

    {{ {'param': 'value', 'foo': 'bar'}|url_encode }}
    {# outputs "param=value&foo=bar" #}

.. note::

    Internally, Twig uses the PHP `urlencode`_ (or `rawurlencode`_ if you pass
    ``true`` as the first parameter) or the `http_build_query`_ function. Note
    that as of Twig 1.16.0, ``urlencode`` **always** uses ``rawurlencode`` (the
    ``raw`` argument was removed.)

.. _`urlencode`:        https://www.php.net/urlencode
.. _`rawurlencode`:     https://www.php.net/rawurlencode
.. _`http_build_query`: https://www.php.net/http_build_query
