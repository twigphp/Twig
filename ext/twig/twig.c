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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_twig.h"
#include "ext/standard/php_var.h"
#include "ext/standard/php_string.h"
#include "ext/standard/php_smart_str.h"
#include "ext/spl/spl_exceptions.h"

#include "Zend/zend_object_handlers.h"
#include "Zend/zend_interfaces.h"
#include "Zend/zend_exceptions.h"
#include "Zend/zend_operators.h"

ZEND_BEGIN_ARG_INFO_EX(twig_template_get_attribute_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 6)
	ZEND_ARG_OBJ_INFO(0, template, Twig_Template, 0)
	ZEND_ARG_INFO(0, object)
	ZEND_ARG_INFO(0, item)
	ZEND_ARG_INFO(0, arguments)
	ZEND_ARG_INFO(0, type)
	ZEND_ARG_INFO(0, isDefinedTest)
ZEND_END_ARG_INFO()

static zend_function_entry twig_functions[] = {
	PHP_FE(twig_template_get_attributes, twig_template_get_attribute_args)
	PHP_FE_END
};

ZEND_DECLARE_MODULE_GLOBALS(twig)

#ifdef COMPILE_DL_TWIG
ZEND_GET_MODULE(twig)
#endif
zend_module_entry twig_module_entry = {
	STANDARD_MODULE_HEADER,
	"twig",
	twig_functions,
	NULL,
	NULL,
	PHP_RINIT(twig),
	NULL,
	NULL,
	PHP_TWIG_VERSION,
	PHP_MODULE_GLOBALS(twig),
	PHP_GINIT(twig),
	NULL,
	NULL,
	STANDARD_MODULE_PROPERTIES_EX
};

PHP_RINIT_FUNCTION(twig)
{
	TWIG_G(twig_error_runtime_ce) = NULL;
	TWIG_G(twig_template_ce)      = NULL;

	return SUCCESS;
}

PHP_GINIT_FUNCTION(twig)
{
	memset(twig_globals, 0, sizeof(*twig_globals));
}

static zend_class_entry *twig_get_twig_error_runtime_ce(TSRMLS_D)
{
	zend_class_entry **ce;

	if (TWIG_G(twig_error_runtime_ce)) {
		return TWIG_G(twig_error_runtime_ce);
	}
	zend_lookup_class(TWIG_ERROR_RUNTIME_CLASS_NAME, strlen(TWIG_ERROR_RUNTIME_CLASS_NAME), &ce TSRMLS_CC);
	TWIG_G(twig_error_runtime_ce) = *ce;

	return TWIG_G(twig_error_runtime_ce);
}

static zend_class_entry *twig_get_twig_template_ce(TSRMLS_D)
{
	zend_class_entry **ce;

	if (TWIG_G(twig_template_ce)) {
		return TWIG_G(twig_template_ce);
	}
	zend_lookup_class(TWIG_TEMPLATE_CLASS_NAME, strlen(TWIG_TEMPLATE_CLASS_NAME), &ce TSRMLS_CC);
	TWIG_G(twig_template_ce) = *ce;

	return TWIG_G(twig_template_ce);
}

static int twig_array_key_exists(zval *array, zval *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) {
		return 0;
	}

	switch (Z_TYPE_P(key)) {
		case IS_NULL:
			return zend_hash_exists(Z_ARRVAL_P(array), "", 1);
		case IS_BOOL:
			return zend_hash_index_exists(Z_ARRVAL_P(array), Z_BVAL_P(key));
		case IS_LONG:
			return zend_hash_index_exists(Z_ARRVAL_P(array), Z_LVAL_P(key));
		case IS_DOUBLE:
			return zend_hash_index_exists(Z_ARRVAL_P(array), zend_dval_to_lval(Z_DVAL_P(key)));
		case IS_STRING:
			return zend_symtable_exists(Z_ARRVAL_P(array), Z_STRVAL_P(key), Z_STRLEN_P(key) + 1);
		default:
			return 0;
	}
}

static int twig_instance_of(zval *object, zend_class_entry *interface TSRMLS_DC)
{
	if (Z_TYPE_P(object) != IS_OBJECT) {
		return 0;
	}

	return instanceof_function(Z_OBJCE_P(object), interface TSRMLS_CC);
}

static int twig_check_instanceof_Twig_Template(zval *object TSRMLS_DC)
{
	zend_class_entry *pce;

	if (Z_TYPE_P(object) != IS_OBJECT) {
		return 0;
	}

	if ((pce = twig_get_twig_template_ce(TSRMLS_C)) == NULL) {
		return 0;
	}

	return instanceof_function(Z_OBJCE_P(object), pce TSRMLS_CC);
}

