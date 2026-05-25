Twig Sandbox
============

The ``sandbox`` extension can be used to evaluate untrusted code.

Registering the Sandbox
-----------------------

Register the ``SandboxExtension`` extension via the ``addExtension()`` method::

    $twig->addExtension(new \Twig\Extension\SandboxExtension($policy));

Configuring the Sandbox Policy
------------------------------

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
    $policy = new \Twig\Sandbox\SecurityPolicy($tags, $filters, $methods, $properties, $functions);

With the above configuration, the security policy will only allow usage of the
``if`` tag, and the ``upper`` filter. Moreover, the templates will only be able
to call the ``getTitle()`` and ``getBody()`` methods on ``Article`` objects,
and the ``title`` and ``body`` public properties. Everything else won't be
allowed and will generate a ``\Twig\Sandbox\SecurityError`` exception.

.. note::

    As of Twig 3.14.1 (and on Twig 3.11.2), if the ``Article`` class implements
    the ``ArrayAccess`` interface, the templates will only be able to access
    the ``title`` and ``body`` attributes.

    Note that native array-like classes (like ``ArrayObject``) are always
    allowed, you don't need to configure them.

.. caution::

    The ``extends`` and ``use`` tags, as well as the ``parent``, ``block``, and
    ``attribute`` functions are always allowed in a sandboxed template. That
    behavior will change in 4.0 where they will need to be explicitly allowed
    like any other tag or function. To opt-in to the 4.0 behavior now (so they
    need to be allow-listed or get rejected), enable strict mode on the
    security policy::

        $policy->setStrict(true);

Enabling the Sandbox
--------------------

By default, the sandbox mode is disabled and should be enabled when including
untrusted template code by using the ``sandboxed`` option of the ``include``
function:

.. code-block:: twig

    {{ include('user.html.twig', sandboxed: true) }}

You can sandbox all templates by passing ``true`` as the second argument of
the extension constructor::

    $twig->addExtension(new \Twig\Extension\SandboxExtension($policy, true));

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
call ``offsetGet()``. None of these calls appear in the template source.

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
