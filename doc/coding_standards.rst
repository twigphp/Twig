Coding Standards
================

.. note::

    The `Twig CS fixer tool <https://github.com/VincentLanglet/Twig-CS-Fixer>`_
    uses the coding standards described in this document to automatically fix
    your templates.

When writing Twig templates, we recommend you to follow these official coding
standards:

* Put exactly one space after the start of a delimiter (``{{``, ``{%``,
  and ``{#``) and before the end of a delimiter (``}}``, ``%}``, and ``#}``):

  .. code-block:: twig

    {{ foo }}
    {# comment #}
    {% if foo %}{% endif %}

  When using the whitespace control character, do not put any spaces between
  it and the delimiter:

  .. code-block:: twig

    {{- foo -}}
    {#- comment -#}
    {%- if foo -%}{%- endif -%}

* Put exactly one space before and after the following operators:
  comparison operators (``==``, ``!=``, ``<``, ``>``, ``>=``, ``<=``), math
  operators (``+``, ``-``, ``/``, ``*``, ``%``, ``//``, ``**``), logic
  operators (``not``, ``and``, ``or``), ``~``, ``is``, ``in``, and the ternary
  operator (``?:``):

  .. code-block:: twig

     {{ 1 + 2 }}
     {{ foo ~ bar }}
     {{ true ? true : false }}

* Put exactly one space after the ``:`` sign in mappings and ``,`` in sequences
  and mappings:

  .. code-block:: twig

     {{ [1, 2, 3] }}
     {{ {'foo': 'bar'} }}

* Do not put any spaces after an opening parenthesis and before a closing
  parenthesis in expressions:

  .. code-block:: twig

    {{ 1 + (2 * 3) }}

* Do not put any spaces before and after string delimiters:

  .. code-block:: twig

    {{ 'foo' }}
    {{ "foo" }}

* Do not put any spaces before and after the following operators: ``|``,
  ``.``, ``..``, ``[]``:

  .. code-block:: twig

    {{ foo|upper|lower }}
    {{ user.name }}
    {{ user[name] }}
    {% for i in 1..12 %}{% endfor %}

* Do not put any spaces before and after the parenthesis used for filter and
  function calls:

  .. code-block:: twig

     {{ foo|default('foo') }}
     {{ range(1..10) }}

* Do not put any spaces before and after the opening and the closing of
  sequences and mappings:

  .. code-block:: twig

     {{ [1, 2, 3] }}
     {{ {'foo': 'bar'} }}

* Use lower cased and underscored variable names:

  .. code-block:: twig

     {% set foo = 'foo' %}
     {% set foo_bar = 'foo' %}

* Indent your code inside tags (use the same indentation as the one used for
  the target language of the rendered template):

  .. code-block:: twig

     {% block foo %}
         {% if true %}
             true
         {% endif %}
     {% endblock %}
