``format_datetime``
===================

.. versionadded:: 2.12

    The ``format_datetime`` filter was added in Twig 2.12.

The ``format_datetime`` filter formats a date time:

.. code-block:: twig

    {# Aug 7, 2019, 11:39:12 PM #}
    {{ '2019-08-07 23:39:12'|format_datetime() }}

You can tweak the output for the date part and the time part:

.. code-block:: twig

    {# 23:39 #}
    {{ '2019-08-07 23:39:12'|format_datetime('none', 'short', locale='fr') }}

    {# 07/08/2019 #}
    {{ '2019-08-07 23:39:12'|format_datetime('short', 'none', locale='fr') }}

    {# mercredi 7 août 2019 23:39:12 UTC #}
    {{ '2019-08-07 23:39:12'|format_datetime('full', 'full', locale='fr') }}

Supported values are: ``none``, ``short``, ``medium``, ``long``, and ``full``.

For greater flexibility, you can even define your own pattern (see the `ICU user
guide
<https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax>`_
for supported patterns).

.. code-block:: twig

    {# 11 oclock PM, GMT #}
    {{ '2019-08-07 23:39:12'|format_datetime(pattern="hh 'oclock' a, zzzz") }}

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# 7 août 2019 23:39:12 #}
    {{ '2019-08-07 23:39:12'|format_datetime(locale='fr') }}

It is possible to set TimeZone (see `WiKi: List of tz database time zones
<https://en.wikipedia.org/wiki/List_of_tz_database_time_zones>`_
). Date and time for timezone set via argument calculated from default timezone, default timezone depends from `system settings
<https://twig.symfony.com/doc/1.x/filters/date.html#timezone>`_
.

.. code-block:: twig

    {% set datetime='2020-02-02 13:15:00' %}
    {% set pattern='MMM d (eee) HH:mm (v / ZZZZ)' %}

    {# Current default timezone `Europe/Moscow` GMT+3 #}
    {# Feb 2 (Sun) 13:15 (Moscow Time / GMT+03:00) #}
    {{ datetime|format_datetime(locale='en', pattern=pattern) }}

    {# -11:00 - 3:00 | -14:00 #}
    {# Feb 1 (Sat) 23:15 (Midway Time / GMT-11:00) #}
    {{ datetime|format_datetime(locale='en', pattern=pattern, timezone='Pacific/Midway') }}

    {#  13:45 - 3:00 | +10:45 #}
    {# Feb 3 (Mon) 00:00 (Chatham Time / GMT+13:45) #}
    {{ datetime|format_datetime(locale='en', pattern=pattern, timezone='Pacific/Chatham') }}


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
* ``calendar``: The calendar (Gregorian by default)
