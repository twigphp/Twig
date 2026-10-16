``include_only``
================

.. versionadded:: 3.29

    The ``include_only`` function was added in Twig 3.29.

The ``include_only`` function returns the rendered content of a template
without giving it access to the current context:

.. code-block:: twig

    {{ include_only('template.html.twig') }}
    {{ include_only(some_var) }}

Variables from the active context are not passed implicitly. This makes the
data a template relies on explicit, which is often clearer and easier to
reason about.

Note that global variables (like the ones registered via ``addGlobal()``) are
not part of the context: they remain available in the included template.

Returned Value
--------------

.. versionadded:: 3.31

    Tying the returned content to the escaping strategy of the included
    template was introduced in Twig 3.31.

The returned content is a ``\Twig\Markup`` instance considered safe for the
default escaping strategy the included template was compiled with, so it is not
escaped again when you store it in a variable and print it in a context using
that strategy:

.. code-block:: twig

    {% set body = include_only('body.html.twig') %}
    {{ body }} {# rendered as-is, not re-escaped #}

Printing it in a context using another strategy is deprecated as of Twig 3.31
and escapes it as of Twig 4.0, as content escaped for HTML is not escaped for a
JavaScript or CSS context. See the :doc:`deprecation notes</deprecated>` for how
to migrate.

Passing Variables
-----------------

As the context is not passed, variables a template needs must be passed
explicitly:

.. code-block:: twig

    {# template.html.twig only gets the "name" variable from the caller #}
    {{ include_only('template.html.twig', {name: 'Fabien'}) }}

When passing a variable from the current context, you can use the following
shortcut:

.. code-block:: twig

    {{ include_only('template.html.twig', {name, email}) }}

    {# is equivalent to #}

    {{ include_only('template.html.twig', {name: name, email: email}) }}

Loading Templates
-----------------

If you are using the filesystem loader, the templates are looked for in the
paths defined by it.

If the expression evaluates to a ``\Twig\TemplateWrapper`` instance, Twig
will use it directly::

    // {{ include_only(template) }}

    $template = $twig->load('some_template.html.twig');

    $twig->display('template.html.twig', ['template' => $template]);

When you set the ``ignore_missing`` flag, Twig will return an empty string if
the template does not exist:

.. code-block:: twig

    {{ include_only('sidebar.html.twig', ignore_missing: true) }}

You can also provide a list of templates that are checked for existence before
inclusion. The first template that exists will be rendered:

.. code-block:: twig

    {{ include_only(['page_detailed.html.twig', 'page.html.twig']) }}

If ``ignore_missing`` is set, it will fall back to rendering nothing if none
of the templates exist, otherwise it will throw an exception.

To render a template created by an end user, use the
:doc:`render_sandboxed() function </functions/render_sandboxed>`.

Arguments
---------

* ``template``:       The template to render
* ``variables``:      The variables to pass to the template
* ``ignore_missing``: Whether to ignore missing templates or not
