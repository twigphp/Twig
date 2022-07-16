``dump_json``
========

The ``dump_json`` function dumps information about a template variable. This is
mostly useful to debug a template that does not behave as expected by
introspecting its variables:

.. code-block:: twig

    {{ dump_json(user) }}

.. note::

    The ``dump_json`` function is not available by default. You must add the
    ``\Twig\Extension\DebugExtension`` extension explicitly when creating your Twig
    environment::

        $twig = new \Twig\Environment($loader, [
            'debug' => true,
            // ...
        ]);
        $twig->addExtension(new \Twig\Extension\DebugExtension());

    Even when enabled, the ``dump_json`` function won't display anything if the
    ``debug`` option on the environment is not enabled (to avoid leaking debug
    information on a production server).

In an HTML context, wrap the output with a ``<script>`` tag to display it in the browser console:

.. code-block:: html+twig

    <script>
        console.log({{ dump(user) }})
    </script>

You can debug several variables by passing them as additional arguments:

.. code-block:: twig

    {{ dump_json(user, categories) }}

If you don't pass any value, all variables from the current context are
dumped:

.. code-block:: twig

    {{ dump_json() }}

.. note::

    Internally, Twig uses the PHP `json_encode` function.

Arguments
---------

* ``context``: The context to dump
