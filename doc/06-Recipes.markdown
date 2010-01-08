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

Using dynamic Object Properties
-------------------------------

When Twig encounters a variable like `article.title`, it tries to find a
`title` public property in the `article` object.

It also works if the property does not exist but is rather defined dynamically
thanks to the magic `__get()` method; you just need to also implement the
`__isset()` magic method like shown in the following snippet of code:

    [php]
    class Article
    {
      public function __get($name)
      {
        if ('title' == $name)
        {
          return 'The title';
        }

        // throw some kind of error
      }

      public function __isset($name)
      {
        if ('title' == $name)
        {
          return true;
        }

        return false;
      }
    }

Making the Templates aware of the Context
-----------------------------------------

Sometimes, you want to make the templates aware of some "context" of your
application. But by default, the compiled templates are only passed an array
of parameters.

When rendering a template, you can pass your context objects along, but it's
not very practical. There is a better solution.

By default, all compiled templates extends a base class, the built-in
`Twig_Template`. This base class is configurable with the
`base_template_class` option:

    [php]
    $twig = new Twig_Environment($loader, array('base_template_class' => 'ProjectTemplate'));

Now, all templates will automatically extend the custom `ProjectTemplate`
class. Create the `ProjectTemplate` and add some getter/setter methods to
allow communication between your templates and your application:

    [php]
    class ProjectTemplate extends Twig_Template
    {
      protected $context = null;

      public function setContext($context)
      {
        $this->context = $context;
      }

      public function getContext()
      {
        return $this->context;
      }
    }

Now, you can use the setter to inject the context whenever you create a
template, and use the getter from within your custom nodes.

Accessing the parent Context in Nested Loops
--------------------------------------------

Sometimes, when using nested loops, you need to access the parent context. The
parent context is always accessible via the `loop.parent` variable. For
instance, if you have the following template data:

    $data = array(
      'topics' => array(
        'topic1' => array('Message 1 of topic 1', 'Message 2 of topic 1'),
        'topic2' => array('Message 1 of topic 2', 'Message 2 of topic 2'),
      ),
    );

And the following template to display all messages in all topics:

    [twig]
    {% for topic, messages in topics %}
        * {{ loop.index }}: {{ topic }}
      {% for message in messages %}
          - {{ loop.parent.loop.index }}.{{ loop.index }}: {{ message }}
      {% endfor %}
    {% endfor %}

The output will be similar to:

    * 1: topic1
      - 1.1: The message 1 of topic 1
      - 1.2: The message 2 of topic 1
    * 2: topic2
      - 2.1: The message 1 of topic 2
      - 2.2: The message 2 of topic 2

In the inner loop, the `loop.parent` variable is used to access the outer
context. So, the index of the current `topic` defined in the outer for loop is
accessible via the `loop.parent.loop.index` variable.
