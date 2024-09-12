``same as``
===========

``same as`` checks if a variable is the same as another variable.
This is equivalent to ``===`` in PHP:

.. code-block:: twig

    {% if user.name is same as(false) %}
        the user attribute is the 'false' PHP value
    {% endif %}
