``replace``
===========

The ``replace`` filter formats a given string by replacing the placeholders
(placeholders are free-form):

.. code-block:: jinja

    {{ "I like %this% and %that%."|replace({'%this%': foo, '%that%': "bar"}) }}

    {# outputs I like foo and bar
       if the foo parameter equals to the foo string. #}

If your want to replace a string stored in a variable, tell Twig to evaluate the variable to a string first by putting it in parentheses:

.. code-block:: jinja
    {% set myVariable = "My text" %}
    {{ "My text"|replace({(myVariable): "Your text"}) }}

    {# outputs "Your text". #}

Arguments
---------

* ``replace_pairs``: The placeholder values

.. seealso:: :doc:`format<format>`
