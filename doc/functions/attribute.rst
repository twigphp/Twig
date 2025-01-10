``attribute``
=============

.. warning::

    The ``attribute`` function is deprecated as of Twig 3.15. Use the
    :ref:`dot operator <dot_operator>` that now accepts any expression
    when wrapped with parenthesis.

    Note that this function will still be available in Twig 4.0 to allow a
    smoother upgrade path.

The ``attribute`` function lets you access an attribute, method, or property of
an object or array when the name of that attribute, method, or property is stored
in a variable or dynamically generated with an expression:

.. code-block:: twig

    {# method_name is a variable that stores the method to call #}
    {{ attribute(object, method_name) }}

    {# you can also pass arguments when calling a method #}
    {{ attribute(object, method_name, arguments) }}

    {# the method/property name can be the result of evaluating an expression #}
    {{ attribute(object, 'some_property_' ~ user.type) }}

    {# in addition to objects, this function works with plain arrays as well #}
    {{ attribute(array, item_name) }}

In addition, the ``defined`` test can check for the existence of a dynamic
attribute:

.. code-block:: twig

    {{ attribute(object, method) is defined ? 'Method exists' : 'Method does not exist' }}

.. note::

    The resolution algorithm is the same as the one used for the ``.``
    operator.

Arguments
---------

* ``variable``: The variable
* ``attribute``: The attribute name
* ``arguments``: An array of arguments to pass to the call
