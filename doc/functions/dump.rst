``dump``
========

.. versionadded:: 1.5
    The dump function was added in Twig 1.5.

The ``dump`` function dumps information about a template variable. This is
mostly useful to debug a template that does not behave as expected by
introspecting its variables:

.. code-block:: jinja

    {{ dump(user) }}

.. note::

    The ``debug`` function is not available by default. You must load it explicitly::

        $twig = new Twig_Environment($loader, $config);
        $twig->addExtension(new Twig_Extension_Debug());

    Even when loaded explicitly, it won't do anything if the ``debug`` option
    is not enabled.

In an HTML context, wrap the output with a ``pre`` tag to make it easier to
read:

.. code-block:: jinja

    <pre>
        {{ dump(user) }}
    </pre>

.. tip::

    Using a ``pre`` tag is not needed when `XDebug`_ is enabled and
    ``html_errors`` is ``on``; as a bonus, the output is also nicer with
    XDebug enabled.

You can debug several variables by passing them as additional arguments:

.. code-block:: jinja

    {{ dump(user, categories) }}

If you don't pass any value, all variables from the current context are
dumped:

.. code-block:: jinja

    {{ dump() }}

.. note::

    Internally, Twig uses the PHP `var_dump`_ function.

.. _`XDebug`: http://xdebug.org/docs/display
.. _`var_dump`: http://php.net/var_dump
