``html_attr_type``
==================

.. _html_attr_type:

.. versionadded:: 3.24

    The ``html_attr_type`` filter was added in Twig 3.24.

The ``html_attr_type`` filter converts arrays into specialized attribute value
objects that implement custom rendering logic. It is designed for use
with the :ref:`html_attr` function for attributes where
the attribute value follows special formatting rules.

.. code-block:: html+twig

    <img {{ html_attr({
        srcset: ['small.jpg 480w', 'large.jpg 1200w']|html_attr_type('cst')
    }) }}>

    {# Output: <img srcset="small.jpg 480w, large.jpg 1200w"> #}

Available Types
---------------

Space-Separated Token List (``sst``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Used for attributes that expect space-separated values, like ``class`` or
``aria-labelledby``:

.. code-block:: html+twig

    {% set classes = ['btn', 'btn-primary']|html_attr_type('sst') %}

    <button {{ html_attr({class: classes}) }}>
        Click me
    </button>

    {# Output: <button class="btn btn-primary">Click me</button> #}

This is the default type used when the :ref:`html_attr` function encounters an
array value (except for ``style`` attributes).

Comma-Separated Token List (``cst``)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Used for attributes that expect comma-separated values, like ``srcset`` or
``sizes``:

.. code-block:: html+twig

    <img {{ html_attr({
        srcset: ['image-1x.jpg 1x', 'image-2x.jpg 2x', 'image-3x.jpg 3x']|html_attr_type('cst'),
        sizes: ['(max-width: 600px) 100vw', '50vw']|html_attr_type('cst')
    }) }}>

    {# Output: <img srcset="image-1x.jpg 1x, image-2x.jpg 2x, image-3x.jpg 3x" sizes="(max-width: 600px) 100vw, 50vw"> #}

Inline Style (``style``)
~~~~~~~~~~~~~~~~~~~~~~~~

Used for style attributes. Handles both maps (property - value pairs) and sequences (CSS declarations):

.. code-block:: html+twig

    {# Associative array #}
    {% set styles = {color: 'red', 'font-size': '14px'}|html_attr_type('style') %}

    <div {{ html_attr({style: styles}) }}>
        Styled content
    </div>

    {# Output: <div style="color: red; font-size: 14px;">Styled content</div> #}

    {# Numeric array #}
    {% set styles = ['color: red', 'font-size: 14px']|html_attr_type('style') %}

    <div {{ html_attr({style: styles}) }}>
        Styled content
    </div>

    {# Output: <div style="color: red; font-size: 14px;">Styled content</div> #}

The ``style`` type is automatically applied by the :ref:`html_attr` function when
it encounters an array value for the ``style`` attribute.

.. note::

    The ``html_attr_type`` filter is part of the ``HtmlExtension`` which is not
    installed by default. Install it first:

    .. code-block:: bash

        $ composer require twig/html-extra

    Then, on Symfony projects, install the ``twig/extra-bundle``:

    .. code-block:: bash

        $ composer require twig/extra-bundle

    Otherwise, add the extension explicitly on the Twig environment::

        use Twig\Extra\Html\HtmlExtension;

        $twig = new \Twig\Environment(...);
        $twig->addExtension(new HtmlExtension());

Arguments
---------

* ``value``: The sequence of attributes to convert
* ``type``: The attribute type. One of:

  * ``sst`` (default): Space-separated token list
  * ``cst``: Comma-separated token list
  * ``style``: Inline CSS styles

.. seealso::

    :ref:`html_attr`,
    :ref:`html_attr_merge`
