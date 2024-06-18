``pluralize``
========

The ``pluralize`` filter transforms a given noun in its singular form into its plural version.

Here is an example:

.. code-block:: twig

    {{ 'partitions'|pluralize('en') }}
    partition

.. note::
    `lang` parameter is mandatory for this filter as only English and French are supported by the Inflector in Symfony.

The ``pluralize`` filter uses the method by the same name in Symfony's
`Inflector <https://symfony.com/doc/current/components/string.html#inflector>`_.

.. note::

    The ``pluralize`` filter is part of the ``StringExtension`` which is not
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

* ``lang``: The lang of the original string. Only English (`en`) and French (`fr`) are supported.
* ``singleResult``: This argument is optional. If set to false, the filter will return an array of pluralized words. Default is true.
