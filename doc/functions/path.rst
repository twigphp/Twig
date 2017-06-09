``path``
=========

When a link to a route path is needed, use the ``path`` function:

.. code-block:: jinja

    <a href="{{ path('some_route_name', {'some_arg': article.arg}) }}">
        {{ article.title }}
    </a>
