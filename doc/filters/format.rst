``format``
==========

The ``format`` filter formats a given string by replacing the placeholders
(placeholders follows the `sprintf`_ notation):

.. code-block:: jinja

    {{ "I like %s and %s."|format(foo, "bar") }}

    {# returns I like foo and bar
       if the foo parameter equals to the foo string. #}

.. _`sprintf`: http://www.php.net/sprintf

.. seealso:: :doc:`replace<replace>`
