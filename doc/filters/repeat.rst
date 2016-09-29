``repeat``
=======

The ``repeat`` filter returns the value repeated a requested number of times.

.. code-block:: jinja

    {# string = 'Twig' #}

    {{ string|repeat(3) }}

    {# outputs 'TwigTwigTwig' #}

.. note::

    Internally, Twig uses the PHP `str_repeat`_ function.

.. _`str_repeat`: http://php.net/str_repeat