``types``
=======

The ``types`` tag declares the types for one or more names variables.

To do this, specify a :ref:`mapping <twig-expressions>` of names to their types as string constants. 

Here is how you declare that variable ``foo`` is a boolean, while ``bar`` is an integer (see note below):

.. code-block:: twig

    {% types {
        foo: 'bool',
        bar: 'int',
    } %}

You may put these all on one line. However, using one line per variable improves readability.

This tag has no effect on the template's output or runtime behavior.

Its purpose is to enable designers and developers to document and specify the context's available
and/or required variables.

Additionally, :ref:`Twig extensions <creating_extensions>` can analyze these tags to perform compile-time and
runtime analysis of templates in order to increase code quality.

.. note::

    The syntax for and contents of type strings are intentionally left out of scope. 
    
