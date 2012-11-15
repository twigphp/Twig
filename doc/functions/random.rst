``random``
==========

.. versionadded:: 1.5
    The random function was added in Twig 1.5.

.. versionadded:: 1.6
    String and integer handling was added in Twig 1.6.

The ``random`` function returns a random value depending on the supplied
parameter type:

* a random item from a sequence;
* a random character from a string;
* a random integer between 0 and the integer parameter (inclusive).

.. code-block:: jinja

    {{ random(['apple', 'orange', 'citrus']) }} {# example output: orange #}
    {{ random('ABC') }}                         {# example output: C #}
    {{ random() }}                              {# example output: 15386094 (works as native PHP `mt_rand`_ function) #}
    {{ random(5) }}                             {# example output: 3 #}

Arguments
---------

 * ``values``: The values

.. _`mt_rand`: http://php.net/mt_rand
