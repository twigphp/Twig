``render_sandboxed``
====================

.. versionadded:: 3.29

    The ``render_sandboxed`` function was added in Twig 3.29.

The ``render_sandboxed`` function renders a template through a dedicated
:doc:`sandbox <../sandbox>`:

.. code-block:: twig

    {{ render_sandboxed('newsletter.twig', {name: name}, 'html') }}

The second argument is the complete context passed to the sandboxed template.
Variables from the trusted template are not included automatically.

The third argument declares the output escaping strategy. The result is
considered safe only for that strategy. If it is used in a context with another
escaping strategy, Twig escapes it for that context. The strategy must be a
non-empty literal string other than ``all`` so Twig can determine safety when
compiling the trusted template.

The output strategy does not sanitize the rendered output. Declaring ``html``
allows HTML written by an untrusted template author to reach the response. Use
it only when this is intended.

The function is not available by default. Register the
``SandboxBridgeExtension`` and a lazy ``SandboxBridgeRuntime`` on the trusted
environment::

    use Twig\Extension\SandboxBridgeExtension;
    use Twig\Runtime\SandboxBridgeRuntime;
    use Twig\RuntimeLoader\FactoryRuntimeLoader;

    $twig->addExtension(new SandboxBridgeExtension());
    $twig->addRuntimeLoader(new FactoryRuntimeLoader([
        SandboxBridgeRuntime::class => fn () => new SandboxBridgeRuntime(
            $sandbox,
        ),
    ]));

Arguments
---------

* ``name``: The name of the sandboxed template to render
* ``context``: The context passed to the sandboxed template
* ``output_strategy``: The escaping strategy for which the result is safe
