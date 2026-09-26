Twig Sandbox
============

The sandbox renders templates written by untrusted authors, such as your
users. A sandboxed template can only use the tags, filters, functions, and
tests you allow, and only access the object methods and properties you allow.

.. warning::

    Twig treats templates as trusted code. The sandbox is the only security
    boundary for templates written by untrusted authors: rendering such a
    template without it gives its author the same power as your application
    code.

Rendering Untrusted Templates
-----------------------------

.. versionadded:: 3.29

    The ``Twig\Sandbox\Sandbox`` class and its ``SandboxInterface`` were added
    in Twig 3.29.

Create a ``Twig\Sandbox\Sandbox`` from an environment dedicated to untrusted
templates and a security policy::

    use Twig\Environment;
    use Twig\Loader\ArrayLoader;
    use Twig\Sandbox\Sandbox;
    use Twig\Sandbox\SecurityPolicy;

    $env = new Environment(new ArrayLoader($userTemplates), [
        'cache' => '/path/to/sandbox/cache',
    ]);

    $policy = new SecurityPolicy(
        allowedTags: ['include'],
        allowedFilters: ['date', 'escape'],
        allowedMethods: [Article::class => ['getTitle']],
    );
    $policy->setStrict(true);

    $sandbox = new Sandbox($env, $policy);

    // render a template known to the environment loader
    echo $sandbox->render('newsletter.twig', ['article' => $article]);

    // render a template held as a string
    echo $sandbox->createTemplate($source)->render(['article' => $article]);

Everything rendered through the sandbox is sandboxed: ``render()``,
``display()``, and ``stream()`` render a template; ``renderBlock()``,
``displayBlock()``, and ``streamBlock()`` render one of its blocks; and
``createTemplate()`` creates a template from a string. Templates included by
a sandboxed template are sandboxed as well. Type-hint ``SandboxInterface``
when injecting a sandbox into your services.

The environment defines everything untrusted templates can reach: its loader
defines which templates exist, and the extensions, filters, functions, tests,
and globals registered on it define which capabilities exist. That's why it
must be dedicated to the sandbox: build a new environment and pass it to the
sandbox before using it; the constructor throws a ``LogicException`` if the
environment was already used. Never pass your application environment: every
application template would then be rendered in the sandbox.

A ``SecurityPolicy`` must be strict (``setStrict(true)``): a non-strict policy
allows some tags, functions, and tests implicitly and behaves differently from
Twig 4.0. The constructor throws a ``LogicException`` otherwise.

To render an untrusted template from one of your templates, use the
:doc:`render_sandboxed <functions/render_sandboxed>` function.

Configuring the Security Policy
-------------------------------

A ``SecurityPolicy`` allow-lists what templates can use; everything else is
rejected with a ``Twig\Sandbox\SecurityError`` exception::

    $policy = new SecurityPolicy(
        allowedTags: ['include'],
        allowedFilters: ['date', 'escape'],
        allowedMethods: [
            Article::class => ['getTitle', 'getBody', '__toString'],
        ],
        allowedProperties: [
            Article::class => ['title'],
        ],
        allowedFunctions: ['range'],
        allowedTests: ['published'],
    );

.. versionadded:: 3.28

    The ``allowedTests`` argument was added in Twig 3.28. Before, all tests
    were allowed.

When auto-escaping is enabled (the default), Twig applies the ``escape``
filter to every printed expression, so allow it until Twig 4.0, where it is
always allowed. The ``..`` operator calls the ``range`` function, so
``{% for i in 1..10 %}`` requires allowing the ``range`` function (and the
``for`` tag until Twig 4.0).

Tags, filters, functions, and tests are checked when each template starts
rendering, and methods and properties when a template uses them. A render can
therefore fail after ``display()`` or ``stream()`` sent part of the output,
for instance when an included template uses a forbidden filter; use
``render()`` to get the output only if the whole template renders.

Objects
~~~~~~~

Templates can only access the methods and properties allowed for the class of
an object. An entry applies to the class, its subclasses, and, for an
interface, the classes implementing it. Method names are case-insensitive,
property names are case-sensitive:

