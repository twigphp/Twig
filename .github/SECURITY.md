Security Policy
===============

DO NOT PUBLISH SECURITY REPORTS PUBLICLY.

Reporting a Security Issue
--------------------------

If you find an issue that might have security implications, send a report to
security[at]symfony.com.

The full [security reporting and resolution process][1] is described in the
Symfony documentation.

Security Scope for Untrusted Templates
--------------------------------------

Twig treats template source as trusted code unless the template is rendered in
the [Twig sandbox][2]. The regular Twig environment is not a security boundary.

Applications that render templates supplied by untrusted users must enable and
correctly configure the Twig sandbox. Any behavior that is possible because an
application renders an untrusted template without the sandbox is not a security
issue in Twig and must not be reported as one.

Reports about untrusted templates are in scope only when they demonstrate a
sandbox restriction bypass while the sandbox is enabled and its security policy
does not allow the demonstrated operation.

  [1]: https://symfony.com/security
  [2]: https://twig.symfony.com/doc/3.x/sandbox.html
