``embed``
=========

.. versionadded:: 1.8
    The ``embed`` tag was added in Twig 1.8.

The ``embed`` statement allows you to embed a template instead of including it
from an external file (like with the ``include`` statement):

.. code-block:: jinja

    {% embed "sidebar.twig" %}
        {% block content %}
            Some content for the sidebar
        {% endblock %}
    {% endembed %}

As it's not easy to understand in which circumstances it might come in handy,
let's take an example; imagine a base template shared by many pages with a
single block:

.. code-block:: text

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

Some pages (page 1, 2, ...) share the same structure for the block:

.. code-block:: text

    ┌─── Page 1 & 2 ──────────────────────┐
    │                                     │
    │           ┌── Base A ───────────┐   │
    │           │ ┌── content1 ─────┐ │   │
    │           │ │ content for p1  │ │   │
    │           │ └─────────────────┘ │   │
    │           │ ┌── content2 ─────┐ │   │
    │           │ │ content for p1  │ │   │
    │           │ └─────────────────┘ │   │
    │           └─────────────────────┘   │
    │                                     │
    └─────────────────────────────────────┘

While other pages (page a, b, ...) share a different structure for the block:

.. code-block:: text

    ┌─── Page a, b ───────────────────────┐
    │                                     │
    │           ┌── Base B ───────────┐   │
    │           │ ┌───────┐ ┌───────┐ │   │
    │           │ │       │ │       │ │   │
    │           │ │content│ │content│ │   │
    │           │ │a, ... │ │b, ... │ │   │
    │           │ │       │ │       │ │   │
    │           │ └───────┘ └───────┘ │   │
    │           └─────────────────────┘   │
    │                                     │
    └─────────────────────────────────────┘

Without the ``embed`` tag, you have two ways to design your templates:

 * Create two base templates (one for 1, 2, ... blocks and another one for a,
   b, ... blocks) to factor out the common template code, then one template
   for each page that inherits from one of the base template;

 * Embed each custom page content directly into each page without any use of
   external templates (you need to repeat the common code for all templates).

These two solutions do not scale well because they each have a major drawback:

 * The first solution makes you create many external files (that you won't
   re-use anywhere else) and so it fails to keep your templates readable (many
   code and content are out of context);

 * The second solution makes you duplicate some common code from one template
   to another (so it fails to obey the "Don't repeat yourself" principle).

In such a situation, the ``embed`` tag fixes all these issues. The common code
can be factored out in base templates (as in solution 1), and the custom
content is kept in each page (as in solution 2):

.. code-block:: jinja

    {# template for pages 1, 2, ... #}

    {% extends page %}

    {% block base %}
        {% embed "base_A.twig" %}
            {% block content1 %}
                Content 1 for page 2
            {% endblock %}

            {% block content2 %}
                Content 2 for page 2
            {% endblock %}
        {% endembed %}
    {% endblock %}

And here is the code for ``base_A.twig``:

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

The goal of the ``base_a.twig`` base template being to factor out the ``Some
code``, ``Some other code``, and ``Yet some other code`` parts.

The ``embed`` tag takes the exact same arguments as the ``include`` tag:

.. code-block:: jinja

    {% embed "base" with {'foo': 'bar'} %}
        ...
    {% endembed %}

    {% embed "base" with {'foo': 'bar'} only %}
        ...
    {% endembed %}

    {% embed "base" ignore missing %}
        ...
    {% endembed %}

.. warning::

    As embedded templates do not have "names", auto-escaping strategies based
    on the template "filename" won't work as expected if you change the
    context (for instance, if you embed a CSS/JavaScript template into an HTML
    one). In that case, explicitly set the default auto-escaping strategy with
    the ``autoescape`` tag.

.. seealso:: :doc:`include<../tags/include>`
