``enum``
========

.. versionadded:: 3.15

    The ``enum`` function was added in Twig 3.15.

``enum`` gives access to enums:

.. code-block:: twig

    {# display one specific case of a backed enum #}
    {{ enum('App\\CardSuite').Clubs.value }} {# "clubs" #}

    {# display one specific case of a backed enum, with a dynamic name #}
    {% set case_name = 'Spades' %}
    {{ enum('App\\CardSuite').(case_name).name }} {# "Spades" #}

    {# get all cases of an enum #}
    {% for case in enum('App\\CardSuite').cases %}
        {{ case.value }}
    {% endfor %}
    {# "clubs", "spades", "hearts", "diamonds" #}

    {# get a specific case of an enum by value #}
    {% set card_suite = enum('App\\CardSuite').from('hearts') %}
    {{ card_suite.name }} {# "Hearts" #}
    {{ card_suite.value }} {# "hearts" #}

    {# call any methods of the enum class #}
    {{ enum('App\\CardSuite').someMethod() }}

When using a string literal for the ``enum`` argument, it will be validated during compile time to be a valid enum name.

Arguments
---------

* ``enum``: The FQCN of the enum
