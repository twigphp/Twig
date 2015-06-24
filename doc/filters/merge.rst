``merge``
=========

The ``merge`` filter merges an array with another array:

.. code-block:: jinja

    {% set values = [1, 2] %}

    {% set values = values|merge(['apple', 'orange']) %}

    {# values now contains [1, 2, 'apple', 'orange'] #}

New values are added at the end of the existing ones.

The ``merge`` filter also works on hashes:

.. code-block:: jinja

    {% set items = { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'unknown' } %}

    {% set items = items|merge({ 'peugeot': 'car', 'renault': 'car' }) %}

    {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car', 'renault': 'car' } #}

For hashes, the merging process occurs on the keys: if the key does not
already exist, it is added but if the key already exists, its value is
overridden.

.. tip::

    If you want to ensure that some values are defined in an array (by given
    default values), reverse the two elements in the call:

    .. code-block:: jinja

        {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}

        {% set items = { 'apple': 'unknown' }|merge(items) %}

        {# items now contains { 'apple': 'fruit', 'orange': 'fruit' } #}
        
.. note::

    Internally, Twig uses the PHP `array_merge`_ function, which means that it will loose any numerical index after a merge, unless the numerical index is concatenated with something to make a string.
    
    .. code-block:: jinja

        {% set items = { 2: 'apple', 4: 'orange' } %}

        {% set items = { '3': 'banana' }|merge(items) %}

        {# items now contains { 0: 'banana', 1: 'apple', 2: 'orange' } #}
        
    .. code-block:: jinja

        {% set result = { 3: 'banana' } %}
        {% set items = { 2: 'apple', 4: 'orange' } %}

        {% for key, value in items %}
            {% set result = result|merge({('_'~key): value}) %}
        {% endfor %}

        {# result now contains { 0: 'banana', '_2': 'apple', '_4': 'orange' } #}

.. _`array_merge`: http://php.net/array_merge
