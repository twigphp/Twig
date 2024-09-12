``format``
==========

The ``format`` filter formats a given string by replacing the placeholders
(placeholders follows the `sprintf`_ notation):

.. code-block:: twig

    {% set fruit = 'apples' %}
    {{ "I like %s and %s."|format(fruit, "oranges") }}

    {# outputs I like apples and oranges #}

.. seealso::

    :doc:`replace<replace>`

.. _`sprintf`: https://www.php.net/sprintf
