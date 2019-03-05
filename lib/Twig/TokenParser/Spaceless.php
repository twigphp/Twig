<?php

use Twig\TokenParser\SpacelessTokenParser;

class_exists('Twig\TokenParser\SpacelessTokenParser');

@trigger_error(sprintf('Using the "Twig_TokenParser_Spaceless" class is deprecated since Twig version 1.38, use "Twig\TokenParser\SpacelessTokenParser" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_TokenParser_Spaceless extends SpacelessTokenParser
    {
    }
}