static zval *twig_get_arrayobject_element(zval *object, zval *offset TSRMLS_DC)
{
	if ((Z_TYPE_P(object) == IS_OBJECT) && instanceof_function(Z_OBJCE_P(object), zend_ce_arrayaccess TSRMLS_CC)) {
		return Z_OBJ_HANDLER_P(object, read_dimension)(object, offset, 0 TSRMLS_CC);
	}

	return NULL;
}

static int twig_isset_arrayobject_element(zval *object, zval *offset TSRMLS_DC)
{
	if ((Z_TYPE_P(object) == IS_OBJECT) && instanceof_function(Z_OBJCE_P(object), zend_ce_arrayaccess TSRMLS_CC)) {
		return Z_OBJ_HANDLER_P(object, has_dimension)(object, offset, 0 TSRMLS_CC);
	}

	return 0;
}

static zval *twig_cufa(zval *object, char *function, zval *arguments TSRMLS_DC)
{
	zend_fcall_info fci;
	zval ***args = NULL;
	int arg_count = 0;
	HashTable *table;
	HashPosition pos;
	int i = 0;
	zval *retval_ptr;
	zval *zfunction;

	if (arguments) {
		table = HASH_OF(arguments);
		args  = safe_emalloc(table->nNumOfElements, sizeof(zval **), 0);

		zend_hash_internal_pointer_reset_ex(table, &pos);

		while (zend_hash_get_current_data_ex(table, (void **)&args[i], &pos) == SUCCESS) {
			i++;
			zend_hash_move_forward_ex(table, &pos);
		}
		arg_count = table->nNumOfElements;
	}

	MAKE_STD_ZVAL(zfunction);
	ZVAL_STRING(zfunction, function, 1);
	fci.size           = sizeof(fci);
	fci.function_table = EG(function_table);
	fci.function_name  = zfunction;
	fci.symbol_table   = NULL;
	fci.object_ptr     = object;
	fci.retval_ptr_ptr = &retval_ptr;
	fci.param_count    = arg_count;
	fci.params         = args;
	fci.no_separation  = 0;

	if (zend_call_function(&fci, NULL TSRMLS_CC) == FAILURE) {
		ALLOC_INIT_ZVAL(retval_ptr);
		ZVAL_BOOL(retval_ptr, 0);
	}

	if (args) {
		efree(fci.params);
	}

	zval_ptr_dtor(&zfunction);

	return retval_ptr;
}

static char twig_call_boolean(zval *object, char *functionName TSRMLS_DC)
{
	zval *ret;
	char res;

	ret = twig_cufa(object, functionName, NULL TSRMLS_CC);
	res = (char)Z_BVAL_P(ret);

	if (Z_TYPE_P(ret) != IS_BOOL) {
		zval *tmp;
		if (!Z_ISREF_P(ret)) {
			Z_ADDREF_P(ret);
		}
		ZVAL_COPY_VALUE(tmp, ret);
		convert_to_boolean_ex(&tmp);
		res = (char)Z_BVAL_P(tmp);
	}

	zval_ptr_dtor(&ret);

	return res;
}

static zval *twig_get_static_property(zval *class, char *prop_name TSRMLS_DC)
{
	zval **tmp_zval;
	zend_class_entry *ce;

	if (class == NULL || Z_TYPE_P(class) != IS_OBJECT) {
		return NULL;
	}

	ce       = zend_get_class_entry(class TSRMLS_CC);
	tmp_zval = zend_std_get_static_property(ce, prop_name, strlen(prop_name), 0, NULL TSRMLS_CC);

	return *tmp_zval;
}

static zval *twig_get_array_element_zval(zval *class, zval *prop_name TSRMLS_DC)
{
	if (Z_TYPE_P(class) == IS_ARRAY) {
		zval **tmp_zval;

		switch(Z_TYPE_P(prop_name)) {
			case IS_NULL:
				zend_hash_find(HASH_OF(class), "", 1, (void**) &tmp_zval);
				return *tmp_zval;
			case IS_BOOL:
				zend_hash_index_find(HASH_OF(class), Z_BVAL_P(prop_name), (void **) &tmp_zval);
				return *tmp_zval;
			case IS_DOUBLE:
				zend_hash_index_find(HASH_OF(class), zend_dval_to_lval(Z_DVAL_P(prop_name)), (void **) &tmp_zval);
				return *tmp_zval;
			case IS_LONG:
				zend_hash_index_find(HASH_OF(class), Z_LVAL_P(prop_name), (void **) &tmp_zval);
				return *tmp_zval;
			case IS_STRING:
				zend_symtable_find(HASH_OF(class), Z_STRVAL_P(prop_name), Z_STRLEN_P(prop_name) + 1, (void**) &tmp_zval);
				return *tmp_zval;
			default:
				return NULL;
		}
	}

	return twig_get_arrayobject_element(class, prop_name TSRMLS_CC);
}

