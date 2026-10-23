``collate``
===========

The ``collate`` function compares two strings in the alphabetical order of a
language. It returns a negative number, zero, or a positive number, like the
`spaceship`_ operator, so that it can be used in the arrow function of the
:doc:`sort<../filters/sort>` filter:

.. code-block:: twig

    {# athlétisme, Boxe, Échecs, équitation, judo, Yoga #}
    {{ ['Yoga', 'équitation', 'Boxe', 'athlétisme', 'Échecs', 'judo']|sort((a, b) => collate(a, b))|join(', ') }}

    {% for city in cities|sort((a, b) => collate(a.name, b.name)) %}
        {{ city.name }}
    {% endfor %}

Unlike the ``<=>`` operator, which compares strings byte per byte, it does not
sort uppercase letters before lowercase ones or accented letters after ``z``.

By default, the function uses the current locale. You can pass it explicitly,
as the order depends on the language:

.. code-block:: twig

    {# cs: hrad, chata, ideal #}
    {{ ['ideal', 'chata', 'hrad']|sort((a, b) => collate(a, b, 'cs'))|join(', ') }}

    {# en: chata, hrad, ideal #}
    {{ ['ideal', 'chata', 'hrad']|sort((a, b) => collate(a, b, 'en'))|join(', ') }}

To sort in descending order, swap the arguments: ``collate(b, a)``.

To sort on several criteria, chain the comparisons with the ``?:`` operator:

.. code-block:: twig

    {% for user in users|sort((a, b) => collate(a.lastname, b.lastname) ?: collate(a.firstname, b.firstname)) %}
        {{ user.lastname }} {{ user.firstname }}
    {% endfor %}

.. note::

    The ``collate`` function is part of the ``IntlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/intl-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Intl\IntlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new IntlExtension());

Arguments
---------

* ``a``: The first string
* ``b``: The second string
* ``locale``: The locale code as defined in `RFC 5646`_

.. _spaceship: https://www.php.net/manual/en/language.operators.comparison.php
.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
