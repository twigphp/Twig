``keys``
========

The ``keys`` filter returns the keys of a sequence or a mapping. It is useful
when you want to iterate over the keys of a sequence or a mapping:

.. code-block:: twig

    {% for key in [1, 2, 3, 4]|keys %}
        {{ key }}
    {% endfor %}
    {# outputs: 1 2 3 4 #}

    {% for key in {a: 'a_value', b: 'b_value'}|keys %}
        {{ key }}
    {% endfor %}
    {# outputs: a b #}

.. note::

    Internally, Twig uses the PHP `array_keys`_ function.

.. _`array_keys`: https://www.php.net/array_keys
