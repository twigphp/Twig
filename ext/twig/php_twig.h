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

#define PHP_TWIG_VERSION "1.9.2-DEV"

#include "php.h"

extern zend_module_entry twig_module_entry;
#define phpext_twig_ptr &twig_module_entry

#ifdef ZTS
#include "TSRM.h"
#endif

#if PHP_VERSION_ID >= 50400
#define TWIG_OBJECT_HAS_PROPERTY(object, propname) ((Z_OBJ_HT_P(object)->has_property)?Z_OBJ_HT_P(object)->has_property(object, propname, 0, NULL TSRMLS_CC):0)
#define TWIG_OBJECT_READ_PROPERTY(object, propname, type) (Z_OBJ_HT_P(object)->read_property(object, propname, BP_VAR_IS, NULL TSRMLS_CC))
#else
#define TWIG_OBJECT_HAS_PROPERTY(object, propname) ((Z_OBJ_HT_P(object)->has_property)?Z_OBJ_HT_P(object)->has_property(object, propname, 0 TSRMLS_CC):0) 
#define TWIG_OBJECT_READ_PROPERTY(object, propname, type) (Z_OBJ_HT_P(object)->read_property(object, propname, BP_VAR_IS TSRMLS_CC))
#endif
#define TWIG_OBJECT_HAS_DIMENSION(object, offset) (Z_OBJ_HT_P(object)->has_dimension(object, offset, 0 TSRMLS_CC))
#define TWIG_OBJECT_READ_DIMENSION(object, offset) (Z_OBJ_HT_P(object)->read_dimension(object, offset, 0 TSRMLS_CC))


PHP_FUNCTION(twig_template_get_attributes);

#endif
