``escape``
==========

.. versionadded:: 1.9.0
    The ``css``, ``url``, and ``html_attr`` strategies were added in Twig
    1.9.0.

The ``escape`` filter escapes a string for safe insertion into the final
output. It supports different escaping strategies depending on the template
context.

By default, it uses the HTML escaping strategy:

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

The ``escape`` filter supports the following escaping strategies:

* ``html``: escapes a string for the **HTML body** context.

* ``js``: escapes a string for the **JavaScript context**.

* ``css``: escapes a string for the **CSS context**. CSS escaping can be
  applied to any string being inserted into CSS and escapes everything except
  alphanumerics.

* ``url``: escapes a string for the **URI or parameter contexts**. This should
  not be used to escape an entire URI; only a subcomponent being inserted.

* ``html_attr``: escapes a string for the **HTML attribute** context.

.. note::

    Internally, ``escape`` uses the PHP native `htmlspecialchars`_ function
    for the HTML escaping strategy.

.. _`htmlspecialchars`: http://php.net/htmlspecialchars
