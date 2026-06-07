Deprecated Features
===================

This document lists deprecated features in Twig 4.x. Deprecated features are
kept for backward compatibility and removed in the next major release (a
feature that was deprecated in Twig 4.x is removed in Twig 5.0).

Sandbox
-------

* The ``Twig\Sandbox\SecurityPolicy::setStrict()`` method is deprecated as of
  Twig 4.0 and will be removed in 5.0. The method is kept as a no-op so that
  code written against Twig 3.x (where it opted-in to the 4.0 sandbox
  behavior) keeps running unmodified on 4.x. Drop the call once you no
  longer need to support Twig 3.x.
