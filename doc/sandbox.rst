Twig Sandbox
============

The sandbox can be used to evaluate untrusted code, restricting what template
authors can reach through explicit allow-lists.

.. warning::

    Twig treats template source as trusted code by default. If an application
    accepts templates from untrusted users, it must enable and correctly
    configure the sandbox. The regular Twig environment is not a security
    boundary, and any behavior caused by rendering an untrusted template
    without the sandbox is not a security issue in Twig.

Rendering Untrusted Templates
-----------------------------

.. versionadded:: 3.29

    The ``Twig\Sandbox\SandboxInterface`` interface and
    ``Twig\Sandbox\Sandbox`` class were added in Twig 3.29.

The recommended way to render untrusted templates is the
``Twig\Sandbox\Sandbox`` class, which implements ``SandboxInterface``. Type-hint
``SandboxInterface`` when injecting a sandbox into an application service. A
``Sandbox`` takes ownership of an environment crafted specifically for it and
renders everything through it in
sandbox mode. Being in full control of that environment, you decide exactly
what untrusted templates can reach: its loader defines which templates exist,
the extensions, filters, functions, tests, and globals you register on it
define which capabilities exist, and the security policy defines what is
allowed to execute::

    use Twig\Environment;
    use Twig\Extra\Intl\IntlExtension;
    use Twig\Loader\ArrayLoader;
    use Twig\Sandbox\Sandbox;
    use Twig\Sandbox\SecurityPolicy;

    // craft an environment dedicated to untrusted templates
    $env = new Environment(new ArrayLoader($untrustedTemplates), [
        'cache' => '/path/to/sandbox/cache',
    ]);
    // register the capabilities untrusted templates may use
    $env->addExtension(new IntlExtension());

    $policy = new SecurityPolicy(
        allowedTags: ['if'],
        allowedFilters: ['upper', 'escape'],
    );
    $policy->setStrict(true);

    $sandbox = new Sandbox($env, $policy);

    // render a template known to the environment loader
    echo $sandbox->render('newsletter.twig', ['name' => 'Fabien']);

    // render an untrusted template held as a string
    echo $sandbox->createTemplate($userTemplate)->render(['name' => 'Fabien']);

The environment must be dedicated to the sandbox: build a fresh environment
and pass it before its first use (the constructor throws a ``LogicException``
otherwise). In particular, never pass your main application environment: all
your application templates would suddenly be rendered in sandbox mode.
Keeping the two environments separate also guarantees isolation in both
directions: the sandbox cannot load or affect application templates, and
application renders happening while a sandboxed render is in flight are not
sandboxed.

A ``SecurityPolicy`` passed to the sandbox must be strict (call
``setStrict(true)``) so it behaves the same way in Twig 3.x and 4.0; the
constructor throws a ``LogicException`` otherwise.

Everything rendered through a ``Sandbox`` is sandboxed: ``render()``,
``display()``, and ``stream()`` render a template from the environment loader
by name; ``renderBlock()``, ``displayBlock()``, and ``streamBlock()`` render a
single block of such a template; ``createTemplate()`` turns a string into a
sandboxed template. Templates included by a sandboxed template are sandboxed
as well.

Data is passed through the render context (or registered as globals on the
environment you crafted); the policy governs any method or property access on
those values either way.

Rendering From a Trusted Template
---------------------------------

To render an untrusted template from a trusted template, use the
:doc:`render_sandboxed() function <functions/render_sandboxed>`.

.. note::

    When auto-escaping is enabled (the default), the ``escape`` filter is
    applied to every printed expression, so it must be part of the filter
    allow-list for sandboxed templates to render.

.. caution::

    PHP code invoked during a sandboxed render (a filter, function, or
    extension you registered on the sandbox environment) runs with its full
    PHP capabilities: the sandbox only restricts what the template source can
    express. Only register extensions and callables that are safe to call
    with attacker-chosen arguments.

