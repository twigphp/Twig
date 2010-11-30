Twig for Template Designers
===========================

This document describes the syntax and semantics of the template engine and
will be most useful as reference to those creating Twig templates.

Synopsis
--------

A template is simply a text file. It can generate any text-based format (HTML,
XML, CSV, LaTeX, etc.). It doesn't have a specific extension, `.html` or
`.xml` are just fine.

A template contains **variables** or **expressions**, which get replaced with
values when the template is evaluated, and tags, which control the logic of
the template.

Below is a minimal template that illustrates a few basics. We will cover the
details later in that document:

    [twig]
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
    <html lang="en">
      <head>
        <title>My Webpage</title>
      </head>
      <body>
        <ul id="navigation">
        {% for item in navigation %}
          <li><a href="{{ item.href }}">{{ item.caption }}</a></li>
        {% endfor %}
        </ul>

        <h1>My Webpage</h1>
        {{ a_variable }}
      </body>
    </html>

There are two kinds of delimiters: `{% ... %}` and `{{ ... }}`. The first one
is used to execute statements such as for-loops, the latter prints the result
of an expression to the template.

IDEs Integration
----------------

Modern IDEs support syntax highlighting and auto-completion for a large range
of languages. As Twig syntax is quite similar to Jinja and Django templates,
IDEs that support these two Python templating systems should also support
Twig.

