``date``
========

.. versionadded:: 1.6
    The date function has been added in Twig 1.6.

Converts an argument to a date to allow date comparison:

.. code-block:: jinja

    {% if date(user.created_at) < date('+2days') %}
        {# do something #}
    {% endif %}

The argument must be in a format supported by the `date`_ function.

You can pass a timezone as the second argument:

.. code-block:: jinja

    {% if date(user.created_at) < date('+2days', 'Europe/Paris') %}
        {# do something #}
    {% endif %}

If no argument is passed, the function returns the current date:

.. code-block:: jinja

    {% if date(user.created_at) < date() %}
        {# always! #}
    {% endif %}

.. _`date`: http://www.php.net/date
