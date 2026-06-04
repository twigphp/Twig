``include``
===========

The ``include`` function returns the rendered content of a template:

.. code-block:: twig

    {{ include('template.html.twig') }}
    {{ include(some_var) }}

Included templates have access to the variables of the active context.

.. tip::

    Prefer the :doc:`include_only() function </functions/include_only>` when
    you can. Sharing the whole context lets a template silently rely on
    variables defined by the caller, which hides its real inputs and couples it
    to wherever it is included from. ``include_only`` takes only the variables
    you pass, making the data flow explicit and partials easier to reuse.

Its documentation also covers the template loading, ``ignore_missing`` and
return-value behavior shared by both functions.

The current context is passed by default to the template but you can also pass
additional variables:

.. code-block:: twig

    {# The included template can access "name" and the current context. #}
    {{ include('template.html.twig', {name: 'Fabien'}) }}

You can disable access to the context by setting ``with_context`` to
``false``:

.. code-block:: twig

    {# Only the "name" variable will be accessible. #}
    {{ include('template.html.twig', {name: 'Fabien'}, with_context: false) }}

When including a template created by an end user, you should
:doc:`sandbox<../sandbox>` it.

.. deprecated:: 3.29

    Sandboxing the included template via the ``sandboxed`` argument is
    deprecated as of Twig 3.29. Render the untrusted template with the
    ``Twig\Sandbox\Sandbox`` class from PHP or the
    :doc:`render_sandboxed() function <render_sandboxed>` from a trusted Twig
    template instead.

Arguments
---------

* ``template``:       The template to render
* ``variables``:      The variables to pass to the template
* ``with_context``:   Whether to pass the current context variables or not
* ``ignore_missing``: Whether to ignore missing templates or not
* ``sandboxed``:      Whether to sandbox the template or not (deprecated as of
  Twig 3.29)
