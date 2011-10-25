``block``
=========

When a template uses inheritance and if you want to print a block multiple
times, use the ``block`` function:

.. code-block:: jinja

    <title>{% block title %}{% endblock %}</title>

    <h1>{{ block('title') }}</h1>

    {% block body %}{% endblock %}

.. tip::

    Block names must only contain letters, numbers, and underscores (``_``).
    The internal regexp reads as follows:
    ``[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*``.

.. seealso:: :doc:`extends<../tags/extends>`, :doc:`parent<../functions/parent>`
