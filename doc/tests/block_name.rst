``block name``
==============

.. versionadded:: 1.28
    The ``block name`` test was added in Twig 1.28.

``block name`` checks if a block is defined in the current context of the template.

.. code-block:: jinja

    {% if 'title' is block name %}
        <title>{{ block('title') }}<title>
    {% endif %}
