``country_name``
================

The ``country_name`` filter returns the country name given its ISO-3166
two-letter code:

.. code-block:: twig

    {# France #}
    {{ 'FR'|country_name }}

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# États-Unis #}
    {{ 'US'|country_name('fr') }}

The locale can contain more than two letters depending on the region:

.. code-block:: twig

    {# 美國 #}
    {{ 'US'|country_name('zh_Hant_HK') }}

.. note::

    For more information on the format of the locale:
    See https://www.rfc-editor.org/info/bcp47 for the specifications.
    It is documented by https://www.php.net/manual/en/class.locale.php.

    Taking the previous example into consideration, the filter searches for the 'US' country key within the array located
    in the 'zh_Hant_HK.php' file corresponding to the locale and then retrieves the associated country name.

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# États-Unis #}
    {{ 'US'|country_name('fr_FOO') }}
    {# equivalent to {{ 'US'|country_name('fr') }} #}

.. note::

    The ``country_name`` filter is part of the ``IntlExtension`` which is not
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
