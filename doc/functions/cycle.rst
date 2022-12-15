``cycle``
=========

The ``cycle`` function cycles on an array of values:

.. code-block:: twig

    {% set start_year = date() | date('Y') %}
    {% set end_year = start_year + 5 %}

    {% for year in start_year..end_year %}
        {{ cycle(['odd', 'even'], loop.index0) }}
    {% endfor %}
    
    {# outputs

        odd
        even
        odd
        even
        odd
        even
        
    #}

The array can contain any number of values:

.. code-block:: twig

    {% set fruits = ['apple', 'orange', 'citrus'] %}

    {% for i in 0..10 %}
        {{ cycle(fruits, i) }}
    {% endfor %}
    
    {# outputs
    
        apple
        orange
        citrus
        apple
        orange
        citrus
        apple
        orange
        citrus
        apple
        orange
    
    #}

Arguments
---------

* ``values``: The list of values to cycle on
* ``position``: The cycle position
