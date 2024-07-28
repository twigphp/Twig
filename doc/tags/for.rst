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

Recursive Loops
---------------

To use loops recursively, pass the iterable you want to recurse to the
``loop()`` function; the following example shows how to use it for a recursive
sitemap:

.. code-block:: html+twig

    <ul class="sitemap">
    {%- for item in sitemap %}
        <li>{{ item.title }}
        {%- if item.children -%}
            <ul class="submenu">{{ loop(item.children) }}</ul>
        {%- endif %}</li>
    {%- endfor %}
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
``loop.depth``        Deep level of a recursive loop (1 indexed)
``loop.depth0``       Deep level of a recursive loop (0 indexed)
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
``loop()``            Allows to iterate over a nested sequence/mapping
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

Accessing the outer ``loop`` in Nested Loops
--------------------------------------------

When using nested loops, you can access the outer ``loop`` object by storing it
in a variable before entering the inner loop.

For instance, if you have the following template data::

    $data = [
        'topics' => [
            'topic1' => ['Message 1 of topic 1', 'Message 2 of topic 1'],
            'topic2' => ['Message 1 of topic 2', 'Message 2 of topic 2'],
        ],
    ];

And the following template to display all messages in all topics:

.. code-block:: twig

    {% for topic, messages in topics %}
        * {{ loop.index }}: {{ topic }}
        {% set outer_loop = loop %}
        {% for message in messages %}
            - {{ outer_loop.index }}.{{ loop.index }}: {{ message }}
        {% endfor %}
    {% endfor %}

The output will be similar to:

.. code-block:: text

    * 1: topic1
      - 1.1: The message 1 of topic 1
      - 1.2: The message 2 of topic 1
    * 2: topic2
      - 2.1: The message 1 of topic 2
      - 2.2: The message 2 of topic 2

Within the inner loop, the ``outer_loop`` variable can be used to reference the
outer loop object.
