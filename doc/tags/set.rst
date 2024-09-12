``set``
=======

Inside code blocks you can also assign values to variables. Assignments use
the ``set`` tag and can have multiple targets.

Here is how you can assign the ``Fabien`` value to the ``name`` variable:

.. code-block:: twig

    {% set name = 'Fabien' %}

After the ``set`` call, the ``name`` variable is available in the template like
any other ones:

.. code-block:: twig

    {# displays Fabien #}
    {{ name }}

The assigned value can be any valid :ref:`Twig expression
<twig-expressions>`:

.. code-block:: twig

    {% set numbers = [1, 2] %}
    {% set user = {'name': 'Fabien'} %}
    {% set name = 'Fabien' ~ ' ' ~ 'Potencier' %}

Several variables can be assigned in one block:

.. code-block:: twig

    {% set first, last = 'Fabien', 'Potencier' %}

    {# is equivalent to #}

    {% set first = 'Fabien' %}
    {% set last = 'Potencier' %}

The ``set`` tag can also be used to "capture" chunks of text:

.. code-block:: html+twig

    {% set content %}
        <div id="pagination">
            ...
        </div>
    {% endset %}

.. caution::

    If you enable automatic output escaping, Twig will only consider the
    content to be safe when capturing chunks of text.

.. note::

    Note that loops are scoped in Twig; therefore a variable declared inside a
    ``for`` loop is not accessible outside the loop itself:

    .. code-block:: twig

        {% for item in items %}
            {% set value = item %}
        {% endfor %}

        {# value is NOT available #}

    If you want to access the variable, just declare it before the loop:

    .. code-block:: twig

        {% set value = "" %}
        {% for item in items %}
            {% set value = item %}
        {% endfor %}

        {# value is available #}