static zval *twig_get_array_element(zval *class, char *prop_name, int prop_name_length TSRMLS_DC)
{
	zval **tmp_zval;

	if (class == NULL) {
		return NULL;
	}

	if (Z_TYPE_P(class) == IS_OBJECT && twig_instance_of(class, zend_ce_arrayaccess TSRMLS_CC)) {
		zval tmp_name_zval;
		zval *tmp_ret_zval;

		INIT_ZVAL(tmp_name_zval);
		ZVAL_STRING(&tmp_name_zval, prop_name, 0);
		tmp_ret_zval = twig_get_arrayobject_element(class, &tmp_name_zval TSRMLS_CC);
		return tmp_ret_zval;
	}

	if (zend_symtable_find(HASH_OF(class), prop_name, prop_name_length+1, (void**)&tmp_zval) == SUCCESS) {
		return *tmp_zval;
	}

	return NULL;
}

static zval *twig_property(zval *object, zval *propname TSRMLS_DC)
{
	zval *tmp = NULL;

	if (Z_OBJ_HT_P(object)->read_property) {
		tmp = Z_OBJ_HT_P(object)->read_property(object, propname, BP_VAR_IS, NULL TSRMLS_CC);
		if (tmp == EG(uninitialized_zval_ptr)) {
			ZVAL_NULL(tmp);
		}
	}

	return tmp;
}

static int twig_has_property(zval *object, zval *propname TSRMLS_DC)
{
	int retval = 0;
	zval propname_copy;

	if (Z_OBJ_HT_P(object)->has_property) {
		MAKE_COPY_ZVAL(&propname, &propname_copy); /* needed against a strange Zend bug when fetching exception backtrace */
		retval = Z_OBJ_HT_P(object)->has_property(object, &propname_copy, 0, NULL TSRMLS_CC);
		zval_dtor(&propname_copy);
	}

	return retval;
}

static int twig_has_dynamic_property(zval *object, char *prop, int prop_len TSRMLS_DC)
{
	if (Z_OBJ_HT_P(object)->get_properties) {
		return zend_hash_exists(Z_OBJ_HT_P(object)->get_properties(object TSRMLS_CC), prop, prop_len + 1);
	}

	return 0;
}

static zval *twig_property_char(zval *object, const char *propname TSRMLS_DC)
{
	zval tmp_name_zval;

	INIT_ZVAL(tmp_name_zval);
	ZVAL_STRING(&tmp_name_zval,propname, 0);

	return twig_property(object, &tmp_name_zval TSRMLS_CC);
}

static zval *twig_call_s(zval *object, char *method, char *arg0 TSRMLS_DC)
{
	zval *zarg0;
	int method_len;
	char *method_lc;
	zval *retval_ptr;

	ALLOC_INIT_ZVAL(zarg0);
	ZVAL_STRING(zarg0, arg0, 1);
	method_len = strlen(method);
	method_lc  =  zend_str_tolower_dup(method, method_len);

	zend_call_method(&object, zend_get_class_entry((const zval *)object TSRMLS_CC), NULL, method_lc, method_len, &retval_ptr, 1, zarg0, NULL TSRMLS_CC);

	zval_ptr_dtor(&zarg0);
	efree(method_lc);

	return retval_ptr;
}

static int twig_call_sb(zval *object, char *method, char *arg0 TSRMLS_DC)
{
	zval *retval_ptr;
	int success;

	retval_ptr = twig_call_s(object, method, arg0 TSRMLS_CC);
	success    = (retval_ptr && (Z_TYPE_P(retval_ptr) == IS_BOOL) && Z_LVAL_P(retval_ptr));

	if (retval_ptr) {
		zval_ptr_dtor(&retval_ptr);
	}

	return success;
}

static int twig_call_zz(zval *object, char *method, zval *arg1, zval *arg2 TSRMLS_DC)
{
	int method_len, success;
	char *method_lc;
	zval *retval_ptr;

	method_len = strlen(method);
	method_lc  =  zend_str_tolower_dup(method, method_len);

	zend_call_method(&object, zend_get_class_entry((const zval *)object TSRMLS_CC), NULL, method_lc, method_len, &retval_ptr, 2, arg1, arg2 TSRMLS_CC);

	success = (retval_ptr && (Z_TYPE_P(retval_ptr) == IS_BOOL) && Z_LVAL_P(retval_ptr));
	if (retval_ptr) {
		zval_ptr_dtor(&retval_ptr);
	}

	efree(method_lc);

	return success;
}