Using the Sandbox Extension Directly
------------------------------------

.. deprecated:: 3.29

    The ``SandboxExtension`` is internal as of Twig 3.29 and should not be
    used directly anymore; the ``sandboxed`` argument of the ``include``
    function and the ``enableSandbox()``, ``disableSandbox()``, and
    ``isSandboxedGlobally()`` methods are deprecated. Use the ``Sandbox``
    class instead.

Before the ``Sandbox`` class existed, sandboxing was configured by registering
the ``SandboxExtension`` on the environment via the ``addExtension()``
method::

    $twig->addExtension(new \Twig\Extension\SandboxExtension($policy));

By default, the sandbox mode is then disabled and gets enabled when including
untrusted template code by using the ``sandboxed`` option of the ``include``
function:

.. code-block:: twig

    {{ include('user.html.twig', sandboxed: true) }}

You can also sandbox all templates by passing ``true`` as the second argument
of the extension constructor::

    $twig->addExtension(new \Twig\Extension\SandboxExtension($policy, true));

Configuring the Sandbox Policy
------------------------------

The security policy is enforced the same way whether templates are rendered
through a ``Sandbox`` or through the ``SandboxExtension`` directly.

The sandbox security is managed by a policy instance, which must be passed to
the ``SandboxExtension`` constructor.

By default, Twig comes with one policy class: ``\Twig\Sandbox\SecurityPolicy``.
This class allows you to allow-list some tags, filters, functions, and
properties and methods on objects::

    $tags = ['if'];
    $filters = ['upper'];
    $methods = [
        'Article' => ['getTitle', 'getBody'],
    ];
    $properties = [
        'Article' => ['title', 'body'],
    ];
    $functions = ['range'];
    $tests = ['my_test'];
    $policy = new \Twig\Sandbox\SecurityPolicy($tags, $filters, $methods, $properties, $functions, $tests);

With the above configuration, the security policy will only allow usage of the
``if`` tag, the ``upper`` filter, and the ``my_test`` test (on top of the
built-in tests that are always allowed, see below). Moreover, the templates
will only be able to call the ``getTitle()`` and ``getBody()`` methods on
``Article`` objects, and the ``title`` and ``body`` public properties.
Everything else won't be allowed and will generate a
``\Twig\Sandbox\SecurityError`` exception.

.. note::

    Most built-in tests (``empty``, ``defined``, ``even``, ``same as``,
    ``iterable``, etc.) are always allowed and do not need to be listed. Only
    custom tests and the built-in ``constant`` test must be allow-listed like
    filters and functions.

.. note::

    If the ``Article`` class implements the ``ArrayAccess`` interface, the
    templates will only be able to access the ``title`` and ``body``
    attributes.

    Note that native array-like classes (like ``ArrayObject``) are always
    allowed, you don't need to configure them.

.. note::

    When an attribute resolves through a PHP magic ``__call()`` method (the
    class has no real method or property with that name), the sandbox checks
    the **virtual method name written in the template**, not ``__call``. For
    example, ``{{ article.slug }}`` on an object that handles ``slug`` via
    ``__call()`` requires ``slug`` in the method allow-list::

        $methods = [
            'Article' => ['slug'],
        ];

    Allow-listing ``__call`` itself has no effect: it would only match a
    template that literally writes ``{{ article.__call }}``. Allow each virtual
    method by its own name so the policy stays granular.

Marking Filters, Functions, Tests, and Tags as Always Allowed
-------------------------------------------------------------

Some filters, functions, tests, and tags are inherently safe and should always
be usable in sandboxed templates without forcing every policy to allow-list
them. Mark such callables by setting the ``always_allowed_in_sandbox`` option
to ``true``::

    $twig->addFilter(new \Twig\TwigFilter('upper', 'strtoupper', [
        'always_allowed_in_sandbox' => true,
    ]));

    $twig->addFunction(new \Twig\TwigFunction('max', 'max', [
        'always_allowed_in_sandbox' => true,
    ]));

    $twig->addTest(new \Twig\TwigTest('even', null, [
        'always_allowed_in_sandbox' => true,
    ]));

