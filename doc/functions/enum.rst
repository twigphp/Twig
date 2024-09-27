``enum_cases``
==============

``enum`` gives access to enums:

.. code-block:: twig

    {# display one specific case of a backed enum #}
    {{ enum('App\\MyEnum').SomeCase.value }}

    {# get all cases of an enum #}
    {% enum('App\\MyEnum').cases() %}

    {# call any methods of the enum class #}
    {% enum('App\\MyEnum').someMethod() %}

When using a string literal for the ``enum`` argument, it will be validated
during compile time to be a valid enum name.

Arguments
---------

* ``enum``: The FQCN of the enum
