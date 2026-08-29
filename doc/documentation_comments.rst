.. _`twig-documentation-comments`:

Documentation Comments
======================

This feature is **experimental** and can change based on usage and feedback.

Documentation comments describe template constructs and variable bindings.
Tools such as IDEs, static analyzers and documentation generators can read this
metadata through :ref:`node visitors <creating_extensions>`. Documentation
comments do not affect rendering.

Documenting Template Constructs
-------------------------------

A documentation comment before an output statement or a tag describes that
construct:

.. code-block:: twig

    {## Displays the title of the current page. ##}
    {{ page_title }}

    {## Displays the main content of the page. ##}
    {% block content %}
        ...
    {% endblock %}

    {## Renders an HTML input. ##}
    {% macro input(name, value = null) %}
        ...
    {% endmacro %}

Documentation comments start with ``{##``. They can use the regular ``#}``
closing marker or the symmetric ``##}`` marker:

.. code-block:: twig

    {## Uses the regular closing marker. #}
    {% block regular %}{% endblock %}

    {## Uses the symmetric closing marker. ##}
    {% block symmetric %}{% endblock %}

Whitespace control works like it does for regular comments. On the opening
marker, ``-`` or ``~`` comes after ``{##``. With the symmetric closing marker,
it comes before ``##}``:

.. code-block:: twig

    {##- Trims all whitespace before this comment. #}
    {% block opening_trim %}{% endblock %}

    {## Trims all whitespace after this comment. -##}
    {% block closing_trim %}{% endblock %}

Documenting Variable Bindings
-----------------------------

Inside a tag, an inline documentation comment starts with ``##`` and continues
until the end of the line. It describes the variable binding that starts on the
next line.

Type Declarations
~~~~~~~~~~~~~~~~~

Use documentation comments in a :doc:`types <tags/types>` tag to describe the
variables expected by a template:

.. code-block:: twig

    {% types {
        ## Whether the answer is correct.
        is_correct: 'boolean',

        ## The number of points awarded for the answer.
        score?: 'number',
    } %}

Assignments
~~~~~~~~~~~

Documentation comments can describe variables assigned by the :doc:`set
<tags/set>` tag:

.. code-block:: twig

    {% set
        ## The number of unread messages.
        unread_count = messages|filter(message => not message.read)|length
    %}

Each target in a multiple assignment can have its own documentation:

.. code-block:: twig

    {% set
        ## The user's given name.
        first_name,
        ## The user's family name.
        last_name
        = user.first_name, user.last_name
    %}

Loop Targets
~~~~~~~~~~~~

Documentation comments can describe the key and value introduced by a
:doc:`for <tags/for>` loop:

.. code-block:: twig

    {% for
        ## The product identifier.
        product_id,
        ## The product for the current iteration.
        product
        in products
    %}
        ...
    {% endfor %}

Macro Arguments
~~~~~~~~~~~~~~~

Documentation comments can describe individual macro arguments:

.. code-block:: twig

    {% macro input(
        ## The HTML field name.
        name,
        ## The initial field value.
        value = null,
    ) %}
        ...
    {% endmacro %}

Attachment Rules
----------------

A documentation comment is considered for the construct or variable binding
that immediately follows it and attaches only when that position is supported.
Consecutive documentation comments are combined and separated by newlines:

.. code-block:: twig

    {## Displays the main content. ##}
    {## The layout renders this block between the header and footer. ##}
    {% block content %}
        ...
    {% endblock %}

Inline documentation comments consume the rest of their line. The documented
construct or variable must therefore start on a later line:

.. code-block:: twig

    {% set ## The current page number.
        page = 1
    %}

Documentation comments are attached on a best-effort basis where Twig can
associate them directly with a construct or declaration. Comments in other
positions remain regular comments and expose no metadata. In particular, they
do not document ordinary variable reads, mapping keys, function arguments,
named call arguments, assignment operators, destructuring assignments, arrow
function arguments or variadic macro arguments.

Reading Documentation from Nodes
--------------------------------

Node visitors can read documentation with ``Node::getDocumentation()``. Twig
automatically attaches documentation before a custom tag to the node returned
by its token parser. If that node is a placeholder, the token parser can call
``Parser::setDocumentationTarget()`` once while parsing the tag to select the
semantic node instead. To support inline documentation, custom token parsers
can pass the corresponding tokens to ``NodeDocumentation::add()``.

The metadata is stored on the semantic node represented by the source:

* output documentation is stored on the ``PrintNode``;
* tag documentation is stored on the node produced by the tag or the target
  selected by its token parser;
* block documentation is stored on the ``BlockNode``;
* macro documentation is stored on the ``MacroNode``;
* type documentation is stored on each ``TypeNode``;
* variable-binding documentation is stored on the node representing its
  assignment target.

Documentation metadata belongs to its node and is not preserved when an
optimization or a node visitor replaces that node.
