String Extension
================

This package is a Twig extension that provides integration with the Symfony String component.

It provides a single [`u`][1] filter that wraps a text in a `UnicodeString` object to give access to [methods of the class](https://symfony.com/doc/current/components/string.html).

`{{ 'Symfony String + Twig = <3'|u.wordwrap(5) }}`

[1]: https://twig.symfony.com/u
