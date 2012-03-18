/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2011 Derick Rethans                                    |
   +----------------------------------------------------------------------+
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the conditions mentioned   |
   | in the accompanying LICENSE file are met (BSD, revised).             |
   +----------------------------------------------------------------------+
   | Author: Derick Rethans <derick@derickrethans.nl>                     |
   +----------------------------------------------------------------------+
 */

#ifndef PHP_TWIG_H
#define PHP_TWIG_H

#define PHP_TWIG_VERSION "1.6.2"

#include "php.h"

extern zend_module_entry twig_module_entry;
#define phpext_twig_ptr &twig_module_entry

#ifdef PHP_WIN32
#define PHP_TWIG_API __declspec(dllexport)
#else
#define PHP_TWIG_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_FUNCTION(twig_template_get_attributes);

PHP_MINIT_FUNCTION(twig);
PHP_MSHUTDOWN_FUNCTION(twig);
PHP_RINIT_FUNCTION(twig);
PHP_RSHUTDOWN_FUNCTION(twig);
PHP_MINFO_FUNCTION(twig);

#ifdef ZTS
#define TWIG_G(v) TSRMG(twig_globals_id, zend_twig_globals *, v)
#else
#define TWIG_G(v) (twig_globals.v)
#endif

#endif
