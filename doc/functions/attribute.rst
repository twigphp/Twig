``attribute``
=============

.. warning::

    The ``attribute`` filter is deprecated as of Twig 3.15. Use the
    :ref:`dot operator <dot_operator>` that now accepts any expression
    when wrapped with parenthesis.

    Note that this filter will still be available in Twig 4.0 to allow a
    smoother upgrade path.

The ``attribute`` function can be used to access a "dynamic" attribute of a
variable:

.. code-block:: twig

    {{ attribute(object, method) }}
    {{ attribute(object, method, arguments) }}
    {{ attribute(array, item) }}

In addition, the ``defined`` test can check for the existence of a dynamic
attribute:

.. code-block:: twig

    {{ attribute(object, method) is defined ? 'Method exists' : 'Method does not exist' }}

.. note::

    The resolution algorithm is the same as the one used for the ``.``
    operator, except that the item can be any valid expression.

Arguments
---------

* ``variable``: The variable
* ``attribute``: The attribute name
* ``arguments``: An array of arguments to pass to the call
