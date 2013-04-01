``json_encode``
===============

The ``json_encode`` filter returns the JSON representation of a string:

.. code-block:: jinja

    {{ data|json_encode() }}

.. note::

    Internally, Twig uses the PHP `json_encode`_ function.

Arguments
---------

 * ``options``: A bitmask. See http://www.php.net/manual/en/json.constants.php for a list of possible values. Ex: ``{{ data|json_encode(JSON_PRETTY_PRINT) }}``

.. _`json_encode`: http://php.net/json_encode
