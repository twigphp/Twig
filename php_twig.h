/*
   +----------------------------------------------------------------------+
   | Twig Extension                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2011 Derick Rethans                                    |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Derick Rethans <derick@derickrethans.nl>                     |
   +----------------------------------------------------------------------+
 */

#ifndef PHP_TWIG_H
#define PHP_TWIG_H

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

PHP_MINIT_FUNCTION(twig);
PHP_MSHUTDOWN_FUNCTION(twig);
PHP_RINIT_FUNCTION(twig);
PHP_RSHUTDOWN_FUNCTION(twig);
PHP_MINFO_FUNCTION(twig);

ZEND_BEGIN_MODULE_GLOBALS(twig)
ZEND_END_MODULE_GLOBALS(twig) 

#ifdef ZTS
#define TWIG_G(v) TSRMG(twig_globals_id, zend_twig_globals *, v)
#else
#define TWIG_G(v) (twig_globals.v)
#endif

#endif
