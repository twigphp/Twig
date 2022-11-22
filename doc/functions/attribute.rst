``attribute``
=============

The ``attribute`` function can be used to access a "dynamic" attribute of a
variable:

.. code-block:: twig

    {{ attribute(object, method) }}
    {{ attribute(object, method, arguments) }}
    {{ attribute(array, item) }}

If you have a ``Foo`` class with a ``bar`` attribute and a ``getBar()`` method, you can use :

.. code-block:: twig

    {{ attribute(foo, 'getBar') }}

Or you can use the attribute name :

.. code-block:: twig

    {{ attribute(foo, 'bar') }}

Don't forget the quotes in the second argument, or you will have an error.

You can also use the function in an array like here :

.. code-block:: twig

    {% set foo = [1, 2] %}
    {{ attribute(foo, 0) }}
    {# result is 1 #}

In addition, the ``defined`` test can check for the existence of a dynamic
attribute:

.. code-block:: twig

    {{ attribute(object, method) is defined ? 'Method exists' : 'Method does not exist' }}

.. note::

    The resolution algorithm is the same as the one used for the ``.``
    notation, except that the item can be any valid expression.