For tags, override ``isAlwaysAllowedInSandbox()`` on your token parser to
return ``true``::

    final class MyTagTokenParser extends \Twig\TokenParser\AbstractTokenParser
    {
        public function isAlwaysAllowedInSandbox(): bool
        {
            return true;
        }

        // ...
    }

Marked filters, functions, tests, and tags are skipped by the sandbox security
check entirely, so they incur no runtime overhead, and they do not need to be
listed in the ``SecurityPolicy`` allow-lists.

The sandbox assumes that attackers control template source, not the Twig
environment, registered extensions, runtime configuration, security policy,
custom escaping strategies, or context values passed by the application. Treat
those application-provided pieces as trusted. If a callable or a value is not
safe for untrusted template authors, don't register or expose it in the
sandboxed environment.

Criteria for Marking an Item as Always Allowed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Only mark a callable or tag as always allowed when **all** the following
conditions hold:

* **No new capability.** The item must not expose anything beyond what the
  sandbox already accepts. Pure value predicates (``is even``, ``is empty``),
  pure value transformations (``upper``, ``trim``, ``abs``), and pure control
  flow (``if``, ``for``, ``set``) qualify.
* **No PHP runtime access.** The item must not read arbitrary PHP constants,
  call arbitrary classes or functions, instantiate objects from
  user-controlled names, or otherwise reach into the PHP runtime. This rules
  out ``constant``, ``enum``, ``invoke``, and similar.
* **No callable arguments.** The item must not accept a callable parameter it
  dispatches to. This rules out higher-order operations like ``map``,
  ``filter``, ``reduce``, ``find``, ``sort``, and ``column``: applications may
  have deliberate reasons to forbid those, and they need the policy gate to do
  so.
* **No cross-template resolution.** The item must not resolve template names
  at runtime or pivot through the loader. This rules out ``include``,
  ``extends``, ``embed``, ``use``, ``import``, ``from``, ``source``, and
  ``template_from_string``.
* **No output-safety bypass.** The item must not let the template declare
  its own output safe. This rules out ``raw``.
* **No dedicated introspection or debugging surface.** The item must not be
  intended to dump arbitrary object internals or call user-defined
  serialization hooks. This rules out ``json_encode`` and ``dump``.
* **No side effects on the PHP environment.** The item must not flush
  output buffers, trigger deprecations, or otherwise affect global state.
  This rules out ``flush`` and ``deprecated``.
* **Deterministic output.** The item must return the same value for the same
  arguments across renders. Applications that rely on sandboxed templates being
  reproducible (for caching, content hashing, golden-output tests, or audit
  comparisons) lose that property if a template can pull from the PHP random
  number generator without the policy opting in. This rules out ``random`` and
  ``shuffle``: applications that want them can still allow-list them
  explicitly.

Note that several allowed items will still interact with PHP interfaces on
objects passed as arguments (``Countable::count()``,
``IteratorAggregate::getIterator()``, ``Stringable::__toString()`` on
iterated items). That transitive behavior is documented separately under
:ref:`Allowed Operations Apply Transitively to Their Arguments
<allowed-operations-transitive>` and is considered an accepted property of
the sandbox model. The criteria above are about what the item itself
exposes, not about how its arguments behave.

Built-ins That Are Always Allowed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following Twig built-ins meet the criteria above and have the
``always_allowed_in_sandbox`` flag set, so they never need to be allow-listed.

* Tags: ``apply``, ``block``, ``do``, ``for``, ``guard``, ``if``, ``macro``,
  ``set``, ``types``, ``with``.
