``format_datetime``
===================

The ``format_datetime`` filter formats a date time:

.. code-block:: twig

    {# Aug 7, 2019, 11:39:12 PM #}
    {{ '2019-08-07 23:39:12'|format_datetime() }}

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

Format
------

You can tweak the output for the date part and the time part:

.. code-block:: twig

    {# 23:39 #}
    {{ '2019-08-07 23:39:12'|format_datetime('none', 'short', locale: 'fr') }}

    {# 07/08/2019 #}
    {{ '2019-08-07 23:39:12'|format_datetime('short', 'none', locale: 'fr') }}

    {# mercredi 7 août 2019 23:39:12 UTC #}
    {{ '2019-08-07 23:39:12'|format_datetime('full', 'full', locale: 'fr') }}

Supported values are: ``none``, ``short``, ``medium``, ``long``, and ``full``.

When no pattern is provided, each omitted format uses the corresponding
:ref:`application default <intl-date-format-defaults>`, or ``medium`` when none
is configured.

.. versionadded:: 3.6

    ``relative_short``, ``relative_medium``, ``relative_long``, and ``relative_full`` are also supported when running on
    PHP 8.0 and superior or when using a polyfill that define the ``IntlDateFormatter::RELATIVE_*`` constants and
    associated behavior.

For greater flexibility, you can even define your own pattern
(see the `ICU user guide`_ for supported patterns).

.. code-block:: twig

    {# 11 o'clock PM, GMT #}
    {{ '2019-08-07 23:39:12'|format_datetime(pattern: "hh 'oclock' a, zzzz") }}

When no pattern, date format or time format is provided, Twig uses the
:ref:`application default pattern <intl-date-format-defaults>`, if any.

Locale
------

By default, the filter uses the
:ref:`application default locale <intl-date-format-defaults>`, or the current
locale when none is configured. You can override it explicitly:

.. code-block:: twig

    {# 7 août 2019 23:39:12 #}
    {{ '2019-08-07 23:39:12'|format_datetime(locale: 'fr') }}

Calendar
--------

By default, the filter uses the
:ref:`application default calendar <intl-date-format-defaults>`, or the
Gregorian calendar when none is configured. You can override it explicitly:

.. code-block:: twig

    {{ '2019-08-07 23:39:12'|format_datetime(
        calendar: 'traditional',
        locale: 'th_TH',
    ) }}

Timezone
--------

By default, the date is displayed by applying the default timezone (the one
specified in php.ini or declared in Twig -- see below), but you can override
it by explicitly specifying a timezone:

.. code-block:: twig

    {{ datetime|format_datetime(locale: 'en', timezone: 'Pacific/Midway') }}

If the date is already a DateTime object, and if you want to keep its current
timezone, pass ``false`` as the timezone value:

.. code-block:: twig

    {{ datetime|format_datetime(locale: 'en', timezone: false) }}

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

.. _intl-date-format-defaults:

Configure Defaults
------------------

You can configure the locale, formats and calendar once for all date and time
filters by passing an ``IntlDateFormatter`` when registering the extension::

    use Twig\Extra\Intl\IntlExtension;

    $dateFormatter = new \IntlDateFormatter(
        locale: 'fr_FR',
        dateType: \IntlDateFormatter::LONG,
        timeType: \IntlDateFormatter::SHORT,
        calendar: \IntlDateFormatter::GREGORIAN,
    );

    $twig->addExtension(new IntlExtension(
        dateFormatterPrototype: $dateFormatter,
    ));

Arguments passed to a filter override these defaults. You can also configure a
pattern with the ``pattern`` argument.

When using a dependency injection container, pass the formatter as the
``$dateFormatterPrototype`` argument of the ``IntlExtension`` service.

Arguments
---------

* ``locale``: The locale code as defined in `RFC 5646`_
* ``dateFormat``: The date format
* ``timeFormat``: The time format
* ``pattern``: A date time pattern
* ``timezone``: The date timezone name
* ``calendar``: The calendar

.. _ICU user guide: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
