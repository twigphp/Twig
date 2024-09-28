``format_time``
===============

The ``format_time`` filter formats a time. It behaves in the exact same way as
the :doc:`format_datetime<format_datetime>` filter, but without the date.

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
* ``pattern``: A date time pattern
* ``timezone``: The date timezone
* ``calendar``: The calendar ("gregorian" by default)

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
