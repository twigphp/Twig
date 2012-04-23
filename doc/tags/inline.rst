``inline``
==========

.. versionadded:: 1.8
    The ``inline`` tag was added in Twig 1.8.

The ``inline`` statement allows you to inline a template instead of including
it from an external file (like with the ``include`` statement):

.. code-block:: jinja

    {% inline %}
        {% extends "sidebar.twig" %}

        {% block content %}
            Some content for the sidebar
        {% endblock %}
    {% endinline %}

As it's not easy to understand in which circumstances it might come in handy,
let's take an example; imagine a base template shared by many pages with a
single block::

    ┌─── Page n ──────────────────────────┐
    │                                     │
    │           ┌─────────────────────┐   │
    │           │                     │   │
    │           │                     │   │
    │           │                     │   │
    │           │                     │   │
    │           │                     │   │
    │           │                     │   │
    │           └─────────────────────┘   │
    │                                     │
    └─────────────────────────────────────┘

Even pages (page 2, 4, ...) share the same structure for the block::

    ┌─── Page 2 & 4 ──────────────────────┐
    │                                     │
    │           ┌── Base even ────────┐   │
    │           │ ┌─────────────────┐ │   │
    │           │ │ Content 2, ...  │ │   │
    │           │ └─────────────────┘ │   │
    │           │ ┌─────────────────┐ │   │
    │           │ │ Content 2, ...  │ │   │
    │           │ └─────────────────┘ │   │
    │           └─────────────────────┘   │
    │                                     │
    └─────────────────────────────────────┘

And odd pages (page 1, 3, ...) share a different structure for the block::

    ┌─── Page 1 & 3 ──────────────────────┐
    │                                     │
    │           ┌── Base odd ─────────┐   │
    │           │ ┌───────┐ ┌───────┐ │   │
    │           │ │       │ │       │ │   │
    │           │ │content│ │content│ │   │
    │           │ │3, ... │ │3, ... │ │   │
    │           │ │       │ │       │ │   │
    │           │ └───────┘ └───────┘ │   │
    │           └─────────────────────┘   │
    │                                     │
    └─────────────────────────────────────┘

Without the ``inline`` tag, you have two ways to design your templates:

 * Create two base templates (one for even blocks and another one for odd
   blocks) to factor out the common template code, then one template for each
   page that inherits from one of the base template;

 * Inline the each custom page content directly into each page without any use
   of external templates (you need to repeat the common code for all
   templates).

These two solutions do not scale well because they each have a major drawback:

 * The first solution makes you create many external files (that you won't
   re-use anywhere else) and so it fails to keep your templates readable (many
   code and content are out of context);

 * The second solution makes you duplicate some common code from one template
   to another (so it fails to obey the "Don't repeat yourself" principle).

In such a situation, the ``inline`` tag fixes all these issues. The common
code can be factored out in base templates (as in solution 1), and the custom
content is kept in each page (as in solution 2):

.. code-block:: jinja

    {# template for an even template: page 2 #}

    {% extends page %}

    {% block base %}
        {% inline %}
            {% extends "even.twig" %}

            {% block content1 %}
                Content 1 for page 2
            {% endblock %}

            {% block content2 %}
                Content 2 for page 2
            {% endblock %}
        {% endinline %}
    {% endblock %}

And here is the code for ``even.twig``:

.. code-block:: jinja

    Some code

    {% block content1 %}
        Some default content
    {% endblock %}

    Some other code

    {% block content2 %}
        Some default content
    {% endblock %}

    Yet some other code

The goal of the ``even.twig`` base template being to factor out the ``Some
code``, ``Some other code``, and ``Yet some other code`` parts.

The ``inline`` tag can be customized with the same options (``with``,
``only``, ``ignore missing``) as the ``include`` tag:

.. code-block:: jinja

    {% inline with {'foo': 'bar'} %}
        ...
    {% endinline %}

    {% inline with {'foo': 'bar'} only %}
        ...
    {% endinline %}

    {% inline ignore missing %}
        ...
    {% endinline %}

.. seealso:: :doc:`include<../tags/include>`
