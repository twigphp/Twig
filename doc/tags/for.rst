``for``
=======

Loop over each item in a sequence or a mapping. For example, to display a list
of users provided in a variable called ``users``:

.. code-block:: html+twig

    <h1>Members</h1>
    <ul>
        {% for user in users %}
            <li>{{ user.username|e }}</li>
        {% endfor %}
    </ul>

.. note::

    A sequence or a mapping can be either an array or an object implementing
    the ``Traversable`` interface.

If you do need to iterate over a sequence of numbers, you can use the ``..``
operator:

.. code-block:: twig

    {% for i in 0..10 %}
        * {{ i }}
    {% endfor %}

The above snippet of code would print all numbers from 0 to 10.

It can be also useful with letters:

.. code-block:: twig

    {% for letter in 'a'..'z' %}
        * {{ letter }}
    {% endfor %}

The ``..`` operator can take any expression at both sides:

.. code-block:: twig

    {% for letter in 'a'|upper..'z'|upper %}
        * {{ letter }}
    {% endfor %}

.. tip::

    If you need a step different from 1, you can use the ``range`` function
    instead.

The ``loop`` variable
---------------------

Inside of a ``for`` loop block you can access some special variables:

===================== =============================================================
Variable              Description
===================== =============================================================
``loop.index``        The current iteration of the loop. (1 indexed)
``loop.index0``       The current iteration of the loop. (0 indexed)
``loop.revindex``     The number of iterations from the end of the loop (1 indexed)
``loop.revindex0``    The number of iterations from the end of the loop (0 indexed)
``loop.first``        True if first iteration
``loop.last``         True if last iteration
``loop.length``       The number of items in the sequence
``loop.parent``       The parent context
===================== =============================================================

.. code-block:: twig

    {% for user in users %}
        {{ loop.index }} - {{ user.username }}
    {% endfor %}

.. note::

    The ``loop.length``, ``loop.revindex``, ``loop.revindex0``, and
    ``loop.last`` variables are only available for PHP arrays, or objects that
    implement the ``Countable`` interface.

The ``loop.parent`` variable gives access to the context enclosing the ``for``
loop. It is not limited to nested loops: it exposes the whole context as it was
before the loop started, which covers two common needs.

When loops are nested, the parent context holds the outer ``loop`` variable, so
the inner loop can reach the outer iteration state:

.. code-block:: twig

    {% for topic, messages in topics %}
       * {{ loop.index }} : {{ topic }}
        {% for message in messages %}
            - {{ loop.parent.loop.index }}.{{ loop.index }}: {{ message }}
        {% endfor %}
    {% endfor %}

Here ``loop.parent.loop.index`` is the index of the current ``topic`` from the
outer ``for`` loop.

``loop.parent`` also lets you reach a variable that the loop variable shadows:
when both share the same name, the outer value is still available through the
parent context:

.. code-block:: twig

    {% set user = 'Fabien' %}
    {% for user in ['Homer', 'Bart'] %}
        {{ loop.index }}. {{ user }} (outer user: {{ loop.parent.user }})
    {% endfor %}

This outputs ``1. Homer (outer user: Fabien)`` then ``2. Bart (outer user: Fabien)``.

See also :doc:`the recipe on accessing the parent context in nested loops <recipes>`.

The ``else`` Clause
-------------------

If no iteration took place because the sequence was empty, you can render a
replacement block by using ``else``:

.. code-block:: html+twig

    <ul>
        {% for user in users %}
            <li>{{ user.username|e }}</li>
        {% else %}
            <li><em>no user found</em></li>
        {% endfor %}
    </ul>

Iterating over Keys
-------------------

By default, a loop iterates over the values of the sequence. You can iterate
on keys by using the ``keys`` filter:

.. code-block:: html+twig

    <h1>Members</h1>
    <ul>
        {% for key in users|keys %}
            <li>{{ key }}</li>
        {% endfor %}
    </ul>

Iterating over Keys and Values
------------------------------

You can also access both keys and values:

.. code-block:: html+twig

    <h1>Members</h1>
    <ul>
        {% for key, user in users %}
            <li>{{ key }}: {{ user.username|e }}</li>
        {% endfor %}
    </ul>

Iterating over a Subset
-----------------------

You might want to iterate over a subset of values. This can be achieved using
the :doc:`slice <../filters/slice>` filter:

.. code-block:: html+twig

    <h1>Top Ten Members</h1>
    <ul>
        {% for user in users|slice(0, 10) %}
            <li>{{ user.username|e }}</li>
        {% endfor %}
    </ul>

Iterating over a String
-----------------------

To iterate over the characters of a string, use the
:doc:`split <../filters/split>` filter:

.. code-block:: html+twig

    <h1>Characters</h1>
    <ul>
        {% for char in "諺 / ことわざ"|split('') -%}
            <li>{{ char }}</li>
        {%- endfor %}
    </ul>
