``html_attr``
=============

.. _html_attr:

.. versionadded:: 3.23

    The ``html_attr`` function was added in Twig 3.24.

The ``html_attr`` function renders HTML attributes from one or more mappings,
taking care of proper escaping. The mappings contain the names of HTML
attributes as keys, and the corresponding values represent the attributes'
values.

.. note::

    Attribute names are escaped using the ``html_attr_relaxed`` strategy.

.. code-block:: html+twig

    <div {{ html_attr({class: ['foo', 'bar'], id: 'main'}) }}>
        Content
    </div>

    {# Output: <div class="foo bar" id="main">Content</div> #}

The function accepts multiple attribute maps. Internally, it uses
:ref:`html_attr_merge` to combine the arguments:

.. code-block:: html+twig

    {% set base_attrs = {class: ['btn']} %}
    {% set variant_attrs = {class: ['btn-primary'], disabled: true} %}

    <button {{ html_attr(base_attrs, variant_attrs) }}>
        Click me
    </button>

    {# Output: <button class="btn btn-primary" disabled="">Click me</button> #}

.. note::

    To make best use of the special merge behavior of ``html_attr_merge`` and
    to avoid confusion, you should consistently use iterables (mappings or sequences)
    for attributes that can take multiple values, like ``class``, ``srcset`` or ``aria-describedby``.

    Use non-iterable values for attributes that contain a single value only, like
    ``id`` or ``href``.

Shorthand notation for mappings can be particularly helpful:

.. code-block:: html+twig

    {% set id = 'user-123' %}
    {% set href = '/profile' %}

    <a {{ html_attr({id, href}) }}>Profile</a>

    {# Output: <a id="user-123" href="/profile">Profile</a> #}

``null`` and Boolean Attribute Values
-------------------------------------

``null`` values always omit printing an attribute altogether.

The boolean ``false`` value also omits the attribute altogether, with an
exception for ``aria-*`` attribute names, see below.

.. code-block:: html+twig

    {# null omits the attribute entirely, and so does false for non-"aria-*" #}
    <input {{ html_attr({disabled: false, title: null}) }}>
    {# Output: <input> #}

``true`` will print the attribute with the empty value ``""``. This is XHTML compatible,
and in HTML 5 equivalent to using the short attribute notation without a value. An exception
is made for ``data-*`` and ``aria-*`` attributes, see below.

.. code-block:: html+twig

    {# true becomes an empty string value #}
    <input {{ html_attr({required: true}) }}>
    {# Output: <input required="">, which is equivalent to <input required> #}

Array Values
------------

Attribute values that are iterables are automatically converted to space-separated
token lists of the values. Exceptions apply for ``data-*`` and ``style`` attributes,
described further below.

.. code-block:: html+twig

    <div {{ html_attr({class: ['btn', 'btn-primary', 'btn-lg']}) }}>
        Button
    </div>

    {# Output: <div class="btn btn-primary btn-lg">Button</div> #}

.. note::

    This is not bound to the ``class`` attribute name, but works for any attribute.

You can use the :ref:`html_attr_type` filter to specify a different strategy for
concatenating values (e.g., comma-separated for ``srcset`` attributes). This would
also override the special behavior for ``data-*`` and ``style``.

WAI-ARIA Attributes
-------------------

To make it more convenient to work with the `WAI-ARIA type mapping for HTML
<https://www.w3.org/TR/wai-aria-1.2/#typemapping>_`, boolean values for ``aria-*``
attributes are converted to strings ``"true"`` and ``"false"``.

.. code-block:: html+twig

    <button {{ html_attr({'aria-pressed': true, 'aria-hidden': false}) }}>
        Toggle
    </button>

    {# Output: <button aria-pressed="true" aria-hidden="false">Toggle</button> #}

Data Attributes
---------------

For ``data-*`` attributes, boolean ``true`` values will be converted to ``"true"``.
Values that are not scalars are automatically JSON-encoded.

.. code-block:: html+twig

    <div {{ html_attr({'data-config': {theme: 'dark', size: 'large'}, 'data-bool': true, 'data-false': false}) }}>
        Content
    </div>

    {# Output: <div data-config="{&quot;theme&quot;:&quot;dark&quot;,&quot;size&quot;:&quot;large&quot;}" data-bool="true">Content</div> #}

Style Attribute
----------------

The ``style`` attribute name has special handling when its value is iterable:

.. code-block:: html+twig

    {# Non-numeric keys will be used as CSS properties and printed #}
    <div {{ html_attr({style: {color: 'red', 'font-size': '16px'}}) }}>
        Styled text
    </div>

    {# Output: <div style="color: red; font-size: 16px;">Styled text</div> #}

    {# Numeric keys will be assumed to have values that are individual CSS declarations #}
    <div {{ html_attr({style: ['color: red', 'font-size: 16px']}) }}>
        Styled text
    </div>

    {# Output: <div style="color: red; font-size: 16px;">Styled text</div> #}

    {# Merging style attributes #}
    <div {{ html_attr({style: {color: 'red'}}, {style: {color: 'blue', background: 'white'}}) }}>
        Styled text
    </div>

    {# Output: <div style="color: blue; background: white;">Styled text</div> #}

.. warning::

    No additional escaping specific to CSS is applied to key or values from this array.
    Do not use it to pass untrusted, user-provided data, neither as key nor as value.

``AttributeValueInterface`` Implementations
-------------------------------------------

For advanced use cases, attribute values can be objects that implement the ``AttributeValueInterface``.
These objects can define their own conversion logic for the ``html_attr`` function that will take
precedence over all rules described here. See the docblocks in that interface for details.

.. note::

    The ``html_attr`` function is part of the ``HtmlExtension`` which is not
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

.. seealso::

    :ref:`html_attr_merge`,
    :ref:`html_attr_type`
