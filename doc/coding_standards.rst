Coding Standards
================

.. note::

    The `Twig CS fixer tool <https://github.com/VincentLanglet/Twig-CS-Fixer>`_
    uses the coding standards described in this document to automatically fix
    your templates.

When writing Twig templates, we recommend you to follow these official coding
standards:

* Put exactly one space after the start of a delimiter (``{{``, ``{%``,
  and ``{#``) and before the end of a delimiter (``}}``, ``%}``, and ``#}``)
  if the content is non empty:

  .. code-block:: twig

    {{ user }}
    {# comment #} {##}
    {% if user %}{% endif %}

  When using the whitespace control character, do not put any spaces between
  it and the delimiter:

  .. code-block:: twig

    {{- user -}}
    {#- comment -#} {#--#}
    {%- if user -%}{%- endif -%}

* Put exactly one space before and after the following operators:
  comparison operators (``==``, ``!=``, ``<``, ``>``, ``>=``, ``<=``), math
  operators (``+``, ``-``, ``/``, ``*``, ``%``, ``//``, ``**``), logic
  operators (``not``, ``and``, ``or``), ``~``, ``is``, ``in``, and the ternary
  operator (``?:``):

  .. code-block:: twig

    {{ 1 + 2 }}
    {{ first_name ~ ' ' ~ last_name }}
    {{ is_correct ? true : false }}

* Put exactly one space after the ``:`` sign in mappings and ``,`` in sequences
  and mappings:

  .. code-block:: twig

    [1, 2, 3]
    {'name': 'Fabien'}

* Do not put any spaces after an opening parenthesis and before a closing
  parenthesis in expressions:

  .. code-block:: twig

    {{ 1 + (2 * 3) }}

* Do not put any spaces before and after string delimiters:

  .. code-block:: twig

    {{ 'Twig' }}
    {{ "Twig" }}

* Do not put any spaces before and after the following operators: ``|``,
  ``.``, ``..``, ``[]``:

  .. code-block:: twig

    {{ name|upper|lower }}
    {{ user.name }}
    {{ user[name] }}
    {% for i in 1..12 %}{% endfor %}

* Do not put any spaces before and after the parenthesis used for filter and
  function calls:

  .. code-block:: twig

    {{ name|default('Fabien') }}
    {{ range(1..10) }}

* Do not put any spaces before and after the opening and the closing of
  sequences and mappings:

  .. code-block:: twig

    [1, 2, 3]
    {'name': 'Fabien'}

* Put exactly one space before and after ``=`` in macro argument declarations:

  .. code-block:: twig

    {% macro html_input(class = "input") %}

* Put exactly one space after the ``:`` sign when using named arguments:

  .. code-block:: twig

    {{ html_input(class: "input") }}

* Use snake case for all variable names (provided by the application and
  created in templates), function/filter/test names, argument names and named
  arguments:

  .. code-block:: twig

    {% set name = 'Fabien' %}
    {% set first_name = 'Fabien' %}

    {{ 'Fabien Potencier'|to_lower_case }}
    {{ generate_random_number() }}

    {% macro html_input(class_name) %}

    {{ html_input(class_name: 'pwd') }}

* Indent your code inside tags (use the same indentation as the one used for
  the target language of the rendered template):

  .. code-block:: twig

    {% block content %}
        {% if true %}
            true
        {% endif %}
    {% endblock %}

* Use ``:`` instead of ``=`` to separate argument names and values:

  .. code-block:: twig

    {{ data|convert_encoding(from: 'iso-2022-jp', to: 'UTF-8') }}
