``singular``
============

.. versionadded:: 3.11

    The ``singular`` filter was added in Twig 3.11.

The ``singular`` filter transforms a given noun in its plural form into its
singular version:

.. code-block:: twig

    {# English (en) rules are used by default #}
    {{ 'partitions'|singular() }}
    partition

    {{ 'partitions'|singular('fr') }}
    partition

.. note::

    The ``singular`` filter is part of the ``StringExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/string-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\String\StringExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new StringExtension());

Arguments
---------

* ``locale``: The locale of the original string (limited to languages supported by the from Symfony `inflector`_, part of the String component)
* ``all``: Whether to return all possible plurals as an array, default is ``false``

.. note::

    Internally, Twig uses the `singularize`_ method from the Symfony String component.

.. _`inflector`: <https://symfony.com/doc/current/components/string.html#inflector>
.. _`singularize`: <https://symfony.com/doc/current/components/string.html#inflector>
