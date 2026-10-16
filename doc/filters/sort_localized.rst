``sort_localized``
==================

.. versionadded:: 3.30

    The ``sort_localized`` filter was added in Twig 3.30.

The ``sort_localized`` filter sorts sequences and mappings in the alphabetical
order of a language:

.. code-block:: twig

    {# fr: athlétisme, Boxe, Échecs, équitation, judo, Yoga #}
    {{ ['Yoga', 'équitation', 'Boxe', 'athlétisme', 'Échecs', 'judo']|sort_localized|join(', ') }}

The :doc:`sort<sort>` filter compares strings byte per byte, which sorts
uppercase letters before lowercase ones and accented letters after ``z``
(``Boxe, Yoga, athlétisme, judo, Échecs, équitation``).

By default, the filter uses the current locale. You can pass it explicitly, as
the order depends on the language::

    {# cs: hrad, chata, ideal #}
    {{ ['ideal', 'chata', 'hrad']|sort_localized(locale: 'cs')|join(', ') }}

    {# en: chata, hrad, ideal #}
    {{ ['ideal', 'chata', 'hrad']|sort_localized(locale: 'en')|join(', ') }}

As with the ``sort`` filter, the keys are preserved; use the :doc:`reverse<reverse>`
filter to sort in descending order.

To sort values on something other than themselves, pass an arrow function that
returns the value to sort each item on:

.. code-block:: twig

    {% for city in cities|sort_localized(city => city.name) %}
        {{ city.name }}
    {% endfor %}

.. note::

    Unlike the arrow function of the ``sort`` filter, which compares two items,
    this one takes a single item and returns what to sort it on; the comparison
    is done by the rules of the locale.

.. note::

    The ``sort_localized`` filter is part of the ``IntlExtension`` which is not installed by default. Install it first:

    .. code-block:: sh

        $ composer require twig/intl-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: sh

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Intl\IntlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new IntlExtension());

Arguments
---------

* ``arrow``: An arrow function returning the value to sort each item on
* ``locale``: The locale code as defined in `RFC 5646`_

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
