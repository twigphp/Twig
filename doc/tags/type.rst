``type``
==============

Twig can perform more performant access, when type of variables can be predicted.
To support prediction you can provide type hints, that you already know from PHP:

.. code-block:: twig

    {% type interval \DateInterval %}
