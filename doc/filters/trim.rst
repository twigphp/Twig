``trim``
========

The ``trim`` filter strips whitespace (or other characters) from the beginning
and end of a string:

.. code-block:: jinja

    {{ '  I like Twig.  '|trim }}

    {# outputs 'I like Twig.' #}

    {{ '  I like Twig.'|trim('.') }}

    {# outputs '  I like Twig' #}

    {{ '  I like Twig.  '|trim(type='left') }}

    {# outputs 'I like Twig.  ' #}

    {{ '  I like Twig.  '|trim(' ', 'right') }}

    {# outputs '  I like Twig.' #}

.. note::

    Internally, Twig uses the PHP `trim`_, `ltrim`_ and `rtrim`_ functions.

Arguments
---------

* ``character_mask``: The characters to strip

* ``type``: The default is to strip from the start and the end (`both`), but `left`
  and `right` will strip from either the left side or right side only.

.. _`trim`: http://php.net/trim
