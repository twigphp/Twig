/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2016 Sensiolabs                                        |
   +----------------------------------------------------------------------+
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the conditions mentioned   |
   | in the accompanying LICENSE file are met (BSD-3-Clause).             |
   +----------------------------------------------------------------------+
   | Author: Julien PAULI <jpauli@php.net>                                |
   +----------------------------------------------------------------------+
 */

#ifndef PHP_TWIG_H
#define PHP_TWIG_H

#include "Zend/zend_extensions.h"

#define PHP_TWIG_VERSION "2.0.0-DEV"

#define PHP_7_0_X_API_NO		320151012
#define PHP_7_1_X_API_NO		320160303
#define IS_PHP_71          ZEND_EXTENSION_API_NO == PHP_7_1_X_API_NO
#define IS_AT_LEAST_PHP_71 ZEND_EXTENSION_API_NO >= PHP_7_1_X_API_NO
#define IS_PHP_70          ZEND_EXTENSION_API_NO == PHP_7_0_X_API_NO
#define IS_AT_LEAST_PHP_70 ZEND_EXTENSION_API_NO >= PHP_7_0_X_API_NO

#include "php.h"

extern zend_module_entry twig_module_entry;
#define phpext_twig_ptr &twig_module_entry

#ifdef COMPILE_DL_TWIG
zend_module_entry *get_module(void);
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

typedef struct _twig_request_globals {
	zend_class_entry *twig_error_runtime_ce;
	zend_class_entry *twig_template_ce;
	zend_class_entry *twig_environment_ce;
	zend_class_entry *twig_source_ce;
} twig_request_globals;

ZEND_BEGIN_MODULE_GLOBALS(twig)
	twig_request_globals r;
ZEND_END_MODULE_GLOBALS(twig)

ZEND_DECLARE_MODULE_GLOBALS(twig);

#ifdef ZTS
#define TWIG_G(v) TSRMG(twig_globals_id, zend_twig_globals *, v)
ZEND_TSRMLS_CACHE_DEFINE();
#else
#define TWIG_G(v) (twig_globals.v)
#endif
#define TWIG_G_R(v) TWIG_G(r).v

#if IS_PHP_70
#define EXECUTOR_SCOPE EG(scope)
#define zend_get_executed_scope() EG(scope)
#else
#define EXECUTOR_SCOPE EG(fake_scope)
#endif

PHP_FUNCTION(twig_get_attribute);
PHP_RSHUTDOWN_FUNCTION(twig);
PHP_MINIT_FUNCTION(twig);
PHP_MSHUTDOWN_FUNCTION(twig);
PHP_GINIT_FUNCTION(twig);

#endif
