``number_format``
=================

.. versionadded:: 1.5
    The number_format filter was added in Twig 1.5

The ``number_format`` filter formats numbers.  It is a wrapper around PHP's
`number_format`_ function:

.. code-block:: jinja

    {{ 200.35|number_format }}

You can control the number of decimal places, decimal point, and thousands
separator using the additional arguments:

.. code-block:: jinja

    {{ 9800.333|number_format(2, '.', ',') }}

If no formatting options are provided then Twig will use the default formatting
options of:

- 0 decimal places.
- ``.`` as the decimal point.
- ``,`` as the thousands separator.

These defaults can be easily changed through the core extension:

.. code-block:: php

    $twig = new Twig_Environment($loader);
    $twig->getExtension('core')->setNumberFormat(3, '.', ',');

The defaults set for ``number_format`` can be over-ridden upon each call using the
additional parameters.

.. _`number_format`: http://php.net/number_format
