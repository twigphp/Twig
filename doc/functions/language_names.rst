``language_names``
==================

.. versionadded:: 3.5

    The ``language_names`` function was added in Twig 3.5.

The ``language_names`` function returns the names of the languages:

.. code-block:: twig

    {# Abkhazian, Achinese, ... #}
    {{ language_names()|join(', ') }}
    
By default, the function uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# abkhaze, aceh, ... #}
    {{ language_names('fr')|join(', ') }}

.. note::

    For more information on the format of the locale:
    See https://www.rfc-editor.org/info/bcp47 for the specifications.
    It is documented by https://www.php.net/manual/en/class.locale.php.

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# abkhaze, aceh, ... #}
    {{ language_names('fr_FOO')|join(', ') }}
    {# equivalent to {{ language_names('fr')|join(', ') }} #}

.. note::

    The ``language_names`` function is part of the ``IntlExtension`` which is not
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
