``sort``
========

.. versionadded:: 2.12
    The ``arrow`` argument was added in Twig 2.12.

The ``sort`` filter sorts an array:

.. code-block:: twig

    {% for user in users|sort %}
        ...
    {% endfor %}

.. note::

    Internally, Twig uses the PHP `asort`_ function to maintain index
    association. It supports Traversable objects by transforming
    those to arrays.

You can pass an arrow function to sort the array:

.. code-block:: twig

    {% set list = [
        { name: 'Apples', quantity: 5 },
        { name: 'Oranges', quantity: 2 },
        { name: 'Grapes', quantity: 4 },
    ] %}

    {% for fruit in fruits|sort((a, b) => a.quantity == b.quantity ? 0 : (a.quantity > b.quantity ? 1 : -1))|column('name') %}
        {{ fruit }}
    {% endfor %}

    {# output in this order: Oranges, Grapes, Apples #}

Arguments
---------

* ``arrow``: An arrow function

.. _`asort`: https://secure.php.net/asort