If you use Textmate, you can use the
[Jinja](http://jinja.pocoo.org/2/documentation/integration) bundle or the
[Django](http://code.djangoproject.com/wiki/TextMate) one.

If you use Vim, you can use the
[Jinja](http://jinja.pocoo.org/2/documentation/integration) syntax plugin.

Variables
---------

The application passes variables to the templates you can mess around in the
template. Variables may have attributes or elements on them you can access
too. How a variable looks like, heavily depends on the application providing
those.

You can use a dot (`.`) to access attributes of a variable, alternative the
so-called "subscript" syntax (`[]`) can be used. The following lines do the
same:

    [twig]
    {{ foo.bar }}
    {{ foo['bar'] }}

>**NOTE**
>It's important to know that the curly braces are *not* part of the variable
>but the print statement. If you access variables inside tags don't put the
>braces around.

If a variable or attribute does not exist you will get back a `null` value
(which can be tested with the `none` expression).

>**SIDEBAR**
>Implementation
>
>For convenience sake `foo.bar` does the following things on
>the PHP layer:
>
> * check if `foo` is an array and `bar` a valid element;
> * if not, and if `foo` is an object, check that `bar` is a valid property;
> * if not, and if `foo` is an object, check that `bar` is a valid method
>   (even if `bar` is the constructor - use `__construct()` instead);
> * if not, and if `foo` is an object, check that `getBar` is a valid method;
> * if not, and if `foo` is an object, check that `isBar` is a valid method (as of Twig 0.9.9);
> * if not, return a `null` value.
>
>`foo['bar']` on the other hand works mostly the same with the a small
>difference in the order:
>
> * check if `foo` is an array and `bar` a valid element;
> * if not, return a `null` value.
>
>Using the alternative syntax is also useful to dynamically get attributes
>from arrays:
>
>     [twig]
>     foo[bar]

Twig always references the following variables:

 * `_self`: references the current template (was `self` before 0.9.9);
 * `_context`: references the current context;
 * `_charset`: references the current charset (as of 0.9.9).

Filters
-------

Variables can by modified by **filters**. Filters are separated from the
variable by a pipe symbol (`|`) and may have optional arguments in
parentheses. Multiple filters can be chained. The output of one filter is
applied to the next.

`{{ name|striptags|title }}` for example will remove all HTML tags from the
`name` and title-cases it. Filters that accept arguments have parentheses
around the arguments, like a function call. This example will join a list by
commas: `{{ list|join(', ') }}`.

The built-in filters section below describes all the built-in filters.

Tests (new in Twig 0.9.9)
-------------------------

Beside filters, there are also so called "tests" available. Tests can be used
to test a variable against a common expression. To test a variable or
expression you add `is` plus the name of the test after the variable. For
example to find out if a variable is odd, you can do `name is odd` which will
then return `true` or `false` depending on if `name` is odd or not.

Tests can accept arguments too:

    [twig]
    {% if loop.index is divisibleby(3) %}

Tests can be negated by prepending them with `not`:

    [twig]
    {% if not (loop.index is divisibleby(3)) %}

    {# also works with an infix notation #}
    {% if loop.index is not divisibleby(3) %}

The built-in tests section below describes all the built-in tests.

Comments
--------

To comment-out part of a line in a template, use the comment syntax `{# ... #}`.
This is useful to comment out parts of the template for debugging or to
add information for other template designers or yourself:

    [twig]
    {# note: disabled template because we no longer use this
      {% for user in users %}
          ...
      {% endfor %}
    #}

Whitespace Control
------------------

The first newline after a template tag is removed automatically (like in PHP.)
Whitespace is not further modified by the template engine, so each whitespace
(spaces, tabs, newlines etc.) is returned unchanged.

Use the `spaceless` tag to remove whitespace between HTML tags:

    [twig]
    {% spaceless %}
        <div>
            <strong>foo</strong>
        </div>
    {% endspaceless %}

    {# output will be <div><strong>foo</strong></div> #}

Escaping
--------

It is sometimes desirable or even necessary to have Twig ignore parts it would
otherwise handle as variables or blocks. For example if the default syntax is
used and you want to use `{{` as raw string in the template and not start a
variable you have to use a trick.

The easiest way is to output the variable delimiter (`{{`) by using a variable
expression:

    [twig]
    {{ '{{' }}

For bigger sections it makes sense to mark a block `raw`. For example to put
Twig syntax as example into a template you can use this snippet:

    [twig]
    {% raw %}
      <ul>
      {% for item in seq %}
        <li>{{ item }}</li>
      {% endfor %}
      </ul>
    {% endraw %}

Template Inheritance
--------------------

The most powerful part of Twig is template inheritance. Template inheritance
allows you to build a base "skeleton" template that contains all the common
elements of your site and defines **blocks** that child templates can
override.

Sounds complicated but is very basic. It's easiest to understand it by
starting with an example.

### Base Template

This template, which we'll call `base.html`, defines a simple HTML skeleton
document that you might use for a simple two-column page. It's the job of
"child" templates to fill the empty blocks with content:

    [twig]
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
    <html lang="en">
    <head>
      {% block head %}
        <link rel="stylesheet" href="style.css" />
        <title>{% block title %}{% endblock %} - My Webpage</title>
      {% endblock %}
    </head>
    <body>
      <div id="content">{% block content %}{% endblock %}</div>
      <div id="footer">
        {% block footer %}
          &copy; Copyright 2009 by <a href="http://domain.invalid/">you</a>.
        {% endblock %}
      </div>
    </body>
    </html>

In this example, the `{% block %}` tags define four blocks that child
templates can fill in. All the `block` tag does is to tell the template engine
that a child template may override those portions of the template.

### Child Template

A child template might look like this:

    [twig]
    {% extends "base.html" %}

    {% block title %}Index{% endblock %}
    {% block head %}
      {% parent %}
      <style type="text/css">
        .important { color: #336699; }
      </style>
    {% endblock %}
    {% block content %}
      <h1>Index</h1>
      <p class="important">
        Welcome on my awesome homepage.
      </p>
    {% endblock %}

The `{% extends %}` tag is the key here. It tells the template engine that
this template "extends" another template. When the template system evaluates
this template, first it locates the parent. The extends tag should be the
first tag in the template.

The filename of the template depends on the template loader. For example the
`Twig_Loader_Filesystem` allows you to access other templates by giving the
filename. You can access templates in subdirectories with a slash:

    [twig]
    {% extends "layout/default.html" %}

But this behavior can depend on the application embedding Twig. Note that
since the child template doesn't define the `footer` block, the value from the
parent template is used instead.

You can't define multiple `{% block %}` tags with the same name in the same
template. This limitation exists because a block tag works in "both"
directions. That is, a block tag doesn't just provide a hole to fill - it also
defines the content that fills the hole in the *parent*. If there were two
similarly-named `{% block %}` tags in a template, that template's parent
wouldn't know which one of the blocks' content to use.

If you want to print a block multiple times you can however use the `display`
tag:

    [twig]
    <title>{% block title %}{% endblock %}</title>
    <h1>{% display title %}</h1>
    {% block body %}{% endblock %}

Like PHP, Twig does not support multiple inheritance. So you can only have one
extends tag called per rendering.

### Parent Blocks

It's possible to render the contents of the parent block by using the `parent`
tag. This gives back the results of the parent block:

    [twig]
    {% block sidebar %}
      <h3>Table Of Contents</h3>
      ...
      {% parent %}
    {% endblock %}

### Named Block End-Tags

Twig allows you to put the name of the block after the end tag for better
readability:

    [twig]
    {% block sidebar %}
      {% block inner_sidebar %}
          ...
      {% endblock inner_sidebar %}
    {% endblock sidebar %}

However the name after the `endblock` word must match the block name.

### Block Nesting and Scope

Blocks can be nested for more complex layouts. Per default, blocks have access
to variables from outer scopes:

    [twig]
    {% for item in seq %}
      <li>{% block loop_item %}{{ item }}{% endblock %}</li>
    {% endfor %}

### Block Shortcuts

For blocks with few content, it's possible to have a shortcut syntax. The
following constructs do the same:

    [twig]
    {% block title %}
      {{ page_title|title }}
    {% endblock %}

-

    [twig]
    {% block title page_title|title %}

### Dynamic Inheritance (as of Twig 0.9.7)

Twig supports dynamic inheritance by using a variable as the base template:

    [twig]
    {% extends some_var %}

If the variable evaluates to a `Twig_Template` object, Twig will use it as the
parent template:

    // {% extends layout %}

    $layout = $twig->loadTemplate('some_layout_template.twig');

    $twig->display('template.twig', array('layout' => $layout));

### Conditional Inheritance (as of Twig 0.9.7)

As a matter of fact, the template name can be any valid expression. So, it's
also possible to make the inheritance mechanism conditional:

    [twig]
    {% extends standalone ? "minimum.html" : "base.html" %}

In this example, the template will extend the "minimum.html" layout template
if the `standalone` variable evaluates to `true`, and "base.html" otherwise.

Import Context Behavior
-----------------------

Per default included templates are passed the current context.

The context that is passed to the included template includes variables defined
in the template:

    [twig]
    {% for box in boxes %}
      {% include "render_box.html" %}
    {% endfor %}

The included template `render_box.html` is able to access `box`.

HTML Escaping
-------------

When generating HTML from templates, there's always a risk that a variable
will include characters that affect the resulting HTML. There are two
approaches: manually escaping each variable or automatically escaping
everything by default.

Twig supports both, but what is used depends on the application configuration.
The default configuration is no automatic escaping for various reasons:

 * Escaping everything except of safe values will also mean that Twig is
   escaping variables known to not include HTML such as numbers which is a
   huge performance hit.

 * The information about the safety of a variable is very fragile. It could
   happen that by coercing safe and unsafe values the return value is double
   escaped HTML.

>**NOTE**
>Escaping is only supported if the *escaper* extension has been enabled.

### Working with Manual Escaping

If manual escaping is enabled it's **your** responsibility to escape variables
if needed. What to escape? If you have a variable that *may* include any of
the following chars (`>`, `<`, `&`, or `"`) you **have to** escape it unless
the variable contains well-formed and trusted HTML. Escaping works by piping
the variable through the `|e` filter: `{{ user.username|e }}`.

### Working with Automatic Escaping

Automatic escaping is enabled when the `escaper` extension has been enabled.

Whether automatic escaping is enabled or not, you can mark a section of a
template to be escaped or not by using the `autoescape` tag:

    [twig]
    {% autoescape on %}
      Everything will be automatically escaped in this block
    {% endautoescape %}

    {% autoescape off %}
      Everything will be outputed as is in this block
    {% endautoescape %}

    {% autoescape on js %}
      Everything will be automatically escaped in this block
      using the js escaping strategy
    {% endautoescape %}

When automatic escaping is enabled everything is escaped by default except for
values explicitly marked as safe. Those can be marked in the template by using
the `|raw` filter.

Functions returning template data (like macros and `parent`) always return
safe markup.

>**NOTE**
>Twig is smart enough to not escape an already escaped value by the `escape`
>filter.

-

>**NOTE**
>The chapter for developers give more information about when and how
>automatic escaping is applied.

List of Control Structures
--------------------------

A control structure refers to all those things that control the flow of a
program - conditionals (i.e. `if`/`elseif`/`else`), `for`-loops, as well as
things like blocks. Control structures appear inside `{% ... %}` blocks.

### For

Loop over each item in a sequence. For example, to display a list of users
provided in a variable called `users`:

    [twig]
    <h1>Members</h1>
    <ul>
      {% for user in users %}
        <li>{{ user.username|e }}</li>
      {% endfor %}
    </ul>

>**NOTE**
>A sequence can be either an array or an object implementing the `Iterator`
>interface.

If you do need to iterate over a sequence of numbers, you can use the `..`
operator (as of Twig 0.9.5):

    [twig]
    {% for i in 0..10 %}
      * {{ i }}
    {% endfor %}

The above snippet of code would print all numbers from 0 to 10.

It can be also useful with letters:

    [twig]
    {% for letter in 'a'..'z' %}
      * {{ letter }}
    {% endfor %}

The `..` operator can take any expression at both sides:

    [twig]
    {% for letter in 'a'|upper..'z'|upper %}
      * {{ letter }}
    {% endfor %}

If you need a step different from 1, you can use the `range` filter instead:

    [twig]
    {% for i in 0|range(10, 2) %}
      * {{ i }}
    {% endfor %}

Inside of a `for` loop block you can access some special variables (if you
don't need them, you can add `without loop` at the end of the `for` statement
for a small speed boost):

| Variable              | Description
| --------------------- | -------------------------------------------------------------
| `loop.index`          | The current iteration of the loop. (1 indexed)
| `loop.index0`         | The current iteration of the loop. (0 indexed)
| `loop.revindex`       | The number of iterations from the end of the loop (1 indexed)
| `loop.revindex0`      | The number of iterations from the end of the loop (0 indexed)
| `loop.first`          | True if first iteration
| `loop.last`           | True if last iteration
| `loop.length`         | The number of items in the sequence
| `loop.parent`         | The parent context

>**NOTE**
>The `loop.length`, `loop.revindex`, `loop.revindex0`, and `loop.last`
>variables are only available for PHP arrays, or objects that implement the
>`Countable` interface (as of Twig 0.9.7).

-

>**NOTE**
>Unlike in PHP it's not possible to `break` or `continue` in a loop.

If no iteration took place because the sequence was empty, you can render a
replacement block by using `else`:

    [twig]
    <ul>
      {% for user in users %}
        <li>{{ user.username|e }}</li>
      {% else %}
        <li><em>no user found</em></li>
      {% endfor %}
    </ul>

By default, a loop iterates over the values of the sequence. You can iterate
on keys by using the `keys` filter:

    [twig]
    <h1>Members</h1>
    <ul>
      {% for key in users|keys %}
        <li>{{ key }}</li>
      {% endfor %}
    </ul>

You can also access both keys and values:

    [twig]
    <h1>Members</h1>
    <ul>
      {% for key, user in users %}
        <li>{{ key }}: {{ user.username|e }}</li>
      {% endfor %}
    </ul>

>**NOTE**
>On Twig before 0.9.3, you need to use the `items` filter to access both the
>keys and values (`{% for key, value in users|items %}`).

To conveniently display comma-separated lists or things alike, you can use
`joined by ", "` at the end of the loop statement (as of Twig 0.9.10). Of
course this is not limited to commas:

    [twig]
    <h1>Members</h1>
    <p>{% for user in users joined by ", " %}{{ user }}{% endfor %}</p>

    {# generates a string like <p>Fabien, Jordi, Thomas, Lucas</p> #}

>**NOTE**
>This way you don't have to check if the item is the last one, the comma will
>only be added in between two items.

### If

The `if` statement in Twig is comparable with the if statements of PHP. In the
simplest form you can use it to test if a variable is defined, not empty or
not false:

    [twig]
    {% if users %}
      <ul>
        {% for user in users %}
          <li>{{ user.username|e }}</li>
        {% endfor %}
      </ul>
    {% endif %}

For multiple branches `elseif` and `else` can be used like in PHP. You can use
more complex `expressions` there too:

    {% if kenny.sick %}
      Kenny is sick.
    {% elseif kenny.dead %}
      You killed Kenny!  You bastard!!!
    {% else %}
      Kenny looks okay --- so far
    {% endif %}

### Macros

Macros are comparable with functions in regular programming languages. They
are useful to put often used HTML idioms into reusable elements to not repeat
yourself.

Here is a small example of a macro that renders a form element:

    [twig]
    {% macro input(name, value, type, size) %}
      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

Macros differs from native PHP functions in a few ways:

 * Default argument values are defined by using the `default` filter in the
   macro body;

 * Arguments of a macro are always optional.

But as PHP functions, macros don't have access to the current template
variables.

>**TIP**
>You can pass the whole context as an argument by using the special `_context`
>variable.

Macros can be defined in any template, and need to be "imported" before being
used (see the Import section for more information):

    [twig]
    {% import "forms.html" as forms %}

The above `import` call imports the "forms.html" file (which can contain only
macros, or a template and some macros), and import the functions as items of
the `forms` variable.

The macro can then be called at will:

    [twig]
    <p>{{ forms.input('username') }}</p>
    <p>{{ forms.input('password', none, 'password') }}</p>

If macros are defined and used in the same template, you can use the
special `_self` variable, without importing them:

    [twig]
    <p>{{ _self.input('username') }}</p>

When you want to use a macro in another one from the same file, use the `_self`
variable:

    [twig]
    {% macro input(name, value, type, size) %}
      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

    {% macro wrapped_input(name, value, type, size) %}
        <div class="field">
            {{ _self.input(name, value, type, size) }}
        </div>
    {% endmacro %}

When the macro is defined in another file, you need to import it:

    [twig]
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

### Filters

Filter sections allow you to apply regular Twig filters on a block of template
data. Just wrap the code in the special `filter` section:

    [twig]
    {% filter upper %}
      This text becomes uppercase
    {% endfilter %}

You can also chain filters:

    [twig]
    {% filter lower|escape %}
      <strong>SOME TEXT</strong>
    {% endfilter %}

It should returns `&lt;strong&gt;some text&lt;/strong&gt;`.

### Assignments

Inside code blocks you can also assign values to variables. Assignments use
the `set` tag and can have multiple targets:

    [twig]
    {% set foo = 'foo' %}

    {% set foo = [1, 2] %}

    {% set foo = ['foo': 'bar'] %}

    {% set foo = 'foo' ~ 'bar' %}

    {% set foo, bar = 'foo', 'bar' %}

The `set` tag can also be used to 'capture' chunks of HTML (new in Twig
0.9.6):

    [twig]
    {% set foo %}
      <div id="pagination">
        ...
      </div>
    {% endset %}

### Extends

The `extends` tag can be used to extend a template from another one. You can
have multiple of them in a file but only one of them may be executed at the
time. There is no support for multiple inheritance. See the section about
Template inheritance above for more information.

### Block

Blocks are used for inheritance and act as placeholders and replacements at
the same time. They are documented in detail as part of the section about
Template inheritance above.

### Include

The `include` statement is useful to include a template and return the
rendered content of that file into the current namespace:

    [twig]
    {% include 'header.html' %}
      Body
    {% include 'footer.html' %}

Included templates have access to the variables of the active context.

You can add additional variables by passing them after the `with` keyword:

    [twig]
    {# the foo template will have access to the variables from the current context and the foo one #}
    {% include 'foo' with ['foo': 'bar'] %}

    {% set vars = ['foo': 'bar'] %}
    {% include 'foo' with vars %}

You can disable access to the context by appending the `only` keyword:

    [twig]
    {# only the foo variable will be accessible #}
    {% include 'foo' with ['foo': 'bar'] only %}

    [twig]
    {# no variable will be accessible #}
    {% include 'foo' only %}

>**NOTE**
>The `with` keyword is supported as of Twig 0.9.5.
>The `only` keyword is supported as of Twig 0.9.9.

-

>**TIP**
>When including a template created by an end user, you should consider
>sandboxing it. More information in the "Twig for Developers" chapter.

The template name can be any valid Twig expression:

    [twig]
    {% include some_var %}
    {% include ajax ? 'ajax.html' : 'not_ajax.html' %}

And if the variable evaluates to a `Twig_Template` object, Twig will use it
directly:

    // {% include template %}

    $template = $twig->loadTemplate('some_template.twig');

    $twig->display('template.twig', array('template' => $template));

### Import

Twig supports putting often used code into macros. These macros can go into
different templates and get imported from there.

Imagine we have a helper module that renders forms (called `forms.html`):

    [twig]
    {% macro input(name, value, type, size) %}
      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
    {% endmacro %}

    {% macro textarea(name, value, rows) %}
      <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

Importing these macros in a template is as easy as using the `import` tag:

    [twig]
    {% import 'forms.html' as forms %}

    <dl>
      <dt>Username</dt>
      <dd>{{ forms.input('username') }}</dd>
      <dt>Password</dt>
      <dd>{{ forms.input('password', none, 'password') }}</dd>
    </dl>
    <p>{{ forms.textarea('comment') }}</p>

Importing is not needed if the macros and the template are defined in the file;
use the special `_self` variable instead:

    [twig]
    {# index.html template #}

    {% macro textarea(name, value, rows) %}
      <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

    <p>{{ _self.textarea('comment') }}</p>

But you can still create an alias by importing from the `_self` variable:

    [twig]
    {# index.html template #}

    {% macro textarea(name, value, rows) %}
      <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

    {% import _self as forms %}

    <p>{{ forms.textarea('comment') }}</p>

### Internationalization (new in Twig 0.9.6)

When the `i18n` extension is enabled, use the `trans` block to mark parts in
the template as translatable:

    [twig]
    {% trans "Hello World!" %}

    {% trans string_var %}

    {% trans %}
        Hello World!
    {% endtrans %}

>**CAUTION**
>The `I18n` extension only works if the PHP
>[gettext](http://www.php.net/gettext) extension is enabled.

In a translatable string, you can embed variables:

    [twig]
    {% trans %}
        Hello {{ name }}!
    {% endtrans %}

>**NOTE**
>`{% trans "Hello {{ name }}!" %}` is not a valid statement.

If you need to apply filters to the variables, you first need to assign the
result to a variable:

    [twig]
    {% set name = name|capitalize %}

    {% trans %}
        Hello {{ name }}!
    {% endtrans %}

To pluralize a translatable string, use the `plural` block:

    [twig]
    {% trans %}
        Hey {{ name }}, I have one apple.
    {% plural apple_count %}
        Hey {{ name }}, I have {{ count }} apples.
    {% endtrans %}

The `plural` tag should provide the `count` used to select the right string.
Within the translatable string, the special `count` variable always contain
the count value (here the value of `apple_count`).

Within an expression or in a tag, you can use the `trans` filter to translate
simple strings or variables (new in Twig 0.9.9):

    [twig]
    {{ var|default(default_value|trans) }}

Expressions
-----------

Twig allows basic expressions everywhere. These work very similar to regular
PHP and even if you're not working with PHP you should feel comfortable with
it.

The operator precedence is as follows, with the lowest-precedence operators
listed first: `or`, `and`, `==`, `!=`, `<`, `>`, `>=`, `<=`, `in`, `+`, `-`,
`~`, `*`, `/`, `%`, `//`, `not`, and `[`.

>**CAUTION**
>When compiling deep-nested arrays or math expressions with Xdebug enabled,
>Twig can easily reach the default maximum nesting level set by Xdebug via the
>`xdebug.max_nesting_level` setting; changing the default (100) to a bigger
>value solves the issue.

### Literals

The simplest form of expressions are literals. Literals are representations
for PHP types such as strings, numbers, and arrays. The following literals
exist:

 * `"Hello World"`: Everything between two double or single quotes is a
   string. They are useful whenever you need a string in the template (for
   example as arguments to function calls, filters or just to extend or
   include a template).

 * `42` / `42.23`: Integers and floating point numbers are created by just
   writing the number down. If a dot is present the number is a float,
   otherwise an integer.

 * `[foo, bar]` (new in Twig 0.9.5): Arrays are defined by a sequence of
   expressions separated by a comma (`,`) and wrapped with squared brackets
   (`[]`). As an array element can be any valid expression, arrays can be
   nested. Like PHP, arrays can also have named items (hashes) like `['foo':
   'foo', 'bar': 'bar']`. You can even mix and match both syntaxes: `['foo':
   'foo', 'bar']`.

 * `true` / `false`: `true` represents the true value, `false`
   represents the false value.

 * `none`: `none` represents no specific value (the equivalent of `null` in
   PHP). This is the value returned when a variable does not exist.

### Math

Twig allows you to calculate with values. This is rarely useful in templates
but exists for completeness' sake. The following operators are supported:

 * `+`: Adds two objects together (the operands are casted to numbers).
   `{{ 1 + 1 }}` is `2`.

 * `-`: Substracts the second number from the first one. `{{ 3 - 2 }}` is `1`.

 * `/`: Divides two numbers. The return value will be a floating point number.
   `{{ 1 / 2 }}` is `{{ 0.5 }}`.

 * `%`: Calculates the remainder of an integer division. `{{ 11 % 7 }}` is `4`.

 * `//`: Divides two numbers and returns the truncated integer result. `{{ 20 //
   7 }}` is `2`.

 * `*`: Multiplies the left operand with the right one. `{{ 2 * 2 }}` would
   return `4`.

 * `**`: Raises the left operand to the power of the right operand. `{{ 2**3
   }}` would return `8`.

### Logic

For `if` statements, `for` filtering or `if` expressions it can be useful to
combine multiple expressions:

 * `and`: Returns true if the left and the right operands are both true.

 * `or`: Returns true if the left or the right operand is true.

 * `not`: Negates a statement.

 * `(expr)`: Groups an expression.

>**NOTE**
>The `is` and `in` operators support negation using an infix notation too: `foo
>is not bar` and `foo not in bar` instead of `not (foo is bar)` and `not (foo
>in bar)`. All other expressions require a prefix notation: `not (foo and
>bar)`.

### Comparisons

The following comparison operators are supported in any expression: `==`,
`!=`, `<`, `>`, `>=`, and `<=`.

### Other Operators

The following operators are very useful but don't fit into any of the other
two categories:

 * `in` (new in Twig 0.9.5): Performs containment test. Returns `true` if the
   left operand is contained in the right. `{{ 1 in [1, 2, 3] }}` would for
   example return `true`. To perform a negative test, the whole expression
   should be prefixed with `not` (`{{ not (1 in [1, 2, 3]) }}` would return
   `false` - can also be written `{{ 1 not in [1, 2, 3] }}`).

 * `..` (new in Twig 0.9.5): Creates a sequence based on the operand before
   and after the operator (see the `for` tag for some usage examples).

 * `|`: Applies a filter.

 * `~`: Converts all operands into strings and concatenates them. `{{ "Hello "
   ~ name ~ "!" }}` would return (assuming `name` is `'John'`) `Hello John!`.

 * `.`, `[]`: Gets an attribute of an object.

 * `?:`: Twig supports the PHP ternary operator:

        [twig]
        {{ foo ? 'yes' : 'no' }}

List of built-in Filters
------------------------

### `date`

The `date` filter is able to format a date to a given format:

    [twig]
    {{ post.published_at|date("m/d/Y") }}

The `date` filter accepts any date format supported by
[`DateTime`](http://www.php.net/manual/en/datetime.construct.php) and
`DateTime` instances. For instance, to display the current date, filter the
word "now":

    [twig]
    {{ "now"|date("m/d/Y") }}

### `format`

The `format` filter formats a given string by replacing the placeholders
(placeholders follows the `printf` notation):

    [twig]
    {# string is a format string like: I like %s and %s. #}
    {{ string|format(foo, "bar") }}
    {# returns I like foo and bar. (if the foo parameter equals to the foo string) #}

### `replace` (new in Twig 0.9.9)

The `replace` filter formats a given string by replacing the placeholders
(placeholders are free-form):

    [twig]
    {# string is a format string like: I like %this% and %that%. #}
    {{ string|replace(['%this%': foo, '%that%': "bar"]) }}
    {# returns I like foo and bar. (if the foo parameter equals to the foo string) #}

### `cycle`

The `cycle` filter can be used to cycle between an array of values:

    [twig]
    {% for i in 0..10 %}
      {{ ['odd', 'even']|cycle(i) }}
    {% endfor %}

The array can contain any number of values:

    [twig]
    {% set fruits = ['apple', 'orange', 'citrus'] %}

    {% for i in 0..10 %}
      {{ fruits|cycle(i) }}
    {% endfor %}

### `url_encode`

The `url_encode` filter URL encodes a given string.

### `json_encode`

The `json_encode` filter returns the JSON representation of a string.

### `title`

The `title` filter returns a titlecased version of the value. I.e. words will
start with uppercase letters, all remaining characters are lowercase.

### `capitalize`

The `capitalize` filter capitalizes a value. The first character will be
uppercase, all others lowercase.

### `upper`

The `upper` filter converts a value to uppercase.

### `lower`

The `lower` filter converts a value to lowercase.

### `striptags`

The `striptags` filter strips SGML/XML tags and replace adjacent whitespace by
one space.

### `join`

The `join` filter returns a string which is the concatenation of the strings
in the sequence. The separator between elements is an empty string per
default, you can define it with the optional parameter:

    [twig]
    {{ [1, 2, 3]|join('|') }}
    {# returns 1|2|3 #}

    {{ [1, 2, 3]|join }}
    {# returns 123 #}

### `reverse`

The `reverse` filter reverses an array or an object if it implements the
`Iterator` interface.

### `length`

The `length` filters returns the number of items of a sequence or mapping, or
the length of a string.

### `sort`

The `sort` filter sorts an array.

### `in` (new in Twig 0.9.5)

Returns true if the value is contained within another one.

    [twig]
    {# returns true #}

    {{ 1|in([1, 2, 3]) }}

    {{ 'cd'|in('abcde') }}

You can use this filter to perform a containment test on strings, arrays, or
objects implementing the `Traversable` interface.

The `in` operator is a syntactic sugar for the `in` filter:

    [twig]
    {% if 1 in [1, 2, 3] %}
      TRUE
    {% endif %}

    {# is equivalent to #}

    {% if 1|in([1, 2, 3]) %}
      TRUE
    {% endif %}

You can negate an `in` expression with `not`:

    [twig]
    {% if not (1 in [1, 2, 3]) %}

    {# also works with an infix notation #}
    {% if 1 not in [1, 2, 3] %}

### `range` (new in Twig 0.9.5)

Returns a list containing a sequence of numbers. The left side of the filter
represents the low value. The first argument of the filter is mandatory and
represents the high value. The second argument is optional and represents the
step (which defaults to `1`).

If you do need to iterate over a sequence of numbers:

    [twig]
    {% for i in 0|range(10) %}
      * {{ i }}
    {% endfor %}

>**TIP**
>The `range` filter works as the native PHP `range` function.

The `..` operator (see above) is a syntactic sugar for the `range` filter
(with a step of 1):

    [twig]
    {% for i in 0|range(10) %}
      * {{ i }}
    {% endfor %}

    {# is equivalent to #}

    {% for i in 0..10 %}
      * {{ i }}
    {% endfor %}

### `default`

The `default` filter returns the passed default value if the value is
undefined, otherwise the value of the variable:

    [twig]
    {{ my_variable|default('my_variable is not defined') }}

### `keys`

The `keys` filter returns the keys of an array. It is useful when you want to
iterate over the keys of an array:

    [twig]
    {% for key in array|keys %}
        ...
    {% endfor %}

### `escape`, `e`

The `escape` filter converts the characters `&`, `<`, `>`, `'`, and `"` in
strings to HTML-safe sequences. Use this if you need to display text that
might contain such characters in HTML.

>**NOTE**
>Internally, `escape` uses the PHP `htmlspecialchars` function.

### `raw`

The `raw` filter marks the value as safe which means that in an environment
with automatic escaping enabled this variable will not be escaped if `raw` is
the last filter applied to it.

    [twig]
    {% autoescape on }
      {{ var|raw }} {# var won't be escaped #}
    {% autoescape off %}

### `constant` (new in Twig 0.9.9)

`constant` returns the constant value for a given string:

    [twig]
    {{ some_date|date('DATE_W3C'|constant) }}

List of built-in Tests (new in Twig 0.9.9)
------------------------------------------

### `divisibleby`

`divisibleby` checks if a variable is divisible by a number:

    [twig]
    {% if loop.index is divisibleby(3) %}

### `none`

`none` returns `true` if the variable is `none`:

    [twig]
    {{ var is none }}

### `even`

`even` returns `true` if the given number is even:

    [twig]
    {{ var is even }}

### `odd`

`odd` returns `true` if the given number is odd:

    [twig]
    {{ var is odd }}

### `sameas`

`sameas` checks if a variable points to the same memory address than another
variable:

    [twig]
    {% if foo.attribute is sameas(false) %}
        the foo attribute really is the `false` PHP value
    {% endif %}

### `constant`

`constant` checks if a variable has the exact same value as a constant. You
can use either global constants or class constants:

    [twig]
    {% if post.status is constant('Post::PUBLISHED') %}
        the status attribute is exactly the same as Post::PUBLISHED
    {% endif %}

### `defined`

`defined` checks if a variable is defined in the current context. This is very
useful if you use the `strict_variables` option:

    [twig]
    {# defined works with variable names #}
    {% if foo is defined %}
        ...
    {% endif %}

    {# and attributes on variables names #}
    {% if foo.bar is defined %}
        ...
    {% endif %}

Extensions
----------

Twig can be easily extended. If you are looking for new tags or filters, have
a look at the Twig official extension repository:
http://github.com/fabpot/Twig-extensions.
