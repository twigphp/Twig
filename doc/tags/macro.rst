``macro``
=========

Macros are comparable with functions in regular programming languages. They
are useful to put often used HTML idioms into reusable elements to not repeat
yourself.

Here is a small example of a macro that renders a form element:

.. code-block:: jinja

    {% macro input(name, value, type, size) %}
        <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

Macros differs from native PHP functions in a few ways:

* Default argument values are defined by using the ``default`` filter in the
  macro body;

* Arguments of a macro are always optional.

But as PHP functions, macros don't have access to the current template
variables.

.. tip::

    You can pass the whole context as an argument by using the special
    ``_context`` variable.

Macros can be defined in any template, and need to be "imported" before being
used (see the documentation for the :doc:`import<../tags/import>` tag for more
information):

.. code-block:: jinja

    {% import "forms.html" as forms %}

The above ``import`` call imports the "forms.html" file (which can contain only
macros, or a template and some macros), and import the functions as items of
the ``forms`` variable.

The macro can then be called at will:

.. code-block:: jinja

    <p>{{ forms.input('username') }}</p>
    <p>{{ forms.input('password', null, 'password') }}</p>

If macros are defined and used in the same template, you can use the
special ``_self`` variable, without importing them:

.. code-block:: jinja

    <p>{{ _self.input('username') }}</p>

When you want to use a macro in another one from the same file, use the ``_self``
variable:

.. code-block:: jinja

    {% macro input(name, value, type, size) %}
      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

    {% macro wrapped_input(name, value, type, size) %}
        <div class="field">
            {{ _self.input(name, value, type, size) }}
        </div>
    {% endmacro %}

When the macro is defined in another file, you need to import it:

.. code-block:: jinja

    {# forms.html #}

    {% macro input(name, value, type, size) %}
      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

    {# shortcuts.html #}

    {% macro wrapped_input(name, value, type, size) %}
        {% import "forms.html" as forms %}
        <div class="field">
            {{ forms.input(name, value, type, size) }}
        </div>
    {% endmacro %}

.. seealso:: :doc:`from<../tags/from>`, :doc:`import<../tags/import>`