static void twig_new(zval *object, char *class, zval *arg0, zval *arg1 TSRMLS_DC)
{
	zend_class_entry **pce;

	if (zend_lookup_class(class, strlen(class), &pce TSRMLS_CC) == FAILURE) {
		return;
	}

	Z_TYPE_P(object) = IS_OBJECT;
	object_init_ex(object, *pce);
	Z_SET_REFCOUNT_P(object, 1);
	Z_UNSET_ISREF_P(object);

	twig_call_zz(object, "__construct", arg0, arg1 TSRMLS_CC);
}

static int twig_add_array_key_to_string(void *pDest TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	smart_str *buf;
	char *joiner;

	buf    = va_arg(args, smart_str*);
	joiner = va_arg(args, char*);

	if (buf->len != 0) {
		smart_str_appends(buf, joiner);
	}

	if (hash_key->nKeyLength == 0) {
		smart_str_append_long(buf, (long) hash_key->h);
	} else {
		char *key, *tmp_str;
		int key_len, tmp_len;
		key     = php_addcslashes(hash_key->arKey, hash_key->nKeyLength - 1, &key_len, 0, "'\\", 2 TSRMLS_CC);
		tmp_str = php_str_to_str_ex(key, key_len, "\0", 1, "' . \"\\0\" . '", 12, &tmp_len, 0, NULL);

		smart_str_appendl(buf, tmp_str, tmp_len);
		efree(key);
		efree(tmp_str);
	}

	return ZEND_HASH_APPLY_KEEP;
}

static char *twig_implode_array_keys(char *joiner, zval *array TSRMLS_DC)
{
	smart_str collector = { 0, 0, 0 };

	smart_str_appendl(&collector, "", 0);
	zend_hash_apply_with_arguments(HASH_OF(array) TSRMLS_CC, twig_add_array_key_to_string, 2, &collector, joiner);
	smart_str_0(&collector);

	return collector.c;
}

static void twig_runtime_error(zval *template TSRMLS_DC, char *message, ...)
{
	char *buffer;
	va_list args;
	zend_class_entry *pce;
	zend_fcall_info_cache fcic = {0};
	zend_fcall_info fci        = {0};
	zval filename_func, constructor_retval;
	zval *zmessage_p, *lineno_p, *filename_p, *constructor_retval_p = &constructor_retval, *ex;
	zval **constructor_args[3];

	if ((pce = twig_get_twig_error_runtime_ce(TSRMLS_C)) == NULL) {
		return;
	}

	va_start(args, message);
	vspprintf(&buffer, 0, message, args);
	va_end(args);

	ALLOC_INIT_ZVAL(ex);
	object_init_ex(ex, pce);

	ALLOC_INIT_ZVAL(zmessage_p);
	ALLOC_INIT_ZVAL(lineno_p);
	ALLOC_INIT_ZVAL(filename_p);

	INIT_ZVAL(filename_func);
	INIT_ZVAL(constructor_retval);

	ZVAL_STRING(zmessage_p, buffer, 0);
	ZVAL_LONG(lineno_p, -1);

	ZVAL_STRINGL(&filename_func, "getTemplateName", sizeof("getTemplateName")-1, 0);
	call_user_function(EG(function_table), &template, &filename_func, filename_p, 0, NULL TSRMLS_CC);

	fci.size           = sizeof(fci);
	fci.params         = (zval ***)constructor_args;
	fci.param_count    = 3;
	fci.retval_ptr_ptr = &constructor_retval_p;

	fcic.called_scope     = pce;
	fcic.calling_scope    = Z_OBJCE_P(template);
	fcic.function_handler = pce->constructor;
	fcic.object_ptr       = ex;
	fcic.initialized      = 1;

	constructor_args[0] = &zmessage_p;
	constructor_args[1] = &lineno_p;
	constructor_args[2] = &filename_p;

	zend_call_function(&fci, &fcic TSRMLS_CC);

	zval_ptr_dtor(&zmessage_p);
	zval_ptr_dtor(&lineno_p);
	zval_ptr_dtor(&filename_p);
	zval_ptr_dtor(&constructor_retval_p);

	zend_throw_exception_object(ex TSRMLS_CC);
}

static int twig_add_method_to_class(void *pDest TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zval *retval;
	char *item;
	size_t item_len;
	zend_function *mptr = (zend_function *) pDest;

	if (!(mptr->common.fn_flags & ZEND_ACC_PUBLIC)) {
		return ZEND_HASH_APPLY_KEEP;
	}

	retval = va_arg(args, zval*);

	item_len = strlen(mptr->common.function_name);
	item     = estrndup(mptr->common.function_name, item_len);
	php_strtolower(item, item_len);

	add_assoc_stringl_ex(retval, item, item_len+1, item, item_len, 0);

	return ZEND_HASH_APPLY_KEEP;
}

