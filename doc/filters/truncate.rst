``truncate``
============

The ``truncate`` filter truncates a string variable to a character length,
by default 30 characters, plus the separator:

.. code-block:: jinja

    {{ 'The red car is going to win the race'|truncate }}

    {# outputs The red car is going to win th... #}

Ther first parameter controls the length of the final string:

.. code-block:: jinja

    {{ 'The red car is going to win the race'|truncate(17) }}

    {# outputs The red car is go... #}

The second parameter defines wheter to preserve words or not, ``false`` by
default. When preserving words, the string is truncated on the first
whitespace after the given length instead of being truncated on the
exact character length:

.. code-block:: jinja

    {{ 'The red car is going to win the race'|truncate(17, true) }}

    {# outputs The red car is going... #}

The third parameter defines the separator - ``...`` by default - that
replaces the truncated text.

.. code-block:: jinja

    {{ 'The red car is going to win the race'|truncate(17, true, '@') }}

    {# outputs The red car is going@ #}

.. note::

    Internally, ``truncate`` checks if `mb_get_info`_ is defined to
    either use multibyte string functions or the singlebyte ones.

.. _`mb_get_info`:      http://php.net/mb_get_info
