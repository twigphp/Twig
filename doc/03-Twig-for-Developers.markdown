Twig for Developers
===================

This chapter describes the API to Twig and not the template language. It will
be most useful as reference to those implementing the template interface to
the application and not those who are creating Twig templates.

Basics
------

Twig uses a central object called the **environment** (of class
`Twig_Environment`). Instances of this class are used to store the
configuration and extensions, and are used to load templates from the file
system or other locations.

Most applications will create one `Twig_Environment` object on application
initialization and use that to load templates. In some cases it's however
useful to have multiple environments side by side, if different configurations
are in use.

The simplest way to configure Twig to load templates for your application
looks roughly like this:

    [php]
    require_once '/path/to/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();

    $loader = new Twig_Loader_Filesystem('/path/to/templates', '/path/to/cache');
    $twig = new Twig_Environment($loader);

This will create a template environment with the default settings and a loader
that looks up the templates in the `/path/to/templates/` folder. Different
loaders are available and you can also write your own if you want to load
templates from a database or other resources.

To load a template from this environment you just have to call the
`loadTemplate()` method which then returns a `Twig_Template` instance:

    [php]
    $template = $twig->loadTemplate('index.html');

To render the template with some variables, call the `render()` method:

    [php]
    echo $template->render(array('the' => 'variables', 'go' => 'here'));

>**NOTE**
>The `display()` method is a shortcut to output the template directly.

Environment Options
-------------------

When creating a new `Twig_Environment` instance, you can pass an array of
options as the constructor second argument:

    [php]
    $twig = new Twig_Environment($loader, array('debug' => true));

The following options are available:

 * `debug`: When set to `true`, the generated templates have a `__toString()`
   method that you can use to display the generated nodes (default to
   `false`).

 * `trim_blocks`: Mimicks the behavior of PHP by removing the newline that
   follows instructions if present (default to `false`).

 * `charset`: The charset used by the templates (default to `utf-8`).

 * `base_template_class`: The base template class to use for generated
   templates (default to `Twig_Template`).

Loaders
-------

Loaders are responsible for loading templates from a resource such as the file
system.

### Cache

All template loaders can cache the compiled templates on the filesystem for
future reuse. It speeds up Twig a lot as the templates are only compiled once;
and the performance boost is even larger if you use a PHP accelerator such as
APC.

The cache can take three values:

 * `null` (the default): Twig will create a sub-directory under the system
   temp directory to store the compiled templates (not recommended as
   templates from two projects with the same name will share the same cache if
   your projects share the same Twig source code).

 * `false`: disable the compile cache altogether (not recommended).

 * An absolute path where to store the compiled templates.

### Auto-reload

When developing with Twig, it's useful to recompile the template whenever the
source code changes. This is the default behavior for `Twig_Loader_Filesystem`
for instance. In a production environment, you can turn this option off for
better performance:

    [php]
    $loader = new Twig_Loader_Filesystem($templateDir, $cacheDir, false);

### Built-in Loaders

Here a list of the built-in loaders Twig provides:

 * `Twig_Loader_Filesystem`: Loads templates from the file system. This loader
   can find templates in folders on the file system and is the preferred way
   to load them.

       [php]
       $loader = new Twig_Loader_Filesystem($templateDir, $cacheDir, $autoReload);

 * `Twig_Loader_String`: Loads templates from a string. It's a dummy loader as
   you pass it the source code directly.

       [php]
       $loader = new Twig_Loader_String($cacheDir, $autoReload);

 * `Twig_Loader_Array`: Loads a template from a PHP array. It's passed an
   array of strings bound to template names. This loader is useful for unit
   testing.

       [php]
       $loader = new Twig_Loader_Array($templates, $cacheDir);

### Create your own Loader

All loaders implement the `Twig_LoaderInterface`:

    [php]
    interface Twig_LoaderInterface
    {
      /**
       * Loads a template by name.
       *
       * @param  string $name The template name
       *
       * @return string The class name of the compiled template
       */
      public function load($name);
    }

But if you want to create your own loader, you'd better inherit from the
`Twig_Loader` class, which already provides a lot of useful features. In this
case, you just need to implement the `getSource()` method. As an example, here
is how the built-in `Twig_Loader_String` reads:

    [php]
    class Twig_Loader_String extends Twig_Loader
    {
      /**
       * Gets the source code of a template, given its name.
       *
       * @param  string $name string The name of the template to load
       *
       * @return array An array consisting of the source code as the first element,
       *               and the last modification time as the second one
       *               or false if it's not relevant
       */
      public function getSource($name)
      {
        return array($name, false);
      }
    }