static int twig_add_property_to_class(void *pDest TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zend_class_entry *ce;
	zval *retval;
	char *class_name, *prop_name;
	zend_property_info *pptr = (zend_property_info *) pDest;

	if (!(pptr->flags & ZEND_ACC_PUBLIC) || (pptr->flags & ZEND_ACC_STATIC)) {
		return ZEND_HASH_APPLY_KEEP;
	}

	ce     = *va_arg(args, zend_class_entry**);
	retval = va_arg(args, zval*);

	zend_unmangle_property_name(pptr->name, pptr->name_length, (const char **) &class_name, (const char **) &prop_name);

	add_assoc_string(retval, prop_name, prop_name, 1);

	return ZEND_HASH_APPLY_KEEP;
}

static void twig_add_class_to_cache(zval *cache, zval *object, char *class_name TSRMLS_DC)
{
	zval *class_info, *class_methods, *class_properties;
	zend_class_entry *class_ce;

	class_ce = zend_get_class_entry(object TSRMLS_CC);

	ALLOC_INIT_ZVAL(class_info);
	ALLOC_INIT_ZVAL(class_methods);
	ALLOC_INIT_ZVAL(class_properties);
	array_init(class_info);
	array_init(class_methods);
	array_init(class_properties);

	/* add all methods to self::cache[$class]['methods'] */
	zend_hash_apply_with_arguments(&class_ce->function_table TSRMLS_CC, twig_add_method_to_class, 1, class_methods);

	zend_hash_apply_with_arguments(&class_ce->properties_info TSRMLS_CC, twig_add_property_to_class, 2, &class_ce, class_properties);

	add_assoc_zval(class_info, "methods", class_methods);
	add_assoc_zval(class_info, "properties", class_properties);
	add_assoc_zval(cache, class_name, class_info);
}

/* {{{ proto mixed twig_template_get_attributes(TwigTemplate template, mixed object, mixed item, array arguments, string type, boolean isDefinedTest, boolean ignoreStrictCheck)
   A C implementation of TwigTemplate::getAttribute() */
PHP_FUNCTION(twig_template_get_attributes)
{
	zval *template;
	zval *object;
	zval *zitem;
	zval *arguments             = NULL;
	char *type                  = "any";
	int   type_len              = sizeof(type) - 1;
	zend_bool isDefinedTest     = 0;
	zend_bool ignoreStrictCheck = 0;

	char *item_str;
	int item_len;

	zval *ret = NULL;

	char free_ret = 0;
	zval *tmp_class;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ozz|asbb", &template, &object, &zitem, &arguments, &type, &type_len, &isDefinedTest, &ignoreStrictCheck) == FAILURE) {
		return;
	}

	{
		zval copy;
		INIT_PZVAL_COPY(&copy, zitem);
		if (Z_TYPE(copy) == IS_STRING) {
			item_str = estrndup(Z_STRVAL(copy), Z_STRLEN(copy));
			item_len = Z_STRLEN(copy);
		} else {
			convert_to_string(&copy);
			item_str = Z_STRVAL(copy);
			item_len = Z_STRLEN(copy);
		}
	}

