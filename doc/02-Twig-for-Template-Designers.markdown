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

Variables
---------

The application passes variables to the templates you can mess around in the
template. Variables may have attributes or elements on them you can access
too. How a variable looks like, heavily depends on the application providing
those.

You can use a dot (`.`) to access attributes of a variable, alternative the
so-called "subscript" syntax (`[]`) can be used. The following lines do the
same::

    [twig]
    {{ foo.bar }}
    {{ foo['bar'] }}

>**NOTE**
>It's important to know that the curly braces are *not* part of the variable
>but the print statement. If you access variables inside tags don't put the
>braces around.

If a variable or attribute does not exist you will get back a `null` value
(which can be testes with the `none` expression).

>**SIDEBAR**
>Implementation
>
>For convenience sake `foo.bar` does the following things on
>the PHP layer:
>
> * check if `foo` is an array and `bar` a valid element;
> * if not, and if `foo` is an object, check that `bar` is a valid property;
> * if not, and if `foo` is an object, check that `bar` is a valid method;
> * if not, and if `foo` is an object, check that `getBar` is a valid method;
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

The builtin filters section below describes all the builtin filters.

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

In the default configuration whitespace is not further modified by the
template engine, so each whitespace (spaces, tabs, newlines etc.) is returned
unchanged. If the application configures Twig to `trim_blocks` the first
newline after a template tag is removed automatically (like in PHP).

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
    <html xmlns="http://www.w3.org/1999/xhtml">
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

Note that as soon as you specify a second argument it's treated as short block
and Twig won't look for a closing tag.

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

When automatic escaping is enabled everything is escaped by default except for
values explicitly marked as safe. Those can be marked in the template by using
the `|safe` filter.

Functions returning template data (like macros and `parent`) always return
safe markup.

>**NOTE**
>Twig is smart enough to not escape an already escaped value by the `escape`
>filter.

-

>**NOTE**
>The chapter for the developers give more information about when and how
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

The above snippet of code would print all numbers from 0 to 9 (the high value
is never part of the generated array).

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

Inside of a `for` loop block you can access some special variables:

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
      {% for key, value in users %}
        <li>{{ key }}: {{ user.username|e }}</li>
      {% endfor %}
    </ul>

>**NOTE**
>On Twig before 0.9.3, you need to use the `items` filter to access both the
>keys and values (`{% for key, value in users|items %}`).

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
are useful to put often used HTML idioms into reusable functions to not repeat
yourself.

Here a small example of a macro that renders a form element:

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

Macros can be defined in any template, and always need to be "imported" before
being used (see the Import section for more information):

    [twig]
    {% import "forms.html" as forms %}

The above `import` call imports the "forms.html" file (which can contain only
macros, or a template and some macros), and import the functions as items of
the `forms` variable.

