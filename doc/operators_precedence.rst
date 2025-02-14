
=========== ================ ======= ============= ===========
Precedence  Operator         Type    Associativity Description
=========== ================ ======= ============= ===========
512 => 300  ``|``            infix   Left          Twig filter call
            ``(``                                  Twig function call
            ``.``                                  Get an attribute on a variable
            ``[``                                  Array access
500         ``-``            prefix  n/a
            ``+``
300 => 5    ``??``           infix   Right         Null coalescing operator (a ?? b)
250         ``=>``           infix   Left          Arrow function (x => expr)
200         ``**``           infix   Right         Exponentiation operator
100         ``is``           infix   Left          Twig tests
            ``is not``                             Twig tests
60          ``*``            infix   Left
            ``/``
            ``//``                                 Floor division
            ``%``
50 => 70    ``not``          prefix  n/a
40 => 27    ``~``            infix   Left
30          ``+``            infix   Left
            ``-``
25          ``..``           infix   Left
20          ``==``           infix   Left
            ``!=``
            ``<=>``
            ``<``
            ``>``
            ``>=``
            ``<=``
            ``not in``
            ``in``
            ``matches``
            ``starts with``
            ``ends with``
            ``has some``
            ``has every``
18          ``b-and``        infix   Left
17          ``b-xor``        infix   Left
16          ``b-or``         infix   Left
15          ``and``          infix   Left
12          ``xor``          infix   Left
10          ``or``           infix   Left
5           ``?:``           infix   Right         Elvis operator (a ?: b)
            ``?:``                                 Elvis operator (a ?: b)
0           ``(``            prefix  n/a           Explicit group expression (a)
            ``literal``                            A literal value (boolean, string, number, sequence, mapping, ...)
            ``?``            infix   Left          Conditional operator (a ? b : c)
=========== ================ ======= ============= ===========

When a precedence will change in 4.0, the new precedence is indicated by the arrow ``=>``.

Here is the same table for Twig 4.0 with adjusted precedences:

=========== ============== ======= ============= ===========
Precedence  Operator       Type    Associativity Description
=========== ============== ======= ============= ===========
512         `(`            infix   Left          Twig function call
            `.`                                  Get an attribute on a variable
            `[`                                  Array access
500         `-`            prefix  n/a
            `+`
300         `|`            infix   Left          Twig filter call
250         `=>`           infix   Left          Arrow function (x => expr)
200         `**`           infix   Right         Exponentiation operator
100         `is`           infix   Left          Twig tests
            `is not`                             Twig tests
70          `not`          prefix  n/a
60          `*`            infix   Left
            `/`
            `//`                                 Floor division
            `%`
30          `+`            infix   Left
            `-`
27          `~`            infix   Left
25          `..`           infix   Left
20          `==`           infix   Left
            `!=`
            `<=>`
            `<`
            `>`
            `>=`
            `<=`
            `not in`
            `in`
            `matches`
            `starts with`
            `ends with`
            `has some`
            `has every`
18          `b-and`        infix   Left
17          `b-xor`        infix   Left
16          `b-or`         infix   Left
15          `and`          infix   Left
12          `xor`          infix   Left
10          `or`           infix   Left
5           `??`           infix   Right         Null coalescing operator (a ?? b)
            `?:`                                 Elvis operator (a ?: b)
            `?:`                                 Elvis operator (a ?: b)
0           `(`            prefix  n/a           Explicit group expression (a)
            `literal`                            A literal value (boolean, string, number, sequence, mapping, ...)
            `?`            infix   Left          Conditional operator (a ? b : c)
=========== ============== ======= ============= ===========