The `getSource()` method must return an array of two values:

 * The first one is the template source code;

 * The second one is the last modification time of the template (used by the
   auto-reload feature), or `false` if the loader does not support
   auto-reloading.

Using Extensions
----------------

Twig extensions are packages that adds new features to Twig. Using an
extension is as simple as using the `addExtension()` method:

    [php]
    $twig->addExtension('Escaper');

Twig comes bundled with three extensions:

 * *Core*: Defines all the core features of Twig and is automatically
   registered when you create a new environment.

 * *Escaper*: Adds automatic output-escaping and the possibility to
   escape/unescape blocks of code.

 * *Sandbox*: Adds a sandbox mode to the default Twig environment, making it
   safe to evaluated untrusted code.

Built-in Extensions
-------------------

This section describes the features added by the built-in extensions.

>**TIP**
>Read the chapter about extending Twig to learn how to create your own
>extensions.

### Core Extension

The `core` extension defines all the core features of Twig:

  * Tags:

     * `for`
     * `if`
     * `extends`
     * `include`
     * `block`
     * `parent`
     * `display`
     * `filter`

  * Filters:

     * `date`
     * `format`
     * `even`
     * `odd`
     * `urlencode`
     * `title`
     * `capitalize`
     * `upper`
     * `lower`
     * `striptags`
     * `join`
     * `reverse`
     * `length`
     * `sort`
     * `default`
     * `keys`
     * `items`
     * `escape`
     * `e`

The core extension does not need to be added to the Twig environment, as it is
registered by default.

### Escaper Extension

The `escaper` extension adds automatic output escaping to Twig. It defines a
new tag, `autoescape`, and a new filter, `safe`.

When creating the escaper extension, you can switch on or off the global
output escaping strategy:

    [php]
    $escaper = new Twig_Extension_Escaper(true);
    $twig->addExtension($escaper);

If set to `true`, all variables in templates are escaped, except those using
the `safe` filter:

    [twig]
    {{ article.to_html|safe }}

You can also change the escaping mode locally by using the `autoescape` tag:

    [twig]
    {% autoescape on %}
      {% var %}
      {% var|safe %}     {# var won't be escaped #}
      {% var|escape %}   {# var won't be doubled-escaped #}
    {% endautoescape %}

### Sandbox Extension

The `sandbox` extension can be used to evaluate untrusted code. Access to
unsafe attributes and methods is prohibited. The sandbox security is managed
by a policy instance. By default, Twig comes with one policy class:
`Twig_Sandbox_SecurityPolicy`. This class allows you to white-list some tags,
filters, and methods:

    [php]
    $tags = array('if');
    $filters = array('upper');
    $methods = array(
      'Article' => array('getTitle', 'getBody'),
    );
    $policy = new Twig_Sandbox_SecurityPolicy($tags, $filters, $methods);

With the previous configuration, the security policy will only allow usage of
the `if` tag, and the `upper` filter. Moreover, the templates will only be
able to call the `getTitle()` and `getBody()` methods on `Article` objects.
Everything else won't be allowed and will generate a
`Twig_Sandbox_SecurityError` exception.

The policy object is the first argument of the sandbox constructor:

    [php]
    $sandbox = new Twig_Extension_Sandbox($policy);
    $twig->addExtension($sandbox);

By default, the sandbox mode is disabled and should be enabled when including
untrusted templates:

    [php]
    {% include "user.html" sandboxed %}

You can sandbox all templates by passing `true` as the second argument of the
extension constructor:

    [php]
    $sandbox = new Twig_Extension_Sandbox($policy, true);

Exceptions
----------

Twig can throw exceptions:

 * `Twig_Error`: The base exception for all template errors.

 * `Twig_SyntaxError`: Thrown to tell the user that there is a problem with
   the template syntax.

 * `Twig_RuntimeError`: Thrown when an error occurs at runtime (when a filter
   does not exist for instance).

 * `Twig_Sandbox_SecurityError`: Thrown when an unallowed tag, filter, or
   method is called in a sandboxed template.