* ``{{ article.title }}`` is allowed if the ``title`` public property or the
  method it resolves to (``title()``, ``getTitle()``, ``isTitle()``, or
  ``hasTitle()``) is allowed;

* ``{{ article.getTitle() }}`` requires the ``getTitle`` method;

* ``{{ article['title'] }}`` on an object implementing ``ArrayAccess`` reads
  the offset if the ``title`` property is allowed, and otherwise resolves like
  ``article.title``; native classes like ``ArrayObject``, ``ArrayIterator``,
  ``SplFixedArray``, or ``SplObjectStorage`` don't need to be allowed, but
  their subclasses do;

* printing an object or converting it to a string in any other way (with a
  filter like ``upper``, a concatenation, a comparison with a string, and so
  on) requires its ``__toString`` method, except for ``Twig\Markup`` objects,
  like the output of ``include()``.

When a method is handled by ``__call()``, allow the name used in the
template: ``{{ article.slug }}`` requires the ``slug`` method. Never allow
magic methods like ``__call``, ``__get``, or ``__set``: a template could call
them directly, as in ``{{ article.__call('anyMethod', ['argument']) }}``, with
any name and arguments. Reading a property handled by ``__get()`` or by a
property hook runs that code, so only allow such a property if that code is
safe to run.

Always Allowed Built-ins
~~~~~~~~~~~~~~~~~~~~~~~~

The built-in ``defined``, ``divisible by``, ``empty``, ``even``,
``iterable``, ``mapping``, ``none``, ``null``, ``odd``, ``same as``,
``sequence``, and ``true`` tests are always allowed. The ``constant`` test
reads PHP constants, so allow it explicitly if you need it.

In Twig 4.0, the following built-ins will be always allowed as well; allow
them explicitly in 3.x:

* Tags: ``apply``, ``block``, ``do``, ``for``, ``if``, ``macro``, ``set``,
  ``types``, ``with``;

* Filters: ``abs``, ``batch``, ``capitalize``, ``convert_encoding``,
  ``default``, ``e``, ``escape``, ``first``, ``format``, ``join``, ``keys``,
  ``last``, ``length``, ``lower``, ``merge``, ``nl2br``, ``number_format``,
  ``replace``, ``reverse``, ``round``, ``slice``, ``split``, ``striptags``,
  ``title``, ``trim``, ``upper``, ``url_encode``;

* Functions: ``cycle``, ``max``, ``min``.

Keeping them in your policy after upgrading is harmless.

Unlike the tags above, never allow the ``guard`` tag: sandboxed templates are
written for a known environment, so they have no reason to check which
filters, functions, and tests it registers.

.. _sandbox-limits:

What the Sandbox Does Not Protect Against
-----------------------------------------

The sandbox assumes that template authors control the template source, and
nothing else: the environment, its extensions, the policy, and the data you
pass are yours, and Twig trusts them. Keep the following in mind when deciding
what to expose.

**Data is visible.** Only pass the data, and register the globals, a template
needs: a template can iterate over every variable through ``_context``. The
policy only governs objects: array keys and values are readable without any
check. If you show error messages to template authors, they also reveal names:
security errors name the class of the object, reading a missing key with
``strict_variables`` enabled lists the existing ones, and an unknown tag,
filter, function, or test suggests registered names close to it.

**Allowed operations use their arguments freely.** Allow-lists only restrict
what the template source writes explicitly. An allowed filter, function, test,
or tag can use the objects it receives in any way PHP allows: iteration calls
``getIterator()``, ``Iterator``, or ``Countable`` methods, ``cycle`` calls
``offsetGet()`` on ``ArrayAccess`` objects, ``json_encode`` calls
``jsonSerialize()`` and, like ``url_encode``, exposes public properties,
``min`` and ``max`` compare objects by their properties, and your own filters
and functions do whatever their code does. Only allow operations that are safe
for the objects you pass, or pass arrays and scalars instead.