/*
	// array
	if (Twig_Template::METHOD_CALL !== $type) {
		$arrayItem = is_bool($item) || is_float($item) ? (int) $item : $item;

		if ((is_array($object) && array_key_exists($arrayItem, $object))
			|| ($object instanceof ArrayAccess && isset($object[$arrayItem]))
		) {
			if ($isDefinedTest) {
				return true;
			}

			return $object[$arrayItem];
		}
*/


	if (strcmp("method", type) != 0) {
		if (twig_array_key_exists(object, zitem) ||	twig_isset_arrayobject_element(object, zitem TSRMLS_CC)) {
			if (UNEXPECTED(isDefinedTest)) {
				str_efree(item_str);
				RETURN_TRUE;
			}

			ret = twig_get_array_element_zval(object, zitem TSRMLS_CC);

			if (!ret) {
				ret = &EG(uninitialized_zval);
			}
			str_efree(item_str);
			RETURN_ZVAL(ret, 1, 0);
		}
/*
		if (Twig_Template::ARRAY_CALL === $type) {
			if ($isDefinedTest) {
				return false;
			}
			if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
				return null;
			}
*/
		if (strcmp("array", type) == 0 || Z_TYPE_P(object) != IS_OBJECT) {
			if (UNEXPECTED(isDefinedTest)) {
				str_efree(item_str);
				RETURN_FALSE;
			}
			if (ignoreStrictCheck || !twig_call_boolean(twig_property_char(template, "env" TSRMLS_CC), "isStrictVariables" TSRMLS_CC)) {
				str_efree(item_str);
				return;
			}
/*
			if ($object instanceof ArrayAccess) {
				$message = sprintf('Key "%s" in object with ArrayAccess of class "%s" does not exist', $arrayItem, get_class($object));
			} elseif (is_object($object)) {
				$message = sprintf('Impossible to access a key "%s" on an object of class "%s" that does not implement ArrayAccess interface', $item, get_class($object));
			} elseif (is_array($object)) {
				if (empty($object)) {
					$message = sprintf('Key "%s" does not exist as the array is empty', $arrayItem);
				} else {
					$message = sprintf('Key "%s" for array with keys "%s" does not exist', $arrayItem, implode(', ', array_keys($object)));
				}
			} elseif (Twig_Template::ARRAY_CALL === $type) {
				if (null === $object) {
					$message = sprintf('Impossible to access a key ("%s") on a null variable', $item);
				} else {
					$message = sprintf('Impossible to access a key ("%s") on a %s variable ("%s")', $item, gettype($object), $object);
				}
			} elseif (null === $object) {
				$message = sprintf('Impossible to access an attribute ("%s") on a null variable', $item);
			} else {
				$message = sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s")', $item, gettype($object), $object);
			}
			throw new Twig_Error_Runtime($message, -1, $this->getTemplateName());
		}
	}
*/
			if (twig_instance_of(object, zend_ce_arrayaccess TSRMLS_CC)) {
				twig_runtime_error(template TSRMLS_CC, "Key \"%s\" in object with ArrayAccess of class \"%s\" does not exist", item_str, Z_OBJ_CLASS_NAME_P(object));
			} else if (Z_TYPE_P(object) == IS_OBJECT) {
				twig_runtime_error(template TSRMLS_CC, "Impossible to access a key \"%s\" on an object of class \"%s\" that does not implement ArrayAccess interface", item_str, Z_OBJ_CLASS_NAME_P(object));
			} else if (Z_TYPE_P(object) == IS_ARRAY) {
				if (0 == zend_hash_num_elements(Z_ARRVAL_P(object))) {
					twig_runtime_error(template TSRMLS_CC, "Key \"%s\" does not exist as the array is empty", item_str);
				} else {
					char *keys = twig_implode_array_keys(", ", object TSRMLS_CC);
					twig_runtime_error(template TSRMLS_CC, "Key \"%s\" for array with keys \"%s\" does not exist", item_str, keys);
					efree(keys);
				}
			} else {
				char *type_name  = zend_zval_type_name(object);
				if (Z_TYPE_P(object) != IS_NULL) {
					zval *object_str = object;
					Z_ADDREF_P(object_str);
					convert_to_string_ex(&object_str);
					twig_runtime_error(template TSRMLS_CC,
							(strcmp("array", type) == 0)
							? "Impossible to access a key (\"%s\") on a %s variable (\"%s\")"
							: "Impossible to access an attribute (\"%s\") on a %s variable (\"%s\")",
							item_str, type_name, Z_STRVAL_P(object_str));
					zval_ptr_dtor(&object_str);
				} else {
					twig_runtime_error(template TSRMLS_CC,
						(strcmp("array", type) == 0)
						? "Impossible to access a key (\"%s\") on a %s variable"
						: "Impossible to access an attribute (\"%s\") on a %s variable",
						item_str, type_name);
				}
			}
			str_efree(item_str);
			return;
		}
	}

/*
	if (!is_object($object)) {
		if ($isDefinedTest) {
			return false;
		}
*/

	if (Z_TYPE_P(object) != IS_OBJECT) {
		if (UNEXPECTED(isDefinedTest)) {
			str_efree(item_str);
			RETURN_FALSE;
		}
/*
		if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
			return null;
		}

		if (null === $object) {
			$message = sprintf('Impossible to invoke a method ("%s") on a null variable', $item);
		} else {
			$message = sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s")', $item, gettype($object), $object);
		}

		throw new Twig_Error_Runtime($message, -1, $this->getTemplateName());
	}
*/
		if (ignoreStrictCheck || !twig_call_boolean(twig_property_char(template, "env" TSRMLS_CC), "isStrictVariables" TSRMLS_CC)) {
			str_efree(item_str);
			return;
		}

		if (Z_TYPE_P(object) != IS_NULL) {
			zval *object_str = object;
			Z_ADDREF_P(object);
			convert_to_string_ex(&object_str);
			twig_runtime_error(template TSRMLS_CC, "Impossible to invoke a method (\"%s\") on a %s variable (\"%s\")", item_str, zend_zval_type_name(object), Z_STRVAL_P(object_str));
			zval_ptr_dtor(&object_str);
		} else {
			twig_runtime_error(template TSRMLS_CC, "Impossible to invoke a method (\"%s\") on a %s variable", item_str, zend_zval_type_name(object));
		}
		str_efree(item_str);
		return;
	}
