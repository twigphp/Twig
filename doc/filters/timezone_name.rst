``timezone_name``
=================

The ``timezone_name`` filter returns the timezone name given a timezone identifier:

.. code-block:: twig

    {# Central European Time (Paris) #}
    {{ 'Europe/Paris'|timezone_name }}

    {# Pacific Time (Los Angeles) #}
    {{ 'America/Los_Angeles'|timezone_name }}

By default, the filter uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# heure du Pacifique nord-américain (Los Angeles) #}
    {{ 'America/Los_Angeles'|timezone_name('fr') }}

.. note::

    You can find the comprehensive list of available timezone_names and locales for this filter at the following link:

    https://github.com/symfony/intl/tree/master/Resources/data/timezones.

    Taking the previous example into consideration,

    the filter searches for the 'America/Los_Angeles' timezone key within the array located in the 'fr.php' file
    and will then retrieves the associated timezone name.

If the specified locale were to be unknown, it will default to the closest available locale instead:

.. code-block:: twig

    {# heure du Pacifique nord-américain (Los Angeles) #}
    {{ 'America/Los_Angeles'|timezone_name('fr_FOO') }}
    {# equivalent to {{ 'America/Los_Angeles'|timezone_name('fr') }} #}

.. note::

    The ``timezone_name`` filter is part of the ``IntlExtension`` which is not
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
