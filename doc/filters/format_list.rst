``format_list``
===============

.. versionadded:: 3.29

    The ``format_list`` filter was added in Twig 3.29.

.. note::

    The ``format_list`` filter requires the ``IntlListFormatter`` class, which is available since PHP 8.5.

The ``format_list`` filter formats a list of strings as a single, locale-aware string:

.. code-block:: twig

    {# en: Apple, Banana, and Cherry #}
    {{ ['Apple', 'Banana', 'Cherry']|format_list }}

The list of supported types:

* ``and``: Formats the list as a conjunction (default)::

        {# en: Apple, Banana, and Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(type: 'and', locale: 'en') }}

* ``or``: Formats the list as a disjunction::

        {# en: Apple, Banana, or Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(type: 'or', locale: 'en') }}

* ``units``: Formats the list as a compound unit, without implying a conjunction or disjunction::

        {# en: Apple, Banana, Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(type: 'units', locale: 'en') }}

The list of supported widths:

* ``wide``: The full-length form (default)::

        {# en: Apple, Banana, and Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(width: 'wide', locale: 'en') }}

* ``short``: A shorter form::

        {# en: Apple, Banana, & Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(width: 'short', locale: 'en') }}

* ``narrow``: The most compact form::

        {# en: Apple, Banana, Cherry #}
        {{ ['Apple', 'Banana', 'Cherry']|format_list(width: 'narrow', locale: 'en') }}

By default, the filter uses the current locale. You can pass it explicitly::

    {# fr: Apple, Banana et Cherry #}
    {{ ['Apple', 'Banana', 'Cherry']|format_list(locale: 'fr') }}


.. note::

    The ``format_list`` filter is part of the ``IntlExtension`` which is not installed by default. Install it first:

    .. code-block:: sh

        $ composer require twig/intl-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: sh

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Intl\IntlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new IntlExtension());

Arguments
---------

* ``type``: The type of the list output
* ``width``: The width of the list output
* ``locale``: The locale code as defined in `RFC 5646`_

.. _RFC 5646: https://www.rfc-editor.org/info/rfc5646
