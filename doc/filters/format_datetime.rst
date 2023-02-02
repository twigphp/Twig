``format_datetime``
===================

The ``format_datetime`` filter formats a date time:

.. code-block:: twig

    {# Aug 7, 2019, 11:39:12 PM #}
    {{ '2019-08-07 23:39:12'|format_datetime() }}

Format
------

You can tweak the output for the date part and the time part:

.. code-block:: twig

    {# 23:39 #}
    {{ '2019-08-07 23:39:12'|format_datetime('none', 'short', locale='fr') }}

    {# 07/08/2019 #}
    {{ '2019-08-07 23:39:12'|format_datetime('short', 'none', locale='fr') }}

    {# mercredi 7 août 2019 23:39:12 UTC #}
    {{ '2019-08-07 23:39:12'|format_datetime('full', 'full', locale='fr') }}

Supported values are: ``none``, ``short``, ``medium``, ``long``, and ``full``.

.. versionadded:: 3.6

    ``relative_short``, ``relative_medium``, ``relative_long``, and ``relative_full`` are also supported when running on
    PHP 8.0 and superior or when using a polyfill that define the ``IntlDateFormatter::RELATIVE_*`` constants and
    associated behavior.

For greater flexibility, you can even define your own pattern
(see the `ICU user guide`_ for supported patterns).

.. code-block:: twig

    {# 11 oclock PM, GMT #}
    {{ '2019-08-07 23:39:12'|format_datetime(pattern="hh 'oclock' a, zzzz") }}

Locale
------

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# 7 août 2019 23:39:12 #}
    {{ '2019-08-07 23:39:12'|format_datetime(locale='fr') }}

Timezone
--------

By default, the date is displayed by applying the default timezone (the one
specified in php.ini or declared in Twig -- see below), but you can override
it by explicitly specifying a timezone:

.. code-block:: twig

    {{ datetime|format_datetime(locale='en', timezone='Pacific/Midway') }}

If the date is already a DateTime object, and if you want to keep its current
timezone, pass ``false`` as the timezone value:

.. code-block:: twig

    {{ datetime|format_datetime(locale='en', timezone=false) }}

The default timezone can also be set globally by calling ``setTimezone()``::

    $twig = new \Twig\Environment($loader);
    $twig->getExtension(\Twig\Extension\CoreExtension::class)->setTimezone('Europe/Paris');

.. note::

    The ``format_datetime`` filter is part of the ``IntlExtension`` which is not
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

* ``locale``: The locale
* ``dateFormat``: The date format
* ``timeFormat``: The time format
* ``pattern``: A date time pattern
* ``timezone``: The date timezone name
* ``calendar``: The calendar ("gregorian" by default)

.. _ICU user guide: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