**Objects must behave consistently.** Before an operation converts the
elements of an iterable to strings (like ``join``), the sandbox iterates it to
check them, then the operation iterates it again. An object producing
different elements on each iteration (because it consumes a queue, re-runs a
query, or reads from a stream) can give the operation elements that were never
checked. Twig does not consider such bypasses security issues: convert these
values to arrays before passing them.

**Your code runs with full power.** Filters, functions, and extensions
registered on the sandbox environment run as regular PHP code; only register
code that is safe to call with arguments chosen by template authors.

**Output is not sanitized.** The text of a template is output as written by
its author, HTML and JavaScript included; auto-escaping only applies to
printed expressions. Only use the output where content from its author is
acceptable.

**Resources are not limited.** A template can consume as much CPU, memory, or
time as it wants, even under the strictest policy: large ranges, nested loops,
or recursive macros are enough. Contain sandboxed renders at the process level
(time limits, memory limits, dedicated workers).

Defining Callables for Sandboxed Templates
------------------------------------------

Filters, functions, and tests receive arguments chosen by template authors.
Never accept a PHP ``callable`` argument, and never leave such an argument
untyped: a template could pass the name of any PHP function, like ``system``.
Type-hint ``\Closure`` instead, so that templates can only pass arrow
functions::

    $custom = function (iterable $items, \Closure $callback) {
        // ...
    };
    $env->addFilter(new \Twig\TwigFilter('custom', $custom));

To adapt the behavior of a filter, function, or test in sandboxed templates,
use the ``needs_is_sandboxed`` option (see :ref:`sandbox-aware-filters`).

.. versionadded:: 3.28

    The ``always_allowed_in_sandbox`` option and the
    ``isAlwaysAllowedInSandbox()`` token parser method were added in Twig 3.28.

A filter, function, or test can be always allowed, so that policies don't have
to list it, with the ``always_allowed_in_sandbox`` option::

    $env->addFilter(new \Twig\TwigFilter('rot13', 'str_rot13', [
        'always_allowed_in_sandbox' => true,
    ]));

For a tag, return ``true`` from ``isAlwaysAllowedInSandbox()`` in its token
parser. Only do so for items meeting all these criteria:

* they expose no new capability: pure value predicates (``even``), value
  transformations (``upper``), or control flow (``if``);

* they don't reach the PHP runtime: no constants, classes, or functions chosen
  by the template;

* they don't accept callables, so applications can still forbid higher-order
  operations like ``map`` or ``filter``;

* they don't load templates, like ``include`` or ``source``;

* they don't let templates mark arbitrary content as safe, like ``raw``;

* they don't dump object internals or call serialization hooks, like
  ``json_encode``;

* they have no side effects on the PHP environment, like ``flush``;

* they are deterministic, unlike ``random``.

These criteria cover what the item itself exposes; how it uses the objects it
receives is covered in :ref:`sandbox-limits`.

Upgrading From the Sandbox Extension
------------------------------------

.. deprecated:: 3.29

    The ``SandboxExtension`` is internal as of Twig 3.29, and its
    ``enableSandbox()``, ``disableSandbox()``, and ``isSandboxedGlobally()``
    methods and the ``sandboxed`` argument of the ``include`` function are
    deprecated. Use the ``Sandbox`` class instead.

Before the ``Sandbox`` class, the sandbox was enabled by registering the
``SandboxExtension`` on an environment, either for all templates or only for
templates included with the ``sandboxed`` argument::

    $twig->addExtension(new \Twig\Extension\SandboxExtension($policy, true));

To migrate, move untrusted templates to an environment dedicated to a
``Sandbox``, and make the policy strict. A non-strict policy always allows the
``extends`` and ``use`` tags, the ``parent``, ``block``, and ``attribute``
functions, and every test; a strict one requires allowing them like anything
else, as Twig 4.0 will. Replace ``include()`` calls using the ``sandboxed``
argument, and the deprecated ``sandbox`` tag, with the
:doc:`render_sandboxed <functions/render_sandboxed>` function.
