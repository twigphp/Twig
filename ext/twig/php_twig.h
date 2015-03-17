/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2011 Sensiolabs                                        |
   +----------------------------------------------------------------------+
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the conditions mentioned   |
   | in the accompanying LICENSE file are met (BSD-3-Clause).             |
   +----------------------------------------------------------------------+
   | Author: Derick Rethans <derick@derickrethans.nl>                     |
   |         Julien PAULI <jpauli@php.net>                                |
   +----------------------------------------------------------------------+
 */

#ifndef PHP_TWIG_H
#define PHP_TWIG_H

#define PHP_TWIG_VERSION "2.0.0"

#define TWIG_TEMPLATE_CLASS_NAME      "Twig_Template"
#define TWIG_ERROR_RUNTIME_CLASS_NAME "Twig_Error_Runtime"

#include "php.h"

extern zend_module_entry twig_module_entry;
zend_module_entry *get_module(void);

#ifdef COMPILE_DL_TWIG
zend_module_entry *get_module(void);
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

#ifdef ZTS
#define TWIG_G(v) TSRMG(twig_globals_id, zend_twig_globals *, v)
#else
#define TWIG_G(v) (twig_globals.v)
#endif

ZEND_BEGIN_MODULE_GLOBALS(twig)
	zend_class_entry *twig_template_ce;
	zend_class_entry *twig_error_runtime_ce;
ZEND_END_MODULE_GLOBALS(twig)

ZEND_EXTERN_MODULE_GLOBALS(twig)

PHP_GINIT_FUNCTION(twig);
PHP_RINIT_FUNCTION(twig);

PHP_FUNCTION(twig_template_get_attributes);

#endif