The macro can then be called at will:

    [twig]
    <p>{{ forms.input('username') }}</p>
    <p>{{ forms.input('password', none, 'password') }}</p>

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
    {% set foo as 'foo' %}

    {% set foo as [1, 2] %}

    {% set foo as ['foo': 'bar] %}

    {% set foo as 'foo' ~ 'bar' %}

    {% set foo, bar as 'foo', 'bar' %}

### Extends

The `extends` tag can be used to extend a template from another one. You can
have multiple of them in a file but only one of them may be executed at the
time. There is no support for multiple inheritance. See the section about
Template inheritance above.

### Block

Blocks are used for inheritance and act as placeholders and replacements at
the same time. They are documented in detail as part of the section about
Template inheritance above.

### Include

The `include` statement is useful to include a template and return the
rendered contents of that file into the current namespace:

    [twig]
    {% include 'header.html' %}
      Body
    {% include 'footer.html' %}

Included templates have access to the variables of the active context.

An included file can be evaluated in the sandbox environment by appending
`sandboxed` at the end if the `escaper` extension has been enabled:

    [twig]
    {% include 'user.html' sandboxed %}

You can also restrict the variables passed to the template by explicitly pass
them as an array:

    [twig]
    {% include 'foo' with ['foo': 'bar'] %}

    {% set vars as ['foo': 'bar'] %}
    {% include 'foo' with vars %}

The most secure way to include a template is to use both the `sandboxed` mode,
and to pass the minimum amount of variables needed for the template to be
rendered correctly:

    [twig]
    {% include 'foo' sandboxed with vars %}

>**NOTE**
>The `with` keyword is supported as of Twig 0.9.5.

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

Even if the macros are defined in the same template as the one where you want
to use them, they still need to be imported:

    [twig]
    {# index.html template #}

    {% macro textarea(name, value, rows) %}
      <textarea name="{{ name }}" rows="{{ rows|default(10) }}" cols="{{ cols|default(40) }}">{{ value|e }}</textarea>
    {% endmacro %}

    {% import "index.html" as forms %}

    <p>{{ forms.textarea('comment') }}</p>

### Debug

Whenever a template does not work as expected, the debug tag can be used to
output the content of the current context:

    [twig]
    {% debug %}

You can also output a specific variable or an expression:

    [twig]
    {% debug items %}

    {% debug post.body %}

Note that this tag only works when the `debug` option of the environment is
set to `true`.

Expressions
-----------

Twig allows basic expressions everywhere. These work very similar to regular
PHP and even if you're not working with PHP you should feel comfortable with
it.

The operator precedence is as follows, with the lowest-precedence operators
listed first: `or`, `and`, `==`, `!=`, `<`, `>`, `>=`, `<=`, `in`, `+`, `-`,
`~`, `*`, `/`, `%`, `//`, `not`, and `[`.

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

 * `[foo, bar]`: Arrays are defined by a sequence of expressions separated by
   a comma (`,`) and wrapped with squared brackets (`[]`). As an array element
   can be any valid expression, arrays can be nested. The array notation is
   only available as of Twig 0.9.5.

 * `true` / `false` / `none`: `true` represents the true value, `false`
   represents the false value.

 * `none`: `none` represents no specific value (the equivalent of `null` in
   PHP). This is the value returned when a variable does not exist.

### Math

Twig allows you to calculate with values. This is rarely useful in templates
but exists for completeness' sake. The following operators are supported:

 * `+`: Adds two objects together. Usually the objects are numbers but if both
   are strings or lists you can concatenate them this way. This however is not
   the preferred way to concatenate strings! For string concatenation have a
   look at the `~` operator. `{{ 1 + 1 }}` is `2`.

 * `-`: Substract the second number from the first one. `{{ 3 - 2 }}` is `1`.

 * `/`: Divide two numbers. The return value will be a floating point number.
   `{{ 1 / 2 }}` is `{{ 0.5 }}`.

 * `%`: Calculate the remainder of an integer division. `{{ 11 % 7 }}` is `4`.

 * `//`: Divide two numbers and return the truncated integer result. `{{ 20 //
   7 }}` is `2`.

 * `*`: Multiply the left operand with the right one. `{{ 2 * 2 }}` would
   return `4`. This can also be used to repeat a string multiple times. `{{
   '=' * 80 }}` would print a bar of 80 equal signs.

 * `**`: Raise the left operand to the power of the right operand. `{{ 2**3
   }}` would return `8`.

### Logic

For `if` statements, `for` filtering or `if` expressions it can be useful to
combine multiple expressions:

 * `and`: Return true if the left and the right operand is true.

 * `or`: Return true if the left or the right operand is true.

 * `not`: Negate a statement.

 * `(expr)`: Group an expression.

### Comparisons

The following comparison operators are supported in any expression: `==`,
`!=`, `<`, `>`, `>=`, and `<=`.

>**TIP**
>Besides PHP classic comparison operators, Twig also supports a shortcut
>notation when you want to test a value in a range:
>
>     [twig]
>     {% if 1 < foo < 4 %}foo is between 1 and 4{% endif %}

### Other Operators

The following operators are very useful but don't fit into any of the other
two categories:

 * `in` (new in Twig 0.9.5): Perform containment test. Returns `true` if the
   left operand is contained in the right. {{ 1 in [1, 2, 3] }} would for
   example return `true`. To perform a negative test, the whole expression
   should be prefixed with `not` ({{ not 1 in [1, 2, 3] }} would return
   `false`).

 * `..` (new in Twig 0.9.5): Creates a sequence based on the operand before
   and after the operator (see the `for` tag for some usage examples).

 * `|`: Applies a filter.

 * `~`: Converts all operands into strings and concatenates them. `{{ "Hello "
   ~ name ~ "!" }}` would return (assuming `name` is `'John'`) `Hello John!`.

 * `.`, `[]`: Get an attribute of an object.

 * `?:`: Twig supports the PHP ternary operator:

        [twig]
        {{ foo ? 'yes' : 'no' }}

List of Builtin Filters
-----------------------

### `date`

The `date` filter is able to format a date to a given format:

    [twig]
    {{ post.published_at|date("m/d/Y") }}

The `date` filter accepts both timestamps and `DateTime` instances.

### `format`

The `format` filter formats a given string by replacing the placeholders:


    [twig]
    {# string is a format string like: I like %s and %s. #}
    {{ string|format(foo, "bar") }}
    {# returns I like foo and bar. (if the foo parameter equals to the foo string) #}

### `even`

The `even` filter returns `true` if the given number is even, `false`
otherwise:

    [twig]
    {{ var|even ? 'even' : 'odd' }}

### `odd`

The `odd` filter returns `true` if the given number is odd, `false`
otherwise:

    [twig]
    {{ var|odd ? 'odd' : 'even' }}

### `encoding`

The `encoding` filter URL encode a given string.

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

### `safe`

The `safe` filter marks the value as safe which means that in an environment
with automatic escaping enabled this variable will not be escaped.

    [twig]
    {% autoescape on }
      {{ var|safe }} {# var won't be escaped #}
    {% autoescape off %}
