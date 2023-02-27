``singularize``
========

The ``singularize`` filter transforms a given plural string into an array of singular words

Here is an example:

.. code-block:: twig

    {{ 'leaves'|singularize|join(', ') }}
    leaf, leave, leaff

.. note::

    The ``singularize`` filter is part of the ``StringExtension`` which is not
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

