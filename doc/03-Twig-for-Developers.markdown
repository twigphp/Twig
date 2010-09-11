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

    $loader = new Twig_Loader_Filesystem('/path/to/templates');
    $twig = new Twig_Environment($loader, array(
      'cache' => '/path/to/compilation_cache',
    ));

This will create a template environment with the default settings and a loader
that looks up the templates in the `/path/to/templates/` folder. Different
loaders are available and you can also write your own if you want to load
templates from a database or other resources.

>**CAUTION**
>Before Twig 0.9.3, the `cache` option did not exist, and the cache directory
>was passed as a second argument of the loader.

-

>**NOTE**
>Notice that the second argument of the environment is an array of options.
>The `cache` option is a compilation cache directory, where Twig caches the
>compiled templates to avoid the parsing phase for sub-sequent requests. It
>is very different from the cache you might want to add for the evaluated
>templates. For such a need, you can use any available PHP cache library.

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

 * `cache`: An absolute path where to store the compiled templates, or false
   to disable caching (which is the default).

 * `auto_reload`: When developing with Twig, it's useful to recompile the
   template whenever the source code changes. If you don't provide a value for
   the `auto_reload` option, it will be determined automatically based on the
   `debug` value.

 * `strict_variables` (new in Twig 0.9.7): If set to `false`, Twig will
   silently ignore invalid variables (variables and or attributes/methods that
   do not exist) and replace them with a `null` value. When set to `true`,
   Twig throws an exception instead (default to `false`).

>**CAUTION**
>Before Twig 0.9.3, the `cache` and `auto_reload` options did not exist. They
>were passed as a second and third arguments of the filesystem loader
>respectively.

Loaders
-------

>**CAUTION**
>This section describes the loaders as implemented in Twig version 0.9.4 and
>above.

Loaders are responsible for loading templates from a resource such as the file
system.

### Compilation Cache

All template loaders can cache the compiled templates on the filesystem for
future reuse. It speeds up Twig a lot as templates are only compiled once;
and the performance boost is even larger if you use a PHP accelerator such as
APC. See the `cache` and `auto_reload` options of `Twig_Environment` above for
more information.

### Built-in Loaders

Here is a list of the built-in loaders Twig provides:

 * `Twig_Loader_Filesystem`: Loads templates from the file system. This loader
   can find templates in folders on the file system and is the preferred way
   to load them.

        [php]
        $loader = new Twig_Loader_Filesystem($templateDir);

   It can also look for templates in an array of directories:

        [php]
        $loader = new Twig_Loader_Filesystem(array($templateDir1, $templateDir2));

   With such a configuration, Twig will first look for templates in
   `$templateDir1` and if they do not exist, it will fallback to look for them
   in the `$templateDir2`.

 * `Twig_Loader_String`: Loads templates from a string. It's a dummy loader as
   you pass it the source code directly.

        [php]
        $loader = new Twig_Loader_String();

 * `Twig_Loader_Array`: Loads a template from a PHP array. It's passed an
   array of strings bound to template names. This loader is useful for unit
   testing.

        [php]
        $loader = new Twig_Loader_Array($templates);

>**TIP**
>When using the `Array` or `String` loaders with a cache mechanism, you should
>know that a new cache key is generated each time a template content "changes"
>(the cache key being the source code of the template). If you don't want to
>see your cache grows out of control, you need to take care of clearing the old
>cache file by yourself.

### Create your own Loader

All loaders implement the `Twig_LoaderInterface`:

    [php]
    interface Twig_LoaderInterface
    {
      /**
       * Gets the source code of a template, given its name.
       *
       * @param  string $name string The name of the template to load
       *
       * @return string The template source code
       */
      public function getSource($name);

      /**
       * Gets the cache key to use for the cache for a given template name.
       *
       * @param  string $name string The name of the template to load
       *
       * @return string The cache key
       */
      public function getCacheKey($name);

      /**
       * Returns true if the template is still fresh.
       *
       * @param string    $name The template name
       * @param timestamp $time The last modification time of the cached template
       */
      public function isFresh($name, $time);
    }

As an example, here is how the built-in `Twig_Loader_String` reads:

    [php]
    class Twig_Loader_String implements Twig_LoaderInterface
    {
      public function getSource($name)
      {
        return $name;
      }

      public function getCacheKey($name)
      {
        return $name;
      }

      public function isFresh($name, $time)
      {
        return false;
      }
    }

The `isFresh()` method must return `true` if the current cached template is
still fresh, given the last modification time, or `false` otherwise.

Using Extensions
----------------

