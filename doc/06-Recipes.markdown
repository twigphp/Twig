Recipes
=======

Making a Layout conditional
---------------------------

Working with Ajax means that the same content is sometimes displayed as is,
and sometimes decorated with a layout. But as Twig templates are compiled as
PHP classes, wrapping an `extends` tag with an `if` tag does not work:

    [twig]
    {# this does not work #}

    {% if request.ajax %}
      {% extends "base.html" %}
    {% endif %}

    {% block content %}
      This is the content to be displayed.
    {% endblock %}

One way to solve this problem is to have two different templates:

    [twig]
    {# index.html #}
    {% extends "layout.html" %}

    {% block content %}
      {% include "index_for_ajax.html" %}
    {% endblock %}


    {# index_for_ajax.html #}
    This is the content to be displayed.

Now, the decision to display one of the template is the responsibility of the
controller:

    [php]
    $twig->render($request->isAjax() ? 'index_for_ajax.html' : 'index.html');

Making an Include dynamic
-------------------------

When including a template, its name does not need to be a string. For
instance, the name can depend on the value of a variable:

    [twig]
    {% include var ~ '_foo.html' %}

If `var` evaluates to `index`, the `index_foo.html` template will be
rendered.

As a matter of fact, the template name can be any valid expression, such as
the following:

    [twig]
    {% include var|default('index') ~ '_foo.html' %}

Customizing the Syntax
----------------------

Twig allows some syntax customization for the block delimiters. It's not
recommended to use this feature as templates will be tied with your custom
syntax. But for specific projects, it can make sense to change the defaults.

To change the block delimiters, you need to create your own lexer object:

    [php]
    $twig = new Twig_Environment();

    $lexer = new Twig_Lexer($twig, array(
      'tag_comment'  => array('{#', '#}'),
      'tag_block'    => array('{%', '%}'),
      'tag_variable' => array('{{', '}}'),
    ));
    $twig->setLexer($lexer);

Here are some configuration example that simulates some other template engines
syntax:

    [php]
    // Ruby erb syntax
    $lexer = new Twig_Lexer($twig, array(
      'tag_comment'  => array('<%#', '%>'),
      'tag_block'    => array('<%', '%>'),
      'tag_variable' => array('<%=', '%>'),
    ));

    // SGML Comment Syntax
    $lexer = new Twig_Lexer($twig, array(
      'tag_comment'  => array('<!--#', '-->'),
      'tag_block'    => array('<!--', '-->'),
      'tag_variable' => array('${', '}'),
    ));

    // Smarty like
    $lexer = new Twig_Lexer($twig, array(
      'tag_comment'  => array('{*', '*}'),
      'tag_block'    => array('{', '}'),
      'tag_variable' => array('{$', '}'),
    ));
