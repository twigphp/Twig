``block``
=========

.. versionadded: 1.28
    Using ``block`` with the ``defined`` test was added in Twig 1.28.
 
When a template uses inheritance and if you want to print a block multiple
times, use the ``block`` function:

.. code-block:: jinja

    <title>{% block title %}{% endblock %}</title>

    <h1>{{ block('title') }}</h1>

    {% block body %}{% endblock %}

Use the ``defined`` test to check if a block exists in the context of the
current template:

.. code-block:: jinja

    {% if block("footer") is defined %}
        ...
    {% endif %}

.. seealso:: :doc:`extends<../tags/extends>`, :doc:`parent<../functions/parent>`
