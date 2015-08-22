``constant``
============

``constant`` returns the constant value for a given string:

.. code-block:: jinja

    {{ some_date|date(constant('DATE_W3C')) }}
    {{ constant('Namespace\\Classname::CONSTANT_NAME') }}

You can read constants from object instances as well:

.. code-block:: jinja

    {{ constant('RSS', date) }}
