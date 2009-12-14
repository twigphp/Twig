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

Overriding default Filters
--------------------------

If some default core filters do not suit your needs, you can easily override
them by creating your own core extension. Of course, you don't need to copy
and paste the whole core extension code of Twig. Instead, you can just extends
it and override the filter(s) by overriding the `getFilters()` method:

    [php]
    class MyCoreExtension extends Twig_Extension_Core
    {
      public function getFilters()
      {
        return array_merge(
          parent::getFilters(),
          array(
            'date' => array('my_date_format_filter', false)
          )
        );
      }
    }

    function my_date_format_filter($timestamp, $format = 'F j, Y H:i')
    {
      return '...'.twig_date_format_filter($timestamp, $format);
    }

Here, we override the `date` filter with a custom one. Using this new core
extension is as simple as registering the `MyCoreExtension` extension by
calling the `addExtension()` method on the environment instance:

    [php]
    $twig = new Twig_Environment($loader, array('debug' => true, 'cache' => false));
    $twig->addExtension(new MyCoreExtension());

But I can already hear some people wondering how it can work as the Core
extension is loaded by default. That's true, but the trick is that both
extensions share the same unique identifier (`core` - defined in the
`getName()` method). By registering an extension with the same name as an
existing one, you have actually overridden the default one, even if it is
already registered:

    [php]
    $twig->addExtension(new Twig_Extension_Core());
    $twig->addExtension(new MyCoreExtension());
