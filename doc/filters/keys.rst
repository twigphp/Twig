``keys``
========

The ``keys`` filter returns the keys of an array. It is useful when you want to
iterate over the keys of an array:

.. code-block:: twig

    {% for key in array|keys %}
        ...
    {% endfor %}
    
Or, if you need both the keys and the values:

.. code-block:: twig

    {% for key, value in array %}
        {{ key }} : {{ value }}
    {% endfor %}
