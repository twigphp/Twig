String Extension
================

This package is a Twig extension that provides integration with the Symfony
String component.

It provides a [`u`][1] filter that wraps a text in a `UnicodeString`
object to give access to [methods of the class][2].

It also provides a [`slug`][3] filter which is simply a wrapper for the 
[`AsciiSlugger`][4]'s `slug` method.

[1]: https://twig.symfony.com/u
[2]: https://symfony.com/doc/current/components/string.html
[3]: https://twig.symfony.com/slug
[4]: https://symfony.com/doc/current/components/string.html#slugger
