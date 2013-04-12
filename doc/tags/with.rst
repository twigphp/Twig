``with``
========

Enforces a nested scope where you can safely set variables without bloating the main context and avoiding potential name collisions.

.. code-block:: jinja

    {% set greeting, name = "Hello", "World" %}
    {% with %}
        {% set name = "Twig" %}
        {{ greeting }}, {{ name }} {# outputs "Hello, Twig" #}
    {% endwith %}
    {{ greeting }}, {{ name }} {# outputs "Hello, World" #}

As is usual to set variables when entering the new scope, that can be done directly in the tag, like in the next example:

.. code-block:: jinja

    {% with name = "Twig" %}
        Hello, {{ name }}
    {% endwith %}
    {# name here not defined anymore #}

.. note::

    It allows also for multiple sets at once, as does the :doc:`set <../tags/set>` tag.
