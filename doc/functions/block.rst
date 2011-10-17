``block``
=========

When a template uses inheritance and if you want to print a block multiple
times, use the ``block`` function:

.. code-block:: jinja

    <title>{% block title %}{% endblock %}</title>

    <h1>{{ block('title') }}</h1>

    {% block body %}{% endblock %}

Naming
------

When naming a block please be sure to not use a hyphen '-'. Underscores '_' are allowed.

.. seealso:: :doc:`extends<../tags/extends>`, :doc:`parent<../functions/parent>`
