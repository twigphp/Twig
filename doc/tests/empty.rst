``empty``
=========

``empty`` checks if a variable is
 - an empty string
 - an empty array
 - an empty hash
 - exactly ``false``
 - exactly ``null``

.. code-block:: jinja

    {# evaluates to true if the foo variable is null, false, an empty array, or an empty string #}
    {% if foo is empty %}
        ...
    {% endif %}

.. _Countable: http://php.net/manual/en/class.countable.php
.. _count: http://php.net/manual/en/function.count.php