/*
	$class = get_class($object);
*/
	{
		zval *tmp_self_cache = twig_get_static_property(template, "cache" TSRMLS_CC);
		tmp_class            = twig_get_array_element(tmp_self_cache, (char *)Z_OBJ_CLASS_NAME_P(object), strlen(Z_OBJ_CLASS_NAME_P(object)) TSRMLS_CC);

		if (!tmp_class) {
			twig_add_class_to_cache(tmp_self_cache, object, (char *)Z_OBJ_CLASS_NAME_P(object) TSRMLS_CC);
			tmp_class = twig_get_array_element(tmp_self_cache, (char *)Z_OBJ_CLASS_NAME_P(object), strlen(Z_OBJ_CLASS_NAME_P(object)) TSRMLS_CC);
		}
	}
/*
	// object property
	if (Twig_Template::METHOD_CALL !== $type && !$object instanceof Twig_Template) {
		if (isset($object->$item) || array_key_exists((string) $item, $object)) {
			if ($isDefinedTest) {
				return true;
			}

			if ($this->env->hasExtension('sandbox')) {
				$this->env->getExtension('sandbox')->checkPropertyAllowed($object, $item);
			}

			return $object->$item;
		}
	}
*/
	if (strcmp("method", type) != 0 && !twig_check_instanceof_Twig_Template(object TSRMLS_CC)) {
		zval *tmp_properties, *tmp_item;

		tmp_properties = twig_get_array_element(tmp_class, "properties", strlen("properties") TSRMLS_CC);
		tmp_item       = twig_get_array_element(tmp_properties, item_str, item_len TSRMLS_CC);

		if (tmp_item || twig_has_property(object, zitem TSRMLS_CC) || twig_has_dynamic_property(object, item_str, item_len TSRMLS_CC)) {
			if (UNEXPECTED(isDefinedTest)) {
				str_efree(item_str);
				RETURN_TRUE;
			}
			if (twig_call_sb(twig_property_char(template, "env" TSRMLS_CC), "hasExtension", "sandbox" TSRMLS_CC)) {
				twig_call_zz(twig_call_s(twig_property_char(template, "env" TSRMLS_CC), "getExtension", "sandbox" TSRMLS_CC), "checkPropertyAllowed", object, zitem TSRMLS_CC);
			}
			if (EG(exception)) {
				str_efree(item_str);
				return;
			}

			ret = twig_property(object, zitem TSRMLS_CC);
			str_efree(item_str);
			if (Z_REFCOUNT_P(ret) == 0) {
				RETURN_ZVAL(ret, 0, 0);
			}
			RETURN_ZVAL(ret, 1, 0);
		}
	}
