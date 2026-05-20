``spaceless``
=============

.. warning::

    The ``spaceless`` filter is deprecated as of Twig 3.12. While not a full
    replacement, you can check the :ref:`whitespace control features <templates-whitespace-control>`.

.. caution::

    The ``spaceless`` filter is declared safe for the ``html`` context, so its
    output is not auto-escaped. Its input is therefore pre-escaped by the
    auto-escaper when needed. If you re-implement this filter in your own
    code, declare it with both ``'pre_escape' => 'html'`` and
    ``'is_safe' => ['html']``: declaring ``is_safe`` without ``pre_escape``
    is equivalent to piping user input through ``|raw`` and opens an XSS
    vector.

Use the ``spaceless`` filter to remove whitespace *between HTML tags*, not
whitespace within HTML tags or whitespace in plain text:

.. code-block:: html+twig

    {{
        "<div>
            <strong>foo</strong>
        </div>
        "|spaceless }}

    {# output will be <div><strong>foo</strong></div> #}

You can combine ``spaceless`` with the ``apply`` tag to apply the transformation
on large amounts of HTML:

.. code-block:: html+twig

    {% apply spaceless %}
        <div>
            <strong>foo</strong>
        </div>
    {% endapply %}

    {# output will be <div><strong>foo</strong></div> #}

This tag is not meant to "optimize" the size of the generated HTML content but
merely to avoid extra whitespace between HTML tags to avoid browser rendering
quirks under some circumstances.

.. caution::

    As the filter uses a regular expression behind the scenes, its performance
    is directly related to the text size you are working on (remember that
    filters are executed at runtime).

.. tip::

    If you want to optimize the size of the generated HTML content, gzip
    compress the output instead.

.. tip::

    If you want to create a tag that actually removes all extra whitespace in
    an HTML string, be warned that this is not as easy as it seems to be
    (think of ``textarea`` or ``pre`` tags for instance). Using a third-party
    library like Tidy is probably a better idea.

.. tip::

    For more information on whitespace control, read the
    :ref:`dedicated section <templates-whitespace-control>` of the documentation and learn how
    you can also use the whitespace control modifier on your tags.
