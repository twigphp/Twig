``reverse``
===========

The ``reverse`` filter reverses an array (or an object if it implements the
`Iterator`_ interface):

.. code-block:: jinja

    {% for use in users|reverse %}
        ...
    {% endfor %}

.. _`Iterator`: http://fr.php.net/manual/en/class.iterator.php