/*
	// object method
	if (!isset(self::$cache[$class]['methods'])) {
		if ($object instanceof self) {
			$ref = new ReflectionClass($class);
			$methods = array();

			foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
				$methods[strtolower($refMethod->name)] = true;
			}

			self::$cache[$class]['methods'] = $methods;
        } else {
			self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
        }
	}

	$call = false;
	$lcItem = strtolower($item);
	if (isset(self::$cache[$class]['methods'][$lcItem])) {
		$method = (string) $item;
	} elseif (isset(self::$cache[$class]['methods']['get'.$lcItem])) {
		$method = 'get'.$item;
	} elseif (isset(self::$cache[$class]['methods']['is'.$lcItem])) {
		$method = 'is'.$item;
	} elseif (isset(self::$cache[$class]['methods']['__call'])) {
		$method = (string) $item;
		$call = true;
*/
	{
		int call = 0;
		char *lcItem = zend_str_tolower_dup(item_str, item_len);
		char *method = NULL;
		char *tmp_method_name_get, *tmp_method_name_is, *tmp_method_name_has;
		int tmp_method_name_get_len, tmp_method_name_is_len, tmp_method_name_has_len;
		zval *zmethod;
		zval *tmp_methods;

		tmp_method_name_get_len = spprintf(&tmp_method_name_get, 0, "get%s", lcItem);
		tmp_method_name_has_len = spprintf(&tmp_method_name_has, 0, "has%s", lcItem);
		tmp_method_name_is_len  = spprintf(&tmp_method_name_is, 0, "is%s", lcItem);

		tmp_methods = twig_get_array_element(tmp_class, "methods", strlen("methods") TSRMLS_CC);

		if (twig_get_array_element(tmp_methods, lcItem, item_len TSRMLS_CC)) {
			method = item_str;
			efree(lcItem);
		} else if (twig_get_array_element(tmp_methods, tmp_method_name_get, tmp_method_name_get_len TSRMLS_CC)) {
			method = tmp_method_name_get;
			efree(lcItem);
		} else if (twig_get_array_element(tmp_methods, tmp_method_name_is, tmp_method_name_is_len TSRMLS_CC)) {
			method = tmp_method_name_is;
			efree(lcItem);
		} else if (twig_get_array_element(tmp_methods, tmp_method_name_has, tmp_method_name_has_len TSRMLS_CC)) {
			method = tmp_method_name_has;
			efree(lcItem);
		} else if (twig_get_array_element(tmp_methods, "__call", strlen("__call") TSRMLS_CC)) {
			method = item_str;
			call   = 1;
			efree(lcItem);
/*
	} else {
		if ($isDefinedTest) {
			return false;
		}

		if ($ignoreStrictCheck || !$this->env->isStrictVariables()) {
			return null;
		}

		throw new Twig_Error_Runtime(sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()"/"is%1$s()" or "__call()" exist and have public access in class "%2$s"', $item, get_class($object)), -1, $this->getTemplateName());
	}

	if ($isDefinedTest) {
		return true;
	}
*/
		} else {
			efree(tmp_method_name_get);
			efree(tmp_method_name_is);
			efree(tmp_method_name_has);
			efree(lcItem);

			if (UNEXPECTED(isDefinedTest)) {
				str_efree(item_str);
				RETURN_FALSE;
			}
			if (ignoreStrictCheck || !twig_call_boolean(twig_property_char(template, "env" TSRMLS_CC), "isStrictVariables" TSRMLS_CC)) {
				str_efree(item_str);
				return;
			}
			twig_runtime_error(template TSRMLS_CC, "Neither the property \"%s\" nor one of the methods \"%s()\", \"get%s()\"/\"is%s()\" or \"__call()\" exist and have public access in class \"%s\"", item_str, item_str, item_str, item_str, Z_OBJ_CLASS_NAME_P(object));
			str_efree(item_str);
			return;
		}

		if (UNEXPECTED(isDefinedTest)) {
			efree(tmp_method_name_is);
			efree(tmp_method_name_get);
			efree(tmp_method_name_has);
			str_efree(item_str);
			RETURN_TRUE;
		}
/*
	if ($this->env->hasExtension('sandbox')) {
		$this->env->getExtension('sandbox')->checkMethodAllowed($object, $method);
	}
*/
		MAKE_STD_ZVAL(zmethod);
		ZVAL_STRING(zmethod, method, 1);

		if (twig_call_sb(twig_property_char(template, "env" TSRMLS_CC), "hasExtension", "sandbox" TSRMLS_CC)) {
			twig_call_zz(twig_call_s(twig_property_char(template, "env" TSRMLS_CC), "getExtension", "sandbox" TSRMLS_CC), "checkMethodAllowed", object, zmethod TSRMLS_CC);
		}
		zval_ptr_dtor(&zmethod);
		if (EG(exception)) {
			str_efree(item_str);
			efree(tmp_method_name_is);
			efree(tmp_method_name_get);
			efree(tmp_method_name_has);
			return;
		}
/*
	// Some objects throw exceptions when they have __call, and the method we try
	// to call is not supported. If ignoreStrictCheck is true, we should return null.
	try {
	    $ret = call_user_func_array(array($object, $method), $arguments);
	} catch (BadMethodCallException $e) {
	    if ($call && ($ignoreStrictCheck || !$this->env->isStrictVariables())) {
	        return null;
	    }
	    throw $e;
	}
*/
		ret = twig_cufa(object, method, arguments TSRMLS_CC);
		efree(tmp_method_name_is);
		efree(tmp_method_name_get);
		efree(tmp_method_name_has);
		str_efree(item_str);

		if (EG(exception) && twig_instance_of(EG(exception), spl_ce_BadMethodCallException TSRMLS_CC)) {
			if (ignoreStrictCheck || !twig_call_boolean(twig_property_char(template, "env" TSRMLS_CC), "isStrictVariables" TSRMLS_CC)) {
				zend_clear_exception(TSRMLS_C);
				return;
			}
		}
		free_ret = 1;
	}
/*
	// useful when calling a template method from a template
	// this is not supported but unfortunately heavily used in the Symfony profiler
	if ($object instanceof Twig_Template) {
		return $ret === '' ? '' : new Twig_Markup($ret, $this->env->getCharset());
	}

	return $ret;
*/

	/* ret can be null, if e.g. the called method throws an exception */
	if (ret) {
		if (twig_check_instanceof_Twig_Template(object TSRMLS_CC)) {
			if (Z_STRLEN_P(ret) != 0) {
				zval *charset = twig_cufa(twig_property_char(template, "env" TSRMLS_CC), "getCharset", NULL TSRMLS_CC);
				twig_new(return_value, "Twig_Markup", ret, charset TSRMLS_CC);
				zval_ptr_dtor(&charset);
				if (ret) {
					zval_ptr_dtor(&ret);
				}
				return;
			}
		}

		RETVAL_ZVAL(ret, 1, 0);
		if (free_ret) {
			zval_ptr_dtor(&ret);
		}
	}
}
