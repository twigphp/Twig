``import``
==========

Twig supports putting often used code into :doc:`macros<../tags/macro>`. These
macros can go into different templates and get imported from there.

There are two ways to import templates. You can import the complete template
into a variable or request specific macros from it.

Imagine we have a helper module that renders forms (called ``forms.html``):

.. code-block:: jinja

    {% macro input(name, value, type, size) %}
        <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

    {% macro textarea(name, value, rows) %}
        <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

The easiest and most flexible is importing the whole module into a variable.
That way you can access the attributes:

.. code-block:: jinja

    {% import 'forms.html' as forms %}

    <dl>
        <dt>Username</dt>
        <dd>{{ forms.input('username') }}</dd>
        <dt>Password</dt>
        <dd>{{ forms.input('password', null, 'password') }}</dd>
    </dl>
    <p>{{ forms.textarea('comment') }}</p>

Alternatively you can import names from the template into the current
namespace:

.. code-block:: jinja

    {% from 'forms.html' import input as input_field, textarea %}

    <dl>
        <dt>Username</dt>
        <dd>{{ input_field('username') }}</dd>
        <dt>Password</dt>
        <dd>{{ input_field('password', '', 'password') }}</dd>
    </dl>
    <p>{{ textarea('comment') }}</p>

Importing is not needed if the macros and the template are defined in the same
file; use the special ``_self`` variable instead:

.. code-block:: jinja

    {# index.html template #}

    {% macro textarea(name, value, rows) %}
        <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

    <p>{{ _self.textarea('comment') }}</p>

But you can still create an alias by importing from the ``_self`` variable:

.. code-block:: jinja

    {# index.html template #}

    {% macro textarea(name, value, rows) %}
        <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

    {% import _self as forms %}

    <p>{{ forms.textarea('comment') }}</p>

.. seealso:: :doc:`macro<../tags/macro>`, :doc:`from<../tags/from>`
