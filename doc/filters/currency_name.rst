``currency_name``
=================

The ``currency_name`` filter returns the currency name given its three-letter
code:

.. code-block:: twig

    {# Euro #}
    {{ 'EUR'|currency_name }}

    {# Japanese Yen #}
    {{ 'JPY'|currency_name }}

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# yen japonais #}
    {{ 'JPY'|currency_name('fr') }}

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# yen japonais #}
    {{ 'JPY'|currency_name('fr_FR') }}
    {# equivalent to {{ 'JPY'|currency_name('fr') }} #}

You can find the comprehensive list of available currency_names and locales for this filter at the following link:
https://github.com/symfony/intl/tree/master/Resources/data/currencies.

Taking the previous example into consideration, the filter searches for the 'JPY' currency key within the
array located in the 'fr_FR.php' file corresponding to the locale. Because this file doesn't exists, it will default
to use the 'fr.php' file and will then retrieves the associated currency name.

.. note::

    The ``currency_name`` filter is part of the ``IntlExtension`` which is not
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
