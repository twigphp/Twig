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

.. tip:

    If you need a step different from 1, you can use the ``range`` function
    instead.

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

The ``loop`` Object
-------------------

Inside of a ``for`` loop block, a ``loop`` object exposes some information
about the current loop iteration.

``loop`` Variables
~~~~~~~~~~~~~~~~~~

===================== ========================================================================
Variable              Description
===================== ========================================================================
``loop.index``        The current iteration of the loop (1 indexed)
``loop.index0``       The current iteration of the loop (0 indexed)
``loop.revindex``*    The number of iterations from the end of the loop (1 indexed)
``loop.revindex0``*   The number of iterations from the end of the loop (0 indexed)
``loop.first``        True if first iteration
``loop.last``         True if last iteration
``loop.length``*      The number of items in the sequence
``loop.parent``       The parent context
``loop.previous``     The value from the previous iteration (``null`` for the first iteration)
``loop.next``         The value from the next iteration (``null`` for the last iteration)
===================== ========================================================================

.. note::

    When the underlying PHP iterator is not countable, the ``loop.length``,
    ``loop.revindex``, and ``loop.revindex0`` variables are not available and a
    ``RuntimeException`` is thrown if you try to use them.

Here is an example on how to use the ``index`` variable:

.. code-block:: twig

    {% for user in users %}
        {{ loop.index }} - {{ user.username }}
    {% endfor %}

Use ``loop.previous`` and ``loop.next`` to access the previous or next values:

.. code-block:: twig

    {% for value in values %}
        {% if not loop.first and value > loop.previous %}
            The value just increased!
        {% endif %}
        {{ value }}
        {% if not loop.last and loop.next > value %}
            The value will increase even more!
        {% endif %}
    {% endfor %}

``loop.previous`` is ``null`` when called at the first item, and ``loop.next``
is ``null`` when called at the last item.

``loop`` Functions
~~~~~~~~~~~~~~~~~~

The ``loop`` object also exposes some functions:

===================== ========================================================================
Function              Description
===================== ========================================================================
``loop.cycle()``      Cycle over a sequence of values
``loop.changed()``    True if previously called with a different value or if not called yet
===================== ========================================================================

Use ``loop.cycle()`` to cycle among a list of values:

.. code-block:: html+twig

    {% for row in rows %}
        <li class="{{ loop.cycle('odd', 'even') }}">{{ row }}</li>
    {% endfor %}

Use ``loop.changed()`` to check if the value has changed since the last call:

.. code-block:: html+twig

    {% for entry in entries %}
        {% if loop.changed(entry.category) %}
            <h2>{{ entry.category }}</h2>
        {% endif %}
        <p>{{ entry.message }}</p>
    {% endfor %}
