``merge``
=========

The ``merge`` filter merges an array or a hash with the given value:

.. code-block:: jinja

    {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}

    {% set items = items|merge({ 'peugeot': 'car' }) %}

    {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car' } #}
