``autoescape``
==============

Whether automatic escaping is enabled or not, you can mark a section of a
template to be escaped or not by using the ``autoescape`` tag:

.. code-block:: twig

    {% autoescape %}
        Everything will be automatically escaped in this block
        using the HTML strategy
    {% endautoescape %}

    {% autoescape 'html' %}
        Everything will be automatically escaped in this block
        using the HTML strategy
    {% endautoescape %}

    {% autoescape 'js' %}
        Everything will be automatically escaped in this block
        using the js escaping strategy
    {% endautoescape %}

    {% autoescape false %}
        Everything will be outputted as is in this block
    {% endautoescape %}

When automatic escaping is enabled everything is escaped by default except for
values explicitly marked as safe. Those can be marked in the template by using
the :doc:`raw<../filters/raw>` filter:

.. code-block:: twig

    {% autoescape %}
        {{ safe_value|raw }}
    {% endautoescape %}

The :doc:`parent<../functions/parent>` and :doc:`block<../functions/block>`
functions always return markup that is safe for every strategy.

.. deprecated:: 3.31

    Content produced by a template is only safe for the strategy it was
    produced under as of Twig 3.31. Printing it in a context using another
    strategy triggers a deprecation and will escape it in Twig 4.0.

Content a template produces carries the escaping strategy it was produced
under: the result of a :doc:`macro<macro>`, content captured with
:doc:`set<set>`, and the result of the
:doc:`include()<../functions/include>` and
:doc:`include_only()<../functions/include_only>` functions. Printing it in a
context using another strategy is deprecated, as content escaped for one
context is not escaped for another one. A macro carries the default strategy of
the template defining it, not the one in effect where it is called.

.. note::

    Twig is smart enough to not escape an already escaped value by the
    :doc:`escape<../filters/escape>` filter when the automatic escaping
    strategy is the same as the one applied by the escape filter.

.. note::

    Twig does not escape string literals printed directly, but it escapes
    variables, even when they were set to a literal:

    .. code-block:: html+twig

        {% set hello = "<strong>Hello</strong>" %}
        {{ hello }}
        {{ "<strong>world</strong>" }}

    Will be rendered as:

    .. code-block:: html

        &lt;strong&gt;Hello&lt;/strong&gt;
        <strong>world</strong>

.. note::

    The chapter :doc:`Twig for Developers<../api>` gives more information
    about when and how automatic escaping is applied.
