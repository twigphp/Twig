``replace``
===========

The ``replace`` filter formats a given string by replacing the placeholders
(placeholders are free-form):

.. code-block:: jinja

    {{ "I like %this% and %that%."|replace({'%this%': foo, '%that%': "bar"}) }}

    {# returns I like foo and bar
       if the foo parameter equals to the foo string. #}

.. seealso:: :doc:`format<format>`
