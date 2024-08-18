``enum_cases``
==============

``enum_cases`` returns the list of cases for a given enum:

.. code-block:: twig

    {% for case in enum_cases('App\\MyEnum') %}
        {{ case.value }}
    {% endfor %}

When using a string literal for the ``enum`` argument, it will be validated during compile time to be a valid enum name.

Arguments
---------

* ``enum``: The FQCN of the enum