* Filters: ``abs``, ``batch``, ``capitalize``, ``convert_encoding``,
  ``default``, ``e``, ``escape``, ``first``, ``format``, ``join``, ``keys``,
  ``last``, ``length``, ``lower``, ``merge``, ``nl2br``, ``number_format``,
  ``replace``, ``reverse``, ``round``, ``slice``, ``split``, ``striptags``,
  ``title``, ``trim``, ``upper``, ``url_encode``.
* Functions: ``cycle``, ``max``, ``min``.

Listing one of these names in your ``SecurityPolicy`` is harmless: it has no
effect.

The corresponding built-in tests (``defined``, ``divisible by``, ``empty``,
``even``, ``iterable``, ``mapping``, ``none``, ``null``, ``odd``, ``same as``,
``sequence``, ``true``) are also always allowed, so they never need to be
allow-listed. The ``constant`` test is the exception: it reaches into the PHP
runtime, so it is not always allowed and must be allow-listed.

.. _allowed-operations-transitive:

Allowed Operations Apply Transitively to Their Arguments
--------------------------------------------------------

The method and property allow-lists only restrict attribute access written
explicitly in the template (``obj.foo`` and ``obj.foo()``). Once an object is
passed as an argument to an allowed tag, filter, function, or test, that
operation can interact with it in any way PHP allows, without going through
the sandbox allow-list.

This is especially easy to miss for implicit calls made through PHP
interfaces. For example, allowing ``json_encode`` may expose public object
properties and call ``JsonSerializable::jsonSerialize()``; allowing sequence
operations such as ``for``, ``keys``, ``slice``, ``random``, or ``join`` may
call ``IteratorAggregate::getIterator()``, ``Iterator`` methods, or
``Countable::count()``; allowing ``cycle`` with an ``ArrayAccess`` value may
call ``offsetGet()``; allowing ``url_encode`` on arrays may expose public
object properties through PHP's query-string serialization; allowing ``max``
or ``min`` may compare objects by their public properties. None of these calls
appear in the template source.

Only allow operations whose behavior is safe for the objects you expose to
sandboxed templates. If this is not guaranteed, convert objects to plain
arrays or scalars before passing them in.

Limiting Resource Usage
-----------------------

The sandbox prevents untrusted templates from reaching code, data, methods, or
properties they shouldn't. It does **not** prevent a template from consuming
CPU, memory, or wall-clock time, even under the strictest allow-list.

This is by design: any limit baked into Twig itself would be both arbitrary
and trivial to work around, since there are many ways a template can burn
resources (large ranges, nested loops, large string operations, recursive
macros, expensive filters, deeply nested includes, and so on).

If you render untrusted templates, you should contain them at the process level
rather than at the template engine level.

Accepting Callables Arguments
-----------------------------

The Twig sandbox allows you to configure which functions, filters, tests and
dot operations are allowed. Many of these calls can accept arguments. As these
arguments are not validated by the sandbox, you must be very careful.

For instance, accepting a PHP ``callable`` as an argument is dangerous as it
allows end user to call any PHP function (by passing a ``string``) or any
static methods (by passing an ``array``). For instance, it would accept any PHP
built-in functions like ``system()`` or ``exec()``::

    $twig->addFilter(new \Twig\TwigFilter('custom', function (callable $callable) {
        // ...
        $callable();
        // ...
    }));

To avoid this security issue, don't type-hint such arguments with ``callable``
but use ``\Closure`` instead (not using a type-hint would also be problematic).
This restricts the allowed callables to PHP closures only, which is enough to
accept Twig arrow functions::

    $twig->addFilter(new \Twig\TwigFilter('custom', function (\Closure $callable) {
        // ...
        $callable();
        // ...
    }));

    {{ people|custom(p => p.username|join(', ') }}

Any PHP callable can easily be converted to a closure by using the `first-class callable syntax`_.

.. _`first-class callable syntax`: https://www.php.net/manual/en/functions.first_class_callable_syntax.php
