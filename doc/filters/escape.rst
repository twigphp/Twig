``escape``
==========

The ``escape`` filter converts the characters ``&``, ``<``, ``>``, ``'``, and
``"`` in strings to HTML-safe sequences. Use this if you need to display text
that might contain such characters in HTML:

.. code-block:: jinja

    {{ user.username|escape }}

For convenience, the ``e`` filter is defined as an alias:

.. code-block:: jinja

    {{ user.username|e }}

The ``escape`` filter can also be used in another context than HTML; for
instance, to escape variables included in a JavaScript:

.. code-block:: jinja

    {{ user.username|escape('js') }}
    {{ user.username|e('js') }}

.. note::

    Internally, ``escape`` uses the PHP native `htmlspecialchars`_ function.

.. _`htmlspecialchars`: http://php.net/htmlspecialchars
