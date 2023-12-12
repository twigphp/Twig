``batch``
=========

The ``batch`` filter "batches" items by returning a list of lists with the
given number of items. A second parameter can be provided and used to fill in
missing items:

.. code-block:: html+twig

    {% set items = ['a', 'b', 'c', 'd'] %}

    <table>
        {% for row in items|batch(3, 'No item') %}
            <tr>
                {% for index, column in row %}
                    <td>{{ index }} - {{ column }}</td>
                {% endfor %}
            </tr>
        {% endfor %}
    </table>

The above example will be rendered as:

.. code-block:: html+twig

    <table>
        <tr>
            <td>0 - a</td>
            <td>1 - b</td>
            <td>2 - c</td>
        </tr>
        <tr>
            <td>3 - d</td>
            <td>4 - No item</td>
            <td>5 - No item</td>
        </tr>
    </table>

If you choose to set the third parameter ``preserve_keys`` to ``false``, the keys will be reset in each loop.

.. code-block:: html+twig

    {% set items = ['a', 'b', 'c', 'd'] %}

    <table>
        {% for row in items|batch(3, 'No item', false) %}
            <tr>
                {% for index, column in row %}
                    <td>{{ index }} - {{ column }}</td>
                {% endfor %}
            </tr>
        {% endfor %}
    </table>

The above example will be rendered as:

.. code-block:: html+twig

    <table>
        <tr>
            <td>0 - a</td>
            <td>1 - b</td>
            <td>2 - c</td>
        </tr>
        <tr>
            <td>0 - d</td>
            <td>1 - No item</td>
            <td>2 - No item</td>
        </tr>
    </table>

Arguments
---------

* ``size``: The size of the batch; fractional numbers will be rounded up
* ``fill``: Used to fill in missing items
* ``preserve_keys``: Whether to preserve keys or not (defaults to ``true``)
