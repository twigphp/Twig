<?php

function twig_array_filter($array, $arrow)
{
    foreach ($array as $k => $v) {
        if ($arrow($v, $k)) {
            yield $k => $v;
        }
    }
}
