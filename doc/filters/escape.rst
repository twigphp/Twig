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

The ``escape`` filter can also be used in other contexts than HTML thanks to
an optional argument which defines the escaping strategy to use:

.. code-block:: jinja

    {{ user.username|e }}
    {# is equivalent to #}
    {{ user.username|e('html') }}

And here is how to escape variables included in JavaScript code:

.. code-block:: jinja

    {{ user.username|escape('js') }}
    {{ user.username|e('js') }}

.. note::

    Internally, ``escape`` uses the PHP native `htmlspecialchars`_ function
    for the HTML escaping strategy.

.. _`htmlspecialchars`: http://php.net/htmlspecialchars
