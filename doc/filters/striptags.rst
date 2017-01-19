``striptags``
=============

The ``striptags`` filter strips SGML/XML tags and replace adjacent whitespace
by one space:

.. code-block:: jinja

    {{ some_html|striptags }}

.. note::

    Internally, Twig uses the PHP `strip_tags`_ function.

    As a consequence, a parameter, ``$allowable_tags``, can be provided. In this example, the tags like ``<br/>``, ``<br>``, ``<p>`` and ``</p>`` will remain in the string:

.. code-block:: jinja

    {{ some_html|striptags('<br><p>') }}

.. _`strip_tags`: http://php.net/strip_tags
