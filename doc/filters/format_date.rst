``format_date``
===============

The ``format_date`` filter formats a date. It supports the same locale,
timezone, calendar and format options as the
:doc:`format_datetime<format_datetime>` filter, but without the time.

When no format or pattern is provided, Twig uses the
:ref:`application default date format <intl-date-format-defaults>`, or
``medium`` when none is configured. To use a custom pattern, pass it explicitly.
The :ref:`application default pattern <intl-date-format-defaults>` is not used
for date-only formatting.

.. note::

    The ``format_date`` filter is part of the ``IntlExtension`` which is not
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
* ``dateFormat``: The date format
* ``pattern``: A date pattern
* ``timezone``: The date timezone
* ``calendar``: The calendar

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
