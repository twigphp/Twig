``locale_name``
===============

The ``locale_name`` filter returns the locale name given its code:

.. code-block:: twig

    {# German #}
    {{ 'de'|locale_name }}

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# allemand #}
    {{ 'de'|locale_name('fr') }}

    {# français (Canada) #}
    {{ 'fr_CA'|locale_name('fr') }}

.. note::

    For more information on the format of the locale:
    See https://www.rfc-editor.org/info/bcp47 for the specifications.
    It is documented by https://www.php.net/manual/en/class.locale.php.

    Taking the previous example into consideration,

    the filter searches for the 'fr_CA' locale key within the array located in the 'fr.php' file and will then retrieves the associated locale name.

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# français (Canada) #}
    {{ 'fr_CA'|locale_name('fr_FOO') }}
    {# equivalent to {{ 'fr_CA'|locale_name('fr') }} #}

.. note::

    The ``locale_name`` filter is part of the ``IntlExtension`` which is not
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