Twig extensions are packages that adds new features to Twig. Using an
extension is as simple as using the `addExtension()` method:

    [php]
    $twig->addExtension(new Twig_Extension_Escaper());

Twig comes bundled with four extensions:

 * *Twig_Extension_Core*: Defines all the core features of Twig and is automatically
   registered when you create a new environment.

 * *Twig_Extension_Escaper*: Adds automatic output-escaping and the possibility to
   escape/unescape blocks of code.

 * *Twig_Extension_Sandbox*: Adds a sandbox mode to the default Twig environment, making it
   safe to evaluated untrusted code.

 * *Twig_Extension_I18n*: Adds internationalization support via the gettext library.

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
     * `macro`
     * `import`
     * `set`

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
     * `in`
     * `range`
     * `cycle`
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

>**WARNING**
>The `autoescape` tag has no effect on included files.

The escaping rules are implemented as follows (it describes the behavior of
Twig 0.9.5 and above):

 * Literals (integers, booleans, arrays, ...) used in the template directly as
   variables or filter arguments are never automatically escaped:

        [twig]
        {{ "Twig<br />" }} {# won't be escaped #}

        {% set text as "Twig<br />" %}
        {{ text }} {# will be escaped #}

 * Escaping is applied before any other filter is applied (the reasoning
   behind this is that filter transformations should be safe, as the filtered
   value and all its arguments are escaped):

        [twig]
        {{ var|nl2br }} {# is equivalent to {{ var|escape|nl2br }} #}

 * The `safe` filter can be used anywhere in the filter chain:

        [twig]
        {{ var|upper|nl2br|safe }} {# is equivalent to {{ var|safe|upper|nl2br }} #}

 * Automatic escaping is applied to filter arguments, except for literals:

        [twig]
        {{ var|foo("bar") }} {# "bar" won't be escaped #}
        {{ var|foo(bar) }} {# bar will be escaped #}
        {{ var|foo(bar|safe) }} {# bar won't be escaped #}

 * Automatic escaping is not applied if one of the filters in the chain has the
   `is_escaper` option set to `true` (this is the case for the built-in
   `escaper`, `safe`, and `urlencode` filters for instance).

### Sandbox Extension

The `sandbox` extension can be used to evaluate untrusted code. Access to
unsafe attributes and methods is prohibited. The sandbox security is managed
by a policy instance. By default, Twig comes with one policy class:
`Twig_Sandbox_SecurityPolicy`. This class allows you to white-list some tags,
filters, properties, and methods:

    [php]
    $tags = array('if');
    $filters = array('upper');
    $methods = array(
      'Article' => array('getTitle', 'getBody'),
    );
    $properties = array(
      'Article' => array('title', 'body),
    );
    $policy = new Twig_Sandbox_SecurityPolicy($tags, $filters, $methods, $properties);

With the previous configuration, the security policy will only allow usage of
the `if` tag, and the `upper` filter. Moreover, the templates will only be
able to call the `getTitle()` and `getBody()` methods on `Article` objects,
and the `title` and `body` public properties. Everything else won't be allowed
and will generate a `Twig_Sandbox_SecurityError` exception.

The policy object is the first argument of the sandbox constructor:

    [php]
    $sandbox = new Twig_Extension_Sandbox($policy);
    $twig->addExtension($sandbox);

By default, the sandbox mode is disabled and should be enabled when including
untrusted template code by using the `sandbox` tag:

    [twig]
    {% sandbox %}
      {% include 'user.html' %}
    {% endsandbox %}

You can sandbox all templates by passing `true` as the second argument of the
extension constructor:

    [php]
    $sandbox = new Twig_Extension_Sandbox($policy, true);

### I18n Extension

The `i18n` extension adds [gettext](http://www.php.net/gettext) support to
Twig. It defines one tag, `trans`.

You need to register this extension before using the `trans` block:

    [php]
    $twig->addExtension(new Twig_Extension_I18n());

Note that you must configure the gettext extension before rendering any
internationalized template. Here is a simple configuration example from the
PHP [documentation](http://fr.php.net/manual/en/function.gettext.php):

    [php]
    // Set language to French
    putenv('LC_ALL=fr_FR');
    setlocale(LC_ALL, 'fr_FR');

    // Specify the location of the translation tables
    bindtextdomain('myAppPhp', 'includes/locale');
    bind_textdomain_codeset('myAppPhp', 'UTF-8');

    // Choose domain
    textdomain('myAppPhp');

>**NOTE**
>The chapter "Twig for Web Designers" contains more information about how to
>use the `trans` block in your templates.

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
