``keys``
========

The ``keys`` filter returns the keys of an array. It is useful when you want to
iterate over the keys of an array:

.. code-block:: twig

    {% for key in array|keys %}
        ...
    {% endfor %}

.. note::

    Internally, Twig uses the PHP `array_keys`_ function.

.. _`array_keys`: https://www.php.net/array_keys
