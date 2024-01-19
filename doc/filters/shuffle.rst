``shuffle``
========

The ``shuffle`` filter shuffles an array:

.. code-block:: twig

    {% for user in users|shuffle %}
        ...
    {% endfor %}

.. caution::

    Internally, Twig uses the PHP `shuffle`_ function.
    This function assigns new keys to the elements in array. It will remove
    any existing keys that may have been assigned, rather than just reordering the keys.

Example 1:

.. code-block:: html+twig

    {% set items = [
        'a',
        'b',
        'c'
    ] %}

    <ul>
        {% for item in items|shuffle %}
            <li>{{ item }}</li>
        {% endfor %}
    </ul>

The above example will be rendered as:

.. code-block:: html+twig

    <ul>
        <li>a</li>
        <li>c</li>
        <li>b</li>
    </ul>

Note, results can also be :
"a, b, c" or "b, a, c" or "b, c, a" or "c, a, b" or "c, b, a".

Example 2:

.. code-block:: html+twig

    {% set items = [
        'a' => 'd',
        'b' => 'e',
        'c' => 'f'
    ] %}

    <ul>
        {% for index, item in items|shuffle %}
            <li>{{ index }} - {{ item }}</li>
        {% endfor %}
    </ul>

The above example will be rendered as:

.. code-block:: html+twig

    <ul>
        <li>0 - d</li>
        <li>1 - f</li>
        <li>2 - e</li>
    </ul>

Note, results can also be :
"d, e, f" or "e, d, f" or "e, f, d" or "f, d, e" or "f, e, d".

.. _`shuffle`: https://www.php.net/shuffle
