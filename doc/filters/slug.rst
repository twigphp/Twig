``slug``
========

The ``slug`` filter transforms a given string into another string that
only includes safe ASCII characters. 

Here is an example:

.. code-block:: twig

    {{ 'Wôrķšƥáçè ~~sèťtïñğš~~'|slug }}
    Workspace-settings

The default separator between words is a dash (``-``), but you can 
define a selector of your choice by passing it as an argument:

.. code-block:: twig

    {{ 'Wôrķšƥáçè ~~sèťtïñğš~~'|slug('/') }}
    Workspace/settings

The slugger automatically detects the language of the original
string, but you can also specify it explicitly using the second
argument:

.. code-block:: twig

    {{ '...'|slug('-', 'ko') }}

The ``slug`` filter uses the method by the same name in Symfony's 
`AsciiSlugger <https://symfony.com/doc/current/components/string.html#slugger>`_. 

Arguments
---------

* ``separator``: The separator that is used to join words (defaults to ``-``)
* ``locale``: The locale of the original string (if none is specified, it will be automatically detected)
