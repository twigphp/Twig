``all``
========

.. versionadded:: 3.15

    The ``all`` filter was added in Twig 3.15.

The ``all`` filter returns ``true`` if all of the elements in the array return ``true`` when passed to the arrow function.
The arrow function receives the value of the sequence:

.. code-block:: twig

    {% set sizes = [34, 36, 38, 40, 42] %}

    {{ sizes|all(v => v > 38) }} {# false #}

It also works with mappings:

.. code-block:: twig

    {% set sizes = {
        xs:  34,
        s:   36,
        m:   38,
        l:   40,
        xl:  42,
    } %}

    {{ sizes|all(v => v > 30) }} {# true #}

The arrow function also receives the key as a second argument:

.. code-block:: twig

    {{ sizes|all((v, k) => k != 'xxl') }} {# true #}

Note that the arrow function has access to the current context:

.. code-block:: twig

    {% set my_size = 39 %}

    {{ sizes|all(v => v >= my_size) }} {# false #}

Arguments
---------

* ``array``: The sequence or mapping
* ``arrow``: The arrow function
