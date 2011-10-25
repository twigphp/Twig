``json_encode``
===============

The ``json_encode`` filter returns the JSON representation of a string:

.. code-block:: jinja

    {{ data|json_encode() }}

.. note::

    Internally, Twig uses the PHP `json_encode`_ function.

.. _`json_encode`: http://php.net/json_encode
