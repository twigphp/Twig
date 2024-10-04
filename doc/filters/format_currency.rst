``format_currency``
===================

The ``format_currency`` filter formats a number as a currency:

.. code-block:: twig

    {# €1,000,000.00 #}
    {{ '1000000'|format_currency('EUR') }}

You can pass attributes to tweak the output:

.. code-block:: twig

    {# €12.34 #}
    {{ '12.345'|format_currency('EUR', {rounding_mode: 'floor'}) }}

    {# €1,000,000.0000 #}
    {{ '1000000'|format_currency('EUR', {fraction_digit: 4}) }}

The list of supported options:

* ``grouping_used``: Specifies whether to use grouping separator for thousands::

        {# €1,234,567.89 #}
        {{ 1234567.89 | format_currency('EUR', {grouping_used:true}, 'en') }}

* ``decimal_always_shown``: Specifies whether to always show the decimal part, even if it's zero::

        {# €123.00 #}
        {{ 123 | format_currency('EUR', {decimal_always_shown:true}, 'en') }}

* ``max_integer_digit``:
* ``min_integer_digit``:
* ``integer_digit``: Define constraints on the integer part::

        {# €345.68 #}
        {{ 12345.6789 | format_currency('EUR', {max_integer_digit:3, min_integer_digit:2}, 'en') }}

* ``max_fraction_digit``:
* ``min_fraction_digit``:
* ``fraction_digit``: Define constraints on the fraction part::

        {# €123.46 #}
        {{ 123.456789 | format_currency('EUR', {max_fraction_digit:2, min_fraction_digit:1}, 'en') }}

* ``multiplier``: Multiplies the value before formatting::

        {# €123,000.00 #}
        {{ 123 | format_currency('EUR', {multiplier:1000}, 'en') }}

* ``grouping_size``:
* ``secondary_grouping_size``: Set the size of the primary and secondary grouping separators::

        {# €1,23,45,678.00 #}
        {{ 12345678 | format_currency('EUR', {grouping_size:3, secondary_grouping_size:2}, 'en') }}

* ``rounding_mode``:
* ``rounding_increment``: Control rounding behavior, here is a list of all rounding_mode available:

    * ``ceil``: Ceiling rounding
    * ``floor``: Floor rounding
    * ``down``: Rounding towards zero
    * ``up``: Rounding away from zero
    * ``half_even``: Round halves to the nearest even integer
    * ``half_up``: Round halves up
    * ``half_down``: Round halves down

    .. code-block:: twig

      {# €123.50 #}
      {{ 123.456 | format_currency('EUR', {rounding_mode:'ceiling', rounding_increment:0.05}, 'en') }}

* ``format_width``:
* ``padding_position``: Set width and padding for the formatted number, here is a list of all padding_position available:

    * ``before_prefix``: Pad before the currency symbol
    * ``after_prefix``: Pad after the currency symbol
    * ``before_suffix``: Pad before the suffix (currency symbol)
    * ``after_suffix``: Pad after the suffix (currency symbol)

    .. code-block:: twig

        {# €123.00 #}
        {{ 123 | format_currency('EUR', {format_width:10, padding_position:'before_suffix'}, 'en') }}

* ``significant_digits_used``:
* ``min_significant_digits_used``:
* ``max_significant_digits_used``: Control significant digits in formatting::

        {# €123.4568 #}
        {{ 123.456789 | format_currency('EUR', {significant_digits_used:true, min_significant_digits_used:4, max_significant_digits_used:7}, 'en') }}

* ``lenient_parse``: If true, allows lenient parsing of the input::

        {# €123.00 #}
        {{ 123 | format_currency('EUR', {lenient_parse:true}, 'en') }}

By default, the filter uses the current locale. You can pass it explicitly::

    {# 1.000.000,00 € #}
    {{ '1000000'|format_currency('EUR', locale: 'de') }}

.. note::

    The ``format_currency`` filter is part of the ``IntlExtension`` which is not
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

* ``currency``: The currency (ISO 4217 code)
* ``attrs``: A map of attributes
* ``locale``: The locale code as defined in `RFC 5646`_

.. note::

    Internally, Twig uses the PHP `NumberFormatter::formatCurrency`_ function.

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
.. _`NumberFormatter::formatCurrency`: https://www.php.net/manual/en/numberformatter.formatcurrency.php
