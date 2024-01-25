``locale_names``
================

.. versionadded:: 3.5

    The ``locale_names`` function was added in Twig 3.5.

The ``locale_names`` function returns the names of the locales:

.. code-block:: twig

    {# Afrikaans, Afrikaans (Namibia), ... #}
    {{ locale_names()|join(', ') }}
    
By default, the function uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# afrikaans, afrikaans (Afrique du Sud), ... #}
    {{ locale_names('fr')|join(', ') }}

.. note::

    You can find the comprehensive list of available locales for this filter at the following link:

    https://github.com/symfony/intl/tree/master/Resources/data/locales.

    Each available locale corresponds to a file name within this directory.

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# afghani (1927–2002), afghani afghan, ... #}
    {{ locale_names('fr_FOO')|join(', ') }}
    {# equivalent to {{ locale_names('fr')|join(', ') }} #}

.. note::

    The ``locale_names`` function is part of the ``IntlExtension`` which is not
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
