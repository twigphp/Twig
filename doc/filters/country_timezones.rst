``country_timezones``
=====================

.. versionadded:: 2.12
    The ``country_timezones`` filter was added in Twig 2.12.

The ``country_timezones`` filter returns the names of the countries associated
with a given timezone:

.. code-block:: twig

    {# Europe/Paris #}
    {{ 'FR'|country_timezones|join(', ') }}

.. note::

    The ``country_timezones`` filter is part of the ``IntlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer req twig/intl-extra

    Then, use the ``twig/extra-bundle`` on Symfony projects or add the extension
    explictly on the Twig environment::

        use Twig\Extra\Intl\IntlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new IntlExtension());
