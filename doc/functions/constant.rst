``constant``
============

``constant`` returns the constant value for a given string:

.. code-block:: twig

    {{ some_date|date(constant('DATE_W3C')) }}
    {{ constant('Namespace\\Classname::CONSTANT_NAME') }}

You can read constants from object instances as well:

.. code-block:: twig

    {{ constant('RSS', date) }}

Retrieve the fully qualified class name of an object:

.. code-block:: twig

    {{ constant('class', date) }}

Use the ``defined`` test to check if a constant is defined:

.. code-block:: twig

    {% if constant('SOME_CONST') is defined %}
        ...
    {% endif %}
