``timezone_names``
==================

.. versionadded:: 3.5

    The ``timezone_names`` function was added in Twig 3.5.

The ``timezone_names`` function returns the names of the timezones:

.. code-block:: twig

    {# Acre Time (Eirunepe), Acre Time (Rio Branco), ... #}
    {{ timezone_names()|join(', ') }}
    
By default, the function uses the current locale. You can pass it explicitly:

.. code-block:: twig

    {# heure : Antarctique (Casey), heure : Canada (Montreal), ... #}
    {{ timezone_names('fr')|join(', ') }}

.. note::

    The ``timezone_names`` function is part of the ``IntlExtension`` which is not
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
