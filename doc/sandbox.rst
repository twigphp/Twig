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

    The ``extends`` and ``use`` tags are always allowed in a sandboxed
    template. That behavior will change in 4.0 where these tags will need to be
    explicitly allowed like any other tag.

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
