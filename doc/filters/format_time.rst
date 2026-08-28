``format_time``
===============

The ``format_time`` filter formats a time. It supports the same locale,
timezone, calendar and format options as the
:doc:`format_datetime<format_datetime>` filter, but without the date.

When no format or pattern is provided, Twig uses the
:ref:`application default time format <intl-date-format-defaults>`, or
``medium`` when none is configured. To use a custom pattern, pass it explicitly.
The :ref:`application default pattern <intl-date-format-defaults>` is not used
for time-only formatting.

.. note::

    The ``format_time`` filter is part of the ``IntlExtension`` which is not
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

* ``locale``: The locale code as defined in `RFC 5646`_
* ``timeFormat``: The time format
* ``pattern``: A time pattern
* ``timezone``: The date timezone
* ``calendar``: The calendar

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
