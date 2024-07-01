<?php

namespace PhpBrew\Patches;

use PhpBrew\Buildable;
use PhpBrew\PatchKit\Patch;
use PhpBrew\PatchKit\DiffPatchRule;
use CLIFramework\Logger;

class PHP56WithOpenSSL11Patch extends Patch
{
    public function desc()
    {
        return 'php5.6 with openssl 1.1.x patch.';
    }

    public function match(Buildable $build, Logger $logger)
    {
        $buildVersion = $build->getVersion();

        return substr($buildVersion, 0, 4) === '5.6.'
            && version_compare($buildVersion, '5.6.31') >= 0 // patch only works for 5.6.31 and up
            && $build->isEnabledVariant('openssl');
    }

    /**
     * phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @link https://github.com/php/php-src/pull/2667
     *
     * @codeCoverageIgnore
     */
    public function rules()
    {
        return [DiffPatchRule::fromPatch(
            <<<'EOP'
diff --git a/ext/openssl/openssl.c b/ext/openssl/openssl.c
index a78a8fb10f82..6c3ae3cde80a 100644
--- a/ext/openssl/openssl.c
+++ b/ext/openssl/openssl.c
@@ -42,6 +42,12 @@
 
 /* OpenSSL includes */
 #include <openssl/evp.h>
+#if OPENSSL_VERSION_NUMBER >= 0x10002000L
+#include <openssl/bn.h>
+#include <openssl/rsa.h>
+#include <openssl/dsa.h>
+#include <openssl/dh.h>
+#endif
 #include <openssl/x509.h>
 #include <openssl/x509v3.h>
 #include <openssl/crypto.h>
@@ -531,6 +537,131 @@ zend_module_entry openssl_module_entry = {
 ZEND_GET_MODULE(openssl)
 #endif
 
+/* {{{ OpenSSL compatibility functions and macros */
+#if OPENSSL_VERSION_NUMBER < 0x10100000L || defined (LIBRESSL_VERSION_NUMBER)
+#define EVP_PKEY_get0_RSA(_pkey) _pkey->pkey.rsa
+#define EVP_PKEY_get0_DH(_pkey) _pkey->pkey.dh
+#define EVP_PKEY_get0_DSA(_pkey) _pkey->pkey.dsa
+#define EVP_PKEY_get0_EC_KEY(_pkey) _pkey->pkey.ec
+
+static int RSA_set0_key(RSA *r, BIGNUM *n, BIGNUM *e, BIGNUM *d)
+{
+	r->n = n;
+	r->e = e;
+	r->d = d;
+
+	return 1;
+}
+
+static int RSA_set0_factors(RSA *r, BIGNUM *p, BIGNUM *q)
+{
+	r->p = p;
+	r->q = q;
+
+	return 1;
+}
+
+static int RSA_set0_crt_params(RSA *r, BIGNUM *dmp1, BIGNUM *dmq1, BIGNUM *iqmp)
+{
+	r->dmp1 = dmp1;
+	r->dmq1 = dmq1;
+	r->iqmp = iqmp;
+
+	return 1;
+}
+
+static void RSA_get0_key(const RSA *r, const BIGNUM **n, const BIGNUM **e, const BIGNUM **d)
+{
+	*n = r->n;
+	*e = r->e;
+	*d = r->d;
+}
+
+static void RSA_get0_factors(const RSA *r, const BIGNUM **p, const BIGNUM **q)
+{
+	*p = r->p;
+	*q = r->q;
+}
+
+static void RSA_get0_crt_params(const RSA *r, const BIGNUM **dmp1, const BIGNUM **dmq1, const BIGNUM **iqmp)
+{
+	*dmp1 = r->dmp1;
+	*dmq1 = r->dmq1;
+	*iqmp = r->iqmp;
+}
+
+static void DH_get0_pqg(const DH *dh, const BIGNUM **p, const BIGNUM **q, const BIGNUM **g)
+{
+	*p = dh->p;
+	*q = dh->q;
+	*g = dh->g;
+}
+
+static int DH_set0_pqg(DH *dh, BIGNUM *p, BIGNUM *q, BIGNUM *g)
+{
+	dh->p = p;
+	dh->q = q;
+	dh->g = g;
+
+	return 1;
+}
+
+static void DH_get0_key(const DH *dh, const BIGNUM **pub_key, const BIGNUM **priv_key)
+{
+	*pub_key = dh->pub_key;
+	*priv_key = dh->priv_key;
+}
+
+static int DH_set0_key(DH *dh, BIGNUM *pub_key, BIGNUM *priv_key)
+{
+	dh->pub_key = pub_key;
+	dh->priv_key = priv_key;
+
+	return 1;
+}
+
+static void DSA_get0_pqg(const DSA *d, const BIGNUM **p, const BIGNUM **q, const BIGNUM **g)
+{
+	*p = d->p;
+	*q = d->q;
+	*g = d->g;
+}
+
+int DSA_set0_pqg(DSA *d, BIGNUM *p, BIGNUM *q, BIGNUM *g)
+{
+	d->p = p;
+	d->q = q;
+	d->g = g;
+
+	return 1;
+}
+
+static void DSA_get0_key(const DSA *d, const BIGNUM **pub_key, const BIGNUM **priv_key)
+{
+	*pub_key = d->pub_key;
+	*priv_key = d->priv_key;
+}
+
+int DSA_set0_key(DSA *d, BIGNUM *pub_key, BIGNUM *priv_key)
+{
+	d->pub_key = pub_key;
+	d->priv_key = priv_key;
+
+	return 1;
+}
+
+#if OPENSSL_VERSION_NUMBER < 0x10002000L || defined (LIBRESSL_VERSION_NUMBER)
+
+static int X509_get_signature_nid(const X509 *x)
+{
+	return OBJ_obj2nid(x->sig_alg->algorithm);
+}
+
+#endif
+
+#endif
+/* }}} */
+
 static int le_key;
 static int le_x509;
 static int le_csr;
@@ -825,7 +956,7 @@ static int add_oid_section(struct php_x509_request * req TSRMLS_DC) /* {{{ */
 	}
 	for (i = 0; i < sk_CONF_VALUE_num(sktmp); i++) {
 		cnf = sk_CONF_VALUE_value(sktmp, i);
-		if (OBJ_create(cnf->value, cnf->name, cnf->name) == NID_undef) {
+		if (OBJ_sn2nid(cnf->name) == NID_undef && OBJ_ln2nid(cnf->name) == NID_undef && OBJ_create(cnf->value, cnf->name, cnf->name) == NID_undef) {
 			php_error_docref(NULL TSRMLS_CC, E_WARNING, "problem creating object %s=%s", cnf->name, cnf->value);
 			return FAILURE;
 		}
@@ -967,7 +1098,7 @@ static void php_openssl_dispose_config(struct php_x509_request * req TSRMLS_DC)
 }
 /* }}} */
 
-#ifdef PHP_WIN32
+#if defined(PHP_WIN32) || (OPENSSL_VERSION_NUMBER >= 0x10100000L && !defined(LIBRESSL_VERSION_NUMBER))
 #define PHP_OPENSSL_RAND_ADD_TIME() ((void) 0)
 #else
 #define PHP_OPENSSL_RAND_ADD_TIME() php_openssl_rand_add_timeval()
@@ -1053,9 +1184,11 @@ static EVP_MD * php_openssl_get_evp_md_from_algo(long algo) { /* {{{ */
 			mdtype = (EVP_MD *) EVP_md2();
 			break;
 #endif
+#if OPENSSL_VERSION_NUMBER < 0x10100000L || defined (LIBRESSL_VERSION_NUMBER)
 		case OPENSSL_ALGO_DSS1:
 			mdtype = (EVP_MD *) EVP_dss1();
 			break;
+#endif
 #if OPENSSL_VERSION_NUMBER >= 0x0090708fL
 		case OPENSSL_ALGO_SHA224:
 			mdtype = (EVP_MD *) EVP_sha224();
@@ -1146,6 +1279,12 @@ PHP_MINIT_FUNCTION(openssl)
 	OpenSSL_add_all_digests();
 	OpenSSL_add_all_algorithms();
 
+#if !defined(OPENSSL_NO_AES) && defined(EVP_CIPH_CCM_MODE) && OPENSSL_VERSION_NUMBER < 0x100020000
+	EVP_add_cipher(EVP_aes_128_ccm());
+	EVP_add_cipher(EVP_aes_192_ccm());
+	EVP_add_cipher(EVP_aes_256_ccm());
+#endif
+
 	SSL_load_error_strings();
 
 	/* register a resource id number with OpenSSL so that we can map SSL -> stream structures in
@@ -1173,7 +1312,9 @@ PHP_MINIT_FUNCTION(openssl)
 #ifdef HAVE_OPENSSL_MD2_H
 	REGISTER_LONG_CONSTANT("OPENSSL_ALGO_MD2", OPENSSL_ALGO_MD2, CONST_CS|CONST_PERSISTENT);
 #endif
+#if OPENSSL_VERSION_NUMBER < 0x10100000L || defined (LIBRESSL_VERSION_NUMBER)
 	REGISTER_LONG_CONSTANT("OPENSSL_ALGO_DSS1", OPENSSL_ALGO_DSS1, CONST_CS|CONST_PERSISTENT);
+#endif
 #if OPENSSL_VERSION_NUMBER >= 0x0090708fL
 	REGISTER_LONG_CONSTANT("OPENSSL_ALGO_SHA224", OPENSSL_ALGO_SHA224, CONST_CS|CONST_PERSISTENT);
 	REGISTER_LONG_CONSTANT("OPENSSL_ALGO_SHA256", OPENSSL_ALGO_SHA256, CONST_CS|CONST_PERSISTENT);
@@ -1251,7 +1392,9 @@ PHP_MINIT_FUNCTION(openssl)
 	}
 
 	php_stream_xport_register("ssl", php_openssl_ssl_socket_factory TSRMLS_CC);
+#ifndef OPENSSL_NO_SSL3
 	php_stream_xport_register("sslv3", php_openssl_ssl_socket_factory TSRMLS_CC);
+#endif
 #ifndef OPENSSL_NO_SSL2
 	php_stream_xport_register("sslv2", php_openssl_ssl_socket_factory TSRMLS_CC);
 #endif
@@ -1308,7 +1451,9 @@ PHP_MSHUTDOWN_FUNCTION(openssl)
 #ifndef OPENSSL_NO_SSL2
 	php_stream_xport_unregister("sslv2" TSRMLS_CC);
 #endif
+#ifndef OPENSSL_NO_SSL3
 	php_stream_xport_unregister("sslv3" TSRMLS_CC);
+#endif
 	php_stream_xport_unregister("tls" TSRMLS_CC);
 	php_stream_xport_unregister("tlsv1.0" TSRMLS_CC);
 #if OPENSSL_VERSION_NUMBER >= 0x10001001L
@@ -1893,6 +2038,7 @@ static int openssl_x509v3_subjectAltName(BIO *bio, X509_EXTENSION *extension)
 {
 	GENERAL_NAMES *names;
 	const X509V3_EXT_METHOD *method = NULL;
+	ASN1_OCTET_STRING *extension_data;
 	long i, length, num;
 	const unsigned char *p;
 
@@ -1901,8 +2047,9 @@ static int openssl_x509v3_subjectAltName(BIO *bio, X509_EXTENSION *extension)
 		return -1;
 	}
 
-	p = extension->value->data;
-	length = extension->value->length;
+	extension_data = X509_EXTENSION_get_data(extension);
+	p = extension_data->data;
+	length = extension_data->length;
 	if (method->it) {
 		names = (GENERAL_NAMES*)(ASN1_item_d2i(NULL, &p, length,
 						       ASN1_ITEM_ptr(method->it)));
@@ -1965,6 +2112,8 @@ PHP_FUNCTION(openssl_x509_parse)
 	char * tmpstr;
 	zval * subitem;
 	X509_EXTENSION *extension;
+	X509_NAME *subject_name;
+	char *cert_name;
 	char *extname;
 	BIO  *bio_out;
 	BUF_MEM *bio_buf;
@@ -1979,10 +2128,10 @@ PHP_FUNCTION(openssl_x509_parse)
 	}
 	array_init(return_value);
 
-	if (cert->name) {
-		add_assoc_string(return_value, "name", cert->name, 1);
-	}
-/*	add_assoc_bool(return_value, "valid", cert->valid); */
+	subject_name = X509_get_subject_name(cert);
+	cert_name = X509_NAME_oneline(subject_name, NULL, 0);
+	add_assoc_string(return_value, "name", cert_name, 1);
+	OPENSSL_free(cert_name);
 
 	add_assoc_name_entry(return_value, "subject", 		X509_get_subject_name(cert), useshortnames TSRMLS_CC);
 	/* hash as used in CA directories to lookup cert by subject name */
@@ -2008,7 +2157,7 @@ PHP_FUNCTION(openssl_x509_parse)
 		add_assoc_string(return_value, "alias", tmpstr, 1);
 	}
 
-	sig_nid = OBJ_obj2nid((cert)->sig_alg->algorithm);
+	sig_nid = X509_get_signature_nid(cert);
 	add_assoc_string(return_value, "signatureTypeSN", (char*)OBJ_nid2sn(sig_nid), 1);
 	add_assoc_string(return_value, "signatureTypeLN", (char*)OBJ_nid2ln(sig_nid), 1);
 	add_assoc_long(return_value, "signatureTypeNID", sig_nid);
@@ -3217,7 +3366,21 @@ PHP_FUNCTION(openssl_csr_get_public_key)
 		RETURN_FALSE;
 	}
 
-	tpubkey=X509_REQ_get_pubkey(csr);
+#if OPENSSL_VERSION_NUMBER >= 0x10100000L && !defined(LIBRESSL_VERSION_NUMBER)
+	/* Due to changes in OpenSSL 1.1 related to locking when decoding CSR,
+	 * the pub key is not changed after assigning. It means if we pass
+	 * a private key, it will be returned including the private part.
+	 * If we duplicate it, then we get just the public part which is
+	 * the same behavior as for OpenSSL 1.0 */
+	csr = X509_REQ_dup(csr);
+#endif
+	/* Retrieve the public key from the CSR */
+	tpubkey = X509_REQ_get_pubkey(csr);
+
+#if OPENSSL_VERSION_NUMBER >= 0x10100000L && !defined(LIBRESSL_VERSION_NUMBER)
+	/* We need to free the CSR as it was duplicated */
+	X509_REQ_free(csr);
+#endif
 	RETVAL_RESOURCE(zend_list_insert(tpubkey, le_key TSRMLS_CC));
 	return;
 }
@@ -3482,13 +3645,20 @@ static int php_openssl_is_private_key(EVP_PKEY* pkey TSRMLS_DC)
 {
 	assert(pkey != NULL);
 
-	switch (pkey->type) {
+	switch (EVP_PKEY_id(pkey)) {
 #ifndef NO_RSA
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
-			assert(pkey->pkey.rsa != NULL);
-			if (pkey->pkey.rsa != NULL && (NULL == pkey->pkey.rsa->p || NULL == pkey->pkey.rsa->q)) {
-				return 0;
+			{
+				RSA *rsa = EVP_PKEY_get0_RSA(pkey);
+				if (rsa != NULL) {
+					const BIGNUM *p, *q;
+
+					RSA_get0_factors(rsa, &p, &q);
+					 if (p == NULL || q == NULL) {
+						return 0;
+					 }
+				}
 			}
 			break;
 #endif
@@ -3498,28 +3668,51 @@ static int php_openssl_is_private_key(EVP_PKEY* pkey TSRMLS_DC)
 		case EVP_PKEY_DSA2:
 		case EVP_PKEY_DSA3:
 		case EVP_PKEY_DSA4:
-			assert(pkey->pkey.dsa != NULL);
-
-			if (NULL == pkey->pkey.dsa->p || NULL == pkey->pkey.dsa->q || NULL == pkey->pkey.dsa->priv_key){ 
-				return 0;
+			{
+				DSA *dsa = EVP_PKEY_get0_DSA(pkey);
+				if (dsa != NULL) {
+					const BIGNUM *p, *q, *g, *pub_key, *priv_key;
+
+					DSA_get0_pqg(dsa, &p, &q, &g);
+					if (p == NULL || q == NULL) {
+						return 0;
+					}
+ 
+					DSA_get0_key(dsa, &pub_key, &priv_key);
+					if (priv_key == NULL) {
+						return 0;
+					}
+				}
 			}
 			break;
 #endif
 #ifndef NO_DH
 		case EVP_PKEY_DH:
-			assert(pkey->pkey.dh != NULL);
-
-			if (NULL == pkey->pkey.dh->p || NULL == pkey->pkey.dh->priv_key) {
-				return 0;
+			{
+				DH *dh = EVP_PKEY_get0_DH(pkey);
+				if (dh != NULL) {
+					const BIGNUM *p, *q, *g, *pub_key, *priv_key;
+
+					DH_get0_pqg(dh, &p, &q, &g);
+					if (p == NULL) {
+						return 0;
+					}
+ 
+					DH_get0_key(dh, &pub_key, &priv_key);
+					if (priv_key == NULL) {
+						return 0;
+					}
+				}
 			}
 			break;
 #endif
 #ifdef HAVE_EVP_PKEY_EC
 		case EVP_PKEY_EC:
-			assert(pkey->pkey.ec != NULL);
-
-			if ( NULL == EC_KEY_get0_private_key(pkey->pkey.ec)) {
-				return 0;
+			{
+				EC_KEY *ec = EVP_PKEY_get0_EC_KEY(pkey);
+				if (ec != NULL && NULL == EC_KEY_get0_private_key(ec)) {
+					return 0;
+				}
 			}
 			break;
 #endif
@@ -3531,34 +3724,80 @@ static int php_openssl_is_private_key(EVP_PKEY* pkey TSRMLS_DC)
 }
 /* }}} */
 
-#define OPENSSL_PKEY_GET_BN(_type, _name) do {							\
-		if (pkey->pkey._type->_name != NULL) {							\
-			int len = BN_num_bytes(pkey->pkey._type->_name);			\
-			char *str = emalloc(len + 1);								\
-			BN_bn2bin(pkey->pkey._type->_name, (unsigned char*)str);	\
-			str[len] = 0;                                           	\
-			add_assoc_stringl(_type, #_name, str, len, 0);				\
-		}																\
-	} while (0)
-
-#define OPENSSL_PKEY_SET_BN(_ht, _type, _name) do {						\
-		zval **bn;														\
-		if (zend_hash_find(_ht, #_name, sizeof(#_name),	(void**)&bn) == SUCCESS && \
-				Z_TYPE_PP(bn) == IS_STRING) {							\
-			_type->_name = BN_bin2bn(									\
-				(unsigned char*)Z_STRVAL_PP(bn),						\
-	 			Z_STRLEN_PP(bn), NULL);									\
-	    }                                                               \
+#define OPENSSL_GET_BN(_array, _bn, _name) do { \
+		if (_bn != NULL) { \
+			int len = BN_num_bytes(_bn); \
+			char *str = emalloc(len + 1); \
+			BN_bn2bin(_bn, (unsigned char*)str); \
+			str[len] = 0; \
+			add_assoc_stringl(_array, #_name, str, len, 0); \
+		} \
 	} while (0);
 
+#define OPENSSL_PKEY_GET_BN(_type, _name) OPENSSL_GET_BN(_type, _name, _name)
+
+#define OPENSSL_PKEY_SET_BN(_data, _name) do { \
+		zval **bn; \
+		if (zend_hash_find(Z_ARRVAL_P(_data), #_name, sizeof(#_name),(void**)&bn) == SUCCESS && \
+				Z_TYPE_PP(bn) == IS_STRING) { \
+			_name = BN_bin2bn( \
+				(unsigned char*)Z_STRVAL_PP(bn), \
+				Z_STRLEN_PP(bn), NULL); \
+		} else { \
+			_name = NULL; \
+		} \
+ 	} while (0);
+
+/* {{{ php_openssl_pkey_init_rsa */
+zend_bool php_openssl_pkey_init_and_assign_rsa(EVP_PKEY *pkey, RSA *rsa, zval *data)
+{
+	BIGNUM *n, *e, *d, *p, *q, *dmp1, *dmq1, *iqmp;
+
+	OPENSSL_PKEY_SET_BN(data, n);
+	OPENSSL_PKEY_SET_BN(data, e);
+	OPENSSL_PKEY_SET_BN(data, d);
+	if (!n || !d || !RSA_set0_key(rsa, n, e, d)) {
+		return 0;
+	}
+
+	OPENSSL_PKEY_SET_BN(data, p);
+	OPENSSL_PKEY_SET_BN(data, q);
+	if ((p || q) && !RSA_set0_factors(rsa, p, q)) {
+		return 0;
+	}
+
+	OPENSSL_PKEY_SET_BN(data, dmp1);
+	OPENSSL_PKEY_SET_BN(data, dmq1);
+	OPENSSL_PKEY_SET_BN(data, iqmp);
+	if ((dmp1 || dmq1 || iqmp) && !RSA_set0_crt_params(rsa, dmp1, dmq1, iqmp)) {
+		return 0;
+	}
+
+	if (!EVP_PKEY_assign_RSA(pkey, rsa)) {
+		return 0;
+	}
+
+	return 1;
+}
+/* }}} */
+
 /* {{{ php_openssl_pkey_init_dsa */
-zend_bool php_openssl_pkey_init_dsa(DSA *dsa)
+zend_bool php_openssl_pkey_init_dsa(DSA *dsa, zval *data)
 {
-	if (!dsa->p || !dsa->q || !dsa->g) {
+	BIGNUM *p, *q, *g, *priv_key, *pub_key;
+	const BIGNUM *priv_key_const, *pub_key_const;
+
+	OPENSSL_PKEY_SET_BN(data, p);
+	OPENSSL_PKEY_SET_BN(data, q);
+	OPENSSL_PKEY_SET_BN(data, g);
+	if (!p || !q || !g || !DSA_set0_pqg(dsa, p, q, g)) {
 		return 0;
 	}
-	if (dsa->priv_key || dsa->pub_key) {
-		return 1;
+
+	OPENSSL_PKEY_SET_BN(data, pub_key);
+	OPENSSL_PKEY_SET_BN(data, priv_key);
+	if (pub_key) {
+		return DSA_set0_key(dsa, pub_key, priv_key);
 	}
 	PHP_OPENSSL_RAND_ADD_TIME();
 	if (!DSA_generate_key(dsa)) {
@@ -3566,7 +3805,8 @@ zend_bool php_openssl_pkey_init_dsa(DSA *dsa)
 	}
 	/* if BN_mod_exp return -1, then DSA_generate_key succeed for failed key
 	 * so we need to double check that public key is created */
-	if (!dsa->pub_key || BN_is_zero(dsa->pub_key)) {
+	DSA_get0_key(dsa, &pub_key_const, &priv_key_const);
+	if (!pub_key_const || BN_is_zero(pub_key_const)) {
 		return 0;
 	}
 	/* all good */
@@ -3574,14 +3814,66 @@ zend_bool php_openssl_pkey_init_dsa(DSA *dsa)
 }
 /* }}} */
 
+/* {{{ php_openssl_dh_pub_from_priv */
+static BIGNUM *php_openssl_dh_pub_from_priv(BIGNUM *priv_key, BIGNUM *g, BIGNUM *p)
+{
+	BIGNUM *pub_key, *priv_key_const_time;
+	BN_CTX *ctx;
+
+	pub_key = BN_new();
+	if (pub_key == NULL) {
+		return NULL;
+	}
+
+	priv_key_const_time = BN_new();
+	if (priv_key_const_time == NULL) {
+		BN_free(pub_key);
+		return NULL;
+	}
+	ctx = BN_CTX_new();
+	if (ctx == NULL) {
+		BN_free(pub_key);
+		BN_free(priv_key_const_time);
+		return NULL;
+	}
+
+	BN_with_flags(priv_key_const_time, priv_key, BN_FLG_CONSTTIME);
+
+	if (!BN_mod_exp_mont(pub_key, g, priv_key_const_time, p, ctx, NULL)) {
+		BN_free(pub_key);
+		pub_key = NULL;
+	}
+
+	BN_free(priv_key_const_time);
+	BN_CTX_free(ctx);
+
+	return pub_key;
+}
+/* }}} */
+
 /* {{{ php_openssl_pkey_init_dh */
-zend_bool php_openssl_pkey_init_dh(DH *dh)
+zend_bool php_openssl_pkey_init_dh(DH *dh, zval *data)
 {
-	if (!dh->p || !dh->g) {
+	BIGNUM *p, *q, *g, *priv_key, *pub_key;
+
+	OPENSSL_PKEY_SET_BN(data, p);
+	OPENSSL_PKEY_SET_BN(data, q);
+	OPENSSL_PKEY_SET_BN(data, g);
+	if (!p || !g || !DH_set0_pqg(dh, p, q, g)) {
 		return 0;
 	}
-	if (dh->pub_key) {
-		return 1;
+
+	OPENSSL_PKEY_SET_BN(data, priv_key);
+	OPENSSL_PKEY_SET_BN(data, pub_key);
+	if (pub_key) {
+		return DH_set0_key(dh, pub_key, priv_key);
+	}
+	if (priv_key) {
+		pub_key = php_openssl_dh_pub_from_priv(priv_key, g, p);
+		if (pub_key == NULL) {
+			return 0;
+		}
+		return DH_set0_key(dh, pub_key, priv_key);
 	}
 	PHP_OPENSSL_RAND_ADD_TIME();
 	if (!DH_generate_key(dh)) {
@@ -3614,18 +3906,8 @@ PHP_FUNCTION(openssl_pkey_new)
 		    if (pkey) {
 				RSA *rsa = RSA_new();
 				if (rsa) {
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, n);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, e);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, d);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, p);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, q);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, dmp1);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, dmq1);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), rsa, iqmp);
-					if (rsa->n && rsa->d) {
-						if (EVP_PKEY_assign_RSA(pkey, rsa)) {
-							RETURN_RESOURCE(zend_list_insert(pkey, le_key TSRMLS_CC));
-						}
+					if (php_openssl_pkey_init_and_assign_rsa(pkey, rsa, *data)) {
+						RETURN_RESOURCE(zend_list_insert(pkey, le_key TSRMLS_CC));
 					}
 					RSA_free(rsa);
 				}
@@ -3638,12 +3920,7 @@ PHP_FUNCTION(openssl_pkey_new)
 		    if (pkey) {
 				DSA *dsa = DSA_new();
 				if (dsa) {
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dsa, p);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dsa, q);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dsa, g);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dsa, priv_key);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dsa, pub_key);
-					if (php_openssl_pkey_init_dsa(dsa)) {
+					if (php_openssl_pkey_init_dsa(dsa, *data)) {
 						if (EVP_PKEY_assign_DSA(pkey, dsa)) {
 							RETURN_RESOURCE(zend_list_insert(pkey, le_key TSRMLS_CC));
 						}
@@ -3659,11 +3936,7 @@ PHP_FUNCTION(openssl_pkey_new)
 		    if (pkey) {
 				DH *dh = DH_new();
 				if (dh) {
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dh, p);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dh, g);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dh, priv_key);
-					OPENSSL_PKEY_SET_BN(Z_ARRVAL_PP(data), dh, pub_key);
-					if (php_openssl_pkey_init_dh(dh)) {
+					if (php_openssl_pkey_init_dh(dh, *data)) {
 						if (EVP_PKEY_assign_DH(pkey, dh)) {
 							RETURN_RESOURCE(zend_list_insert(pkey, le_key TSRMLS_CC));
 						}
@@ -3738,10 +4011,10 @@ PHP_FUNCTION(openssl_pkey_export_to_file)
 			cipher = NULL;
 		}
 
-		switch (EVP_PKEY_type(key->type)) {
+		switch (EVP_PKEY_base_id(key)) {
 #ifdef HAVE_EVP_PKEY_EC
 			case EVP_PKEY_EC:
-				pem_write = PEM_write_bio_ECPrivateKey(bio_out, EVP_PKEY_get1_EC_KEY(key), cipher, (unsigned char *)passphrase, passphrase_len, NULL, NULL);
+				pem_write = PEM_write_bio_ECPrivateKey(bio_out, EVP_PKEY_get0_EC_KEY(key), cipher, (unsigned char *)passphrase, passphrase_len, NULL, NULL);
 				break;
 #endif
 			default:
@@ -3807,7 +4080,7 @@ PHP_FUNCTION(openssl_pkey_export)
 			cipher = NULL;
 		}
 
-		switch (EVP_PKEY_type(key->type)) {
+		switch (EVP_PKEY_base_id(key)) {
 #ifdef HAVE_EVP_PKEY_EC
 			case EVP_PKEY_EC:
 				pem_write = PEM_write_bio_ECPrivateKey(bio_out, EVP_PKEY_get1_EC_KEY(key), cipher, (unsigned char *)passphrase, passphrase_len, NULL, NULL);
@@ -3928,25 +4201,33 @@ PHP_FUNCTION(openssl_pkey_get_details)
 	/*TODO: Use the real values once the openssl constants are used 
 	 * See the enum at the top of this file
 	 */
-	switch (EVP_PKEY_type(pkey->type)) {
+	switch (EVP_PKEY_base_id(pkey)) {
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
-			ktype = OPENSSL_KEYTYPE_RSA;
-
-			if (pkey->pkey.rsa != NULL) {
-				zval *rsa;
-
-				ALLOC_INIT_ZVAL(rsa);
-				array_init(rsa);
-				OPENSSL_PKEY_GET_BN(rsa, n);
-				OPENSSL_PKEY_GET_BN(rsa, e);
-				OPENSSL_PKEY_GET_BN(rsa, d);
-				OPENSSL_PKEY_GET_BN(rsa, p);
-				OPENSSL_PKEY_GET_BN(rsa, q);
-				OPENSSL_PKEY_GET_BN(rsa, dmp1);
-				OPENSSL_PKEY_GET_BN(rsa, dmq1);
-				OPENSSL_PKEY_GET_BN(rsa, iqmp);
-				add_assoc_zval(return_value, "rsa", rsa);
+			{
+				RSA *rsa = EVP_PKEY_get0_RSA(pkey);
+				ktype = OPENSSL_KEYTYPE_RSA;
+
+				if (rsa != NULL) {
+					zval *z_rsa;
+					const BIGNUM *n, *e, *d, *p, *q, *dmp1, *dmq1, *iqmp;
+
+					RSA_get0_key(rsa, &n, &e, &d);
+					RSA_get0_factors(rsa, &p, &q);
+					RSA_get0_crt_params(rsa, &dmp1, &dmq1, &iqmp);
+
+					ALLOC_INIT_ZVAL(z_rsa);
+					array_init(z_rsa);
+					OPENSSL_PKEY_GET_BN(z_rsa, n);
+					OPENSSL_PKEY_GET_BN(z_rsa, e);
+					OPENSSL_PKEY_GET_BN(z_rsa, d);
+					OPENSSL_PKEY_GET_BN(z_rsa, p);
+					OPENSSL_PKEY_GET_BN(z_rsa, q);
+					OPENSSL_PKEY_GET_BN(z_rsa, dmp1);
+					OPENSSL_PKEY_GET_BN(z_rsa, dmq1);
+					OPENSSL_PKEY_GET_BN(z_rsa, iqmp);
+					add_assoc_zval(return_value, "rsa", z_rsa);
+				}
 			}
 
 			break;	
@@ -3954,42 +4235,55 @@ PHP_FUNCTION(openssl_pkey_get_details)
 		case EVP_PKEY_DSA2:
 		case EVP_PKEY_DSA3:
 		case EVP_PKEY_DSA4:
-			ktype = OPENSSL_KEYTYPE_DSA;
-
-			if (pkey->pkey.dsa != NULL) {
-				zval *dsa;
-
-				ALLOC_INIT_ZVAL(dsa);
-				array_init(dsa);
-				OPENSSL_PKEY_GET_BN(dsa, p);
-				OPENSSL_PKEY_GET_BN(dsa, q);
-				OPENSSL_PKEY_GET_BN(dsa, g);
-				OPENSSL_PKEY_GET_BN(dsa, priv_key);
-				OPENSSL_PKEY_GET_BN(dsa, pub_key);
-				add_assoc_zval(return_value, "dsa", dsa);
+			{
+				DSA *dsa = EVP_PKEY_get0_DSA(pkey);
+				ktype = OPENSSL_KEYTYPE_DSA;
+
+				if (dsa != NULL) {
+					zval *z_dsa;
+					const BIGNUM *p, *q, *g, *priv_key, *pub_key;
+
+					DSA_get0_pqg(dsa, &p, &q, &g);
+					DSA_get0_key(dsa, &pub_key, &priv_key);
+
+					ALLOC_INIT_ZVAL(z_dsa);
+					array_init(z_dsa);
+					OPENSSL_PKEY_GET_BN(z_dsa, p);
+					OPENSSL_PKEY_GET_BN(z_dsa, q);
+					OPENSSL_PKEY_GET_BN(z_dsa, g);
+					OPENSSL_PKEY_GET_BN(z_dsa, priv_key);
+					OPENSSL_PKEY_GET_BN(z_dsa, pub_key);
+					add_assoc_zval(return_value, "dsa", z_dsa);
+				}
 			}
 			break;
 		case EVP_PKEY_DH:
-			
-			ktype = OPENSSL_KEYTYPE_DH;
-
-			if (pkey->pkey.dh != NULL) {
-				zval *dh;
-
-				ALLOC_INIT_ZVAL(dh);
-				array_init(dh);
-				OPENSSL_PKEY_GET_BN(dh, p);
-				OPENSSL_PKEY_GET_BN(dh, g);
-				OPENSSL_PKEY_GET_BN(dh, priv_key);
-				OPENSSL_PKEY_GET_BN(dh, pub_key);
-				add_assoc_zval(return_value, "dh", dh);
+			{
+				DH *dh = EVP_PKEY_get0_DH(pkey);
+				ktype = OPENSSL_KEYTYPE_DH;
+
+				if (dh != NULL) {
+					zval *z_dh;
+					const BIGNUM *p, *q, *g, *priv_key, *pub_key;
+
+					DH_get0_pqg(dh, &p, &q, &g);
+					DH_get0_key(dh, &pub_key, &priv_key);
+
+					ALLOC_INIT_ZVAL(z_dh);
+					array_init(z_dh);
+					OPENSSL_PKEY_GET_BN(z_dh, p);
+					OPENSSL_PKEY_GET_BN(z_dh, g);
+					OPENSSL_PKEY_GET_BN(z_dh, priv_key);
+					OPENSSL_PKEY_GET_BN(z_dh, pub_key);
+					add_assoc_zval(return_value, "dh", z_dh);
+				}
 			}
 
 			break;
 #ifdef HAVE_EVP_PKEY_EC
 		case EVP_PKEY_EC:
 			ktype = OPENSSL_KEYTYPE_EC;
-			if (pkey->pkey.ec != NULL) {
+			if (EVP_PKEY_get0_EC_KEY(pkey) != NULL) {
 				zval *ec;
 				const EC_GROUP *ec_group;
 				int nid;
@@ -4546,13 +4840,13 @@ PHP_FUNCTION(openssl_private_encrypt)
 	cryptedlen = EVP_PKEY_size(pkey);
 	cryptedbuf = emalloc(cryptedlen + 1);
 
-	switch (pkey->type) {
+	switch (EVP_PKEY_id(pkey)) {
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
 			successful =  (RSA_private_encrypt(data_len, 
 						(unsigned char *)data, 
 						cryptedbuf, 
-						pkey->pkey.rsa, 
+						EVP_PKEY_get0_RSA(pkey), 
 						padding) == cryptedlen);
 			break;
 		default:
@@ -4604,13 +4898,13 @@ PHP_FUNCTION(openssl_private_decrypt)
 	cryptedlen = EVP_PKEY_size(pkey);
 	crypttemp = emalloc(cryptedlen + 1);
 
-	switch (pkey->type) {
+	switch (EVP_PKEY_id(pkey)) {
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
 			cryptedlen = RSA_private_decrypt(data_len, 
 					(unsigned char *)data, 
 					crypttemp, 
-					pkey->pkey.rsa, 
+					EVP_PKEY_get0_RSA(pkey), 
 					padding);
 			if (cryptedlen != -1) {
 				cryptedbuf = emalloc(cryptedlen + 1);
@@ -4669,13 +4963,13 @@ PHP_FUNCTION(openssl_public_encrypt)
 	cryptedlen = EVP_PKEY_size(pkey);
 	cryptedbuf = emalloc(cryptedlen + 1);
 
-	switch (pkey->type) {
+	switch (EVP_PKEY_id(pkey)) {
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
 			successful = (RSA_public_encrypt(data_len, 
 						(unsigned char *)data, 
 						cryptedbuf, 
-						pkey->pkey.rsa, 
+						EVP_PKEY_get0_RSA(pkey), 
 						padding) == cryptedlen);
 			break;
 		default:
@@ -4728,13 +5022,13 @@ PHP_FUNCTION(openssl_public_decrypt)
 	cryptedlen = EVP_PKEY_size(pkey);
 	crypttemp = emalloc(cryptedlen + 1);
 
-	switch (pkey->type) {
+	switch (EVP_PKEY_id(pkey)) {
 		case EVP_PKEY_RSA:
 		case EVP_PKEY_RSA2:
 			cryptedlen = RSA_public_decrypt(data_len, 
 					(unsigned char *)data, 
 					crypttemp, 
-					pkey->pkey.rsa, 
+					EVP_PKEY_get0_RSA(pkey), 
 					padding);
 			if (cryptedlen != -1) {
 				cryptedbuf = emalloc(cryptedlen + 1);
@@ -4798,7 +5092,7 @@ PHP_FUNCTION(openssl_sign)
 	long keyresource = -1;
 	char * data;
 	int data_len;
-	EVP_MD_CTX md_ctx;
+	EVP_MD_CTX *md_ctx;
 	zval *method = NULL;
 	long signature_algo = OPENSSL_ALGO_SHA1;
 	const EVP_MD *mdtype;
@@ -4831,9 +5125,10 @@ PHP_FUNCTION(openssl_sign)
 	siglen = EVP_PKEY_size(pkey);
 	sigbuf = emalloc(siglen + 1);
 
-	EVP_SignInit(&md_ctx, mdtype);
-	EVP_SignUpdate(&md_ctx, data, data_len);
-	if (EVP_SignFinal (&md_ctx, sigbuf,(unsigned int *)&siglen, pkey)) {
+	md_ctx = EVP_MD_CTX_create();
+	EVP_SignInit(md_ctx, mdtype);
+	EVP_SignUpdate(md_ctx, data, data_len);
+	if (EVP_SignFinal (md_ctx, sigbuf,(unsigned int *)&siglen, pkey)) {
 		zval_dtor(signature);
 		sigbuf[siglen] = '\0';
 		ZVAL_STRINGL(signature, (char *)sigbuf, siglen, 0);
@@ -4842,7 +5137,7 @@ PHP_FUNCTION(openssl_sign)
 		efree(sigbuf);
 		RETVAL_FALSE;
 	}
-	EVP_MD_CTX_cleanup(&md_ctx);
+	EVP_MD_CTX_destroy(md_ctx);
 	if (keyresource == -1) {
 		EVP_PKEY_free(pkey);
 	}
@@ -4856,7 +5151,7 @@ PHP_FUNCTION(openssl_verify)
 	zval **key;
 	EVP_PKEY *pkey;
 	int err;
-	EVP_MD_CTX     md_ctx;
+	EVP_MD_CTX     *md_ctx;
 	const EVP_MD *mdtype;
 	long keyresource = -1;
 	char * data;	int data_len;
@@ -4890,10 +5185,11 @@ PHP_FUNCTION(openssl_verify)
 		RETURN_FALSE;
 	}
 
-	EVP_VerifyInit   (&md_ctx, mdtype);
-	EVP_VerifyUpdate (&md_ctx, data, data_len);
-	err = EVP_VerifyFinal (&md_ctx, (unsigned char *)signature, signature_len, pkey);
-	EVP_MD_CTX_cleanup(&md_ctx);
+	md_ctx = EVP_MD_CTX_create();
+	EVP_VerifyInit   (md_ctx, mdtype);
+	EVP_VerifyUpdate (md_ctx, data, data_len);
+	err = EVP_VerifyFinal (md_ctx, (unsigned char *)signature, signature_len, pkey);
+	EVP_MD_CTX_destroy(md_ctx);
 
 	if (keyresource == -1) {
 		EVP_PKEY_free(pkey);
@@ -4917,7 +5213,7 @@ PHP_FUNCTION(openssl_seal)
 	char *method =NULL;
 	int method_len = 0;
 	const EVP_CIPHER *cipher;
-	EVP_CIPHER_CTX ctx;
+	EVP_CIPHER_CTX *ctx;
 
 	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "szza/|s", &data, &data_len, &sealdata, &ekeys, &pubkeys, &method, &method_len) == FAILURE) {
 		return;
@@ -4950,6 +5246,7 @@ PHP_FUNCTION(openssl_seal)
 	memset(eks, 0, sizeof(*eks) * nkeys);
 	key_resources = safe_emalloc(nkeys, sizeof(long), 0);
 	memset(key_resources, 0, sizeof(*key_resources) * nkeys);
+	memset(pkeys, 0, sizeof(*pkeys) * nkeys);
 
 	/* get the public keys we are using to seal this data */
 	zend_hash_internal_pointer_reset_ex(pubkeysht, &pos);
@@ -4967,27 +5264,28 @@ PHP_FUNCTION(openssl_seal)
 		i++;
 	}
 
-	if (!EVP_EncryptInit(&ctx,cipher,NULL,NULL)) {
+	ctx = EVP_CIPHER_CTX_new();
+	if (ctx == NULL || !EVP_EncryptInit(ctx,cipher,NULL,NULL)) {
 		RETVAL_FALSE;
-		EVP_CIPHER_CTX_cleanup(&ctx);
+		EVP_CIPHER_CTX_free(ctx);
 		goto clean_exit;
 	}
 
 #if 0
 	/* Need this if allow ciphers that require initialization vector */
-	ivlen = EVP_CIPHER_CTX_iv_length(&ctx);
+	ivlen = EVP_CIPHER_CTX_iv_length(ctx);
 	iv = ivlen ? emalloc(ivlen + 1) : NULL;
 #endif
 	/* allocate one byte extra to make room for \0 */
-	buf = emalloc(data_len + EVP_CIPHER_CTX_block_size(&ctx));
-	EVP_CIPHER_CTX_cleanup(&ctx);
+	buf = emalloc(data_len + EVP_CIPHER_CTX_block_size(ctx));
+	EVP_CIPHER_CTX_cleanup(ctx);
 
-	if (EVP_SealInit(&ctx, cipher, eks, eksl, NULL, pkeys, nkeys) <= 0 ||
-			!EVP_SealUpdate(&ctx, buf, &len1, (unsigned char *)data, data_len) ||
-			!EVP_SealFinal(&ctx, buf + len1, &len2)) {
+	if (EVP_SealInit(ctx, cipher, eks, eksl, NULL, pkeys, nkeys) <= 0 ||
+			!EVP_SealUpdate(ctx, buf, &len1, (unsigned char *)data, data_len) ||
+			!EVP_SealFinal(ctx, buf + len1, &len2)) {
 		RETVAL_FALSE;
 		efree(buf);
-		EVP_CIPHER_CTX_cleanup(&ctx);
+		EVP_CIPHER_CTX_free(ctx);
 		goto clean_exit;
 	}
 
@@ -5018,7 +5316,7 @@ PHP_FUNCTION(openssl_seal)
 		efree(buf);
 	}
 	RETVAL_LONG(len1 + len2);
-	EVP_CIPHER_CTX_cleanup(&ctx);
+	EVP_CIPHER_CTX_free(ctx);
 
 clean_exit:
 	for (i=0; i<nkeys; i++) {
@@ -5045,7 +5343,7 @@ PHP_FUNCTION(openssl_open)
 	int len1, len2;
 	unsigned char *buf;
 	long keyresource = -1;
-	EVP_CIPHER_CTX ctx;
+	EVP_CIPHER_CTX *ctx;
 	char * data;	int data_len;
 	char * ekey;	int ekey_len;
 	char *method =NULL;
@@ -5074,8 +5372,9 @@ PHP_FUNCTION(openssl_open)
 	
 	buf = emalloc(data_len + 1);
 
-	if (EVP_OpenInit(&ctx, cipher, (unsigned char *)ekey, ekey_len, NULL, pkey) && EVP_OpenUpdate(&ctx, buf, &len1, (unsigned char *)data, data_len)) {
-		if (!EVP_OpenFinal(&ctx, buf + len1, &len2) || (len1 + len2 == 0)) {
+	ctx = EVP_CIPHER_CTX_new();
+	if (EVP_OpenInit(ctx, cipher, (unsigned char *)ekey, ekey_len, NULL, pkey) && EVP_OpenUpdate(ctx, buf, &len1, (unsigned char *)data, data_len)) {
+		if (!EVP_OpenFinal(ctx, buf + len1, &len2) || (len1 + len2 == 0)) {
 			efree(buf);
 			RETVAL_FALSE;
 		} else {
@@ -5091,7 +5390,7 @@ PHP_FUNCTION(openssl_open)
 	if (keyresource == -1) {
 		EVP_PKEY_free(pkey);
 	}
-	EVP_CIPHER_CTX_cleanup(&ctx);
+	EVP_CIPHER_CTX_free(ctx);
 }
 /* }}} */
 
@@ -5151,7 +5450,7 @@ PHP_FUNCTION(openssl_digest)
 	char *data, *method;
 	int data_len, method_len;
 	const EVP_MD *mdtype;
-	EVP_MD_CTX md_ctx;
+	EVP_MD_CTX *md_ctx;
 	int siglen;
 	unsigned char *sigbuf;
 
@@ -5167,9 +5466,10 @@ PHP_FUNCTION(openssl_digest)
 	siglen = EVP_MD_size(mdtype);
 	sigbuf = emalloc(siglen + 1);
 
-	EVP_DigestInit(&md_ctx, mdtype);
-	EVP_DigestUpdate(&md_ctx, (unsigned char *)data, data_len);
-	if (EVP_DigestFinal (&md_ctx, (unsigned char *)sigbuf, (unsigned int *)&siglen)) {
+	md_ctx = EVP_MD_CTX_create();
+	EVP_DigestInit(md_ctx, mdtype);
+	EVP_DigestUpdate(md_ctx, (unsigned char *)data, data_len);
+	if (EVP_DigestFinal (md_ctx, (unsigned char *)sigbuf, (unsigned int *)&siglen)) {
 		if (raw_output) {
 			sigbuf[siglen] = '\0';
 			RETVAL_STRINGL((char *)sigbuf, siglen, 0);
@@ -5185,6 +5485,8 @@ PHP_FUNCTION(openssl_digest)
 		efree(sigbuf);
 		RETVAL_FALSE;
 	}
+
+	EVP_MD_CTX_destroy(md_ctx);
 }
 /* }}} */
 
@@ -5230,7 +5532,7 @@ PHP_FUNCTION(openssl_encrypt)
 	char *data, *method, *password, *iv = "";
 	int data_len, method_len, password_len, iv_len = 0, max_iv_len;
 	const EVP_CIPHER *cipher_type;
-	EVP_CIPHER_CTX cipher_ctx;
+	EVP_CIPHER_CTX *cipher_ctx;
 	int i=0, outlen, keylen;
 	unsigned char *outbuf, *key;
 	zend_bool free_iv;
@@ -5262,19 +5564,24 @@ PHP_FUNCTION(openssl_encrypt)
 	outlen = data_len + EVP_CIPHER_block_size(cipher_type);
 	outbuf = safe_emalloc(outlen, 1, 1);
 
-	EVP_EncryptInit(&cipher_ctx, cipher_type, NULL, NULL);
+	cipher_ctx = EVP_CIPHER_CTX_new();
+	if (!cipher_ctx) {
+		php_error_docref(NULL TSRMLS_CC, E_WARNING, "Failed to create cipher context");
+		RETURN_FALSE;
+	}
+	EVP_EncryptInit(cipher_ctx, cipher_type, NULL, NULL);
 	if (password_len > keylen) {
-		EVP_CIPHER_CTX_set_key_length(&cipher_ctx, password_len);
+		EVP_CIPHER_CTX_set_key_length(cipher_ctx, password_len);
 	}
-	EVP_EncryptInit_ex(&cipher_ctx, NULL, NULL, key, (unsigned char *)iv);
+	EVP_EncryptInit_ex(cipher_ctx, NULL, NULL, key, (unsigned char *)iv);
 	if (options & OPENSSL_ZERO_PADDING) {
-		EVP_CIPHER_CTX_set_padding(&cipher_ctx, 0);
+		EVP_CIPHER_CTX_set_padding(cipher_ctx, 0);
 	}
 	if (data_len > 0) {
-		EVP_EncryptUpdate(&cipher_ctx, outbuf, &i, (unsigned char *)data, data_len);
+		EVP_EncryptUpdate(cipher_ctx, outbuf, &i, (unsigned char *)data, data_len);
 	}
 	outlen = i;
-	if (EVP_EncryptFinal(&cipher_ctx, (unsigned char *)outbuf + i, &i)) {
+	if (EVP_EncryptFinal(cipher_ctx, (unsigned char *)outbuf + i, &i)) {
 		outlen += i;
 		if (options & OPENSSL_RAW_DATA) {
 			outbuf[outlen] = '\0';
@@ -5301,7 +5608,8 @@ PHP_FUNCTION(openssl_encrypt)
 	if (free_iv) {
 		efree(iv);
 	}
-	EVP_CIPHER_CTX_cleanup(&cipher_ctx);
+	EVP_CIPHER_CTX_cleanup(cipher_ctx);
+	EVP_CIPHER_CTX_free(cipher_ctx);
 }
 /* }}} */
 
@@ -5313,7 +5621,7 @@ PHP_FUNCTION(openssl_decrypt)
 	char *data, *method, *password, *iv = "";
 	int data_len, method_len, password_len, iv_len = 0;
 	const EVP_CIPHER *cipher_type;
-	EVP_CIPHER_CTX cipher_ctx;
+	EVP_CIPHER_CTX *cipher_ctx;
 	int i, outlen, keylen;
 	unsigned char *outbuf, *key;
 	int base64_str_len;
@@ -5359,17 +5667,23 @@ PHP_FUNCTION(openssl_decrypt)
 	outlen = data_len + EVP_CIPHER_block_size(cipher_type);
 	outbuf = emalloc(outlen + 1);
 
-	EVP_DecryptInit(&cipher_ctx, cipher_type, NULL, NULL);
+	cipher_ctx = EVP_CIPHER_CTX_new();
+	if (!cipher_ctx) {
+		php_error_docref(NULL TSRMLS_CC, E_WARNING, "Failed to create cipher context");
+		RETURN_FALSE;
+	}
+
+	EVP_DecryptInit(cipher_ctx, cipher_type, NULL, NULL);
 	if (password_len > keylen) {
-		EVP_CIPHER_CTX_set_key_length(&cipher_ctx, password_len);
+		EVP_CIPHER_CTX_set_key_length(cipher_ctx, password_len);
 	}
-	EVP_DecryptInit_ex(&cipher_ctx, NULL, NULL, key, (unsigned char *)iv);
+	EVP_DecryptInit_ex(cipher_ctx, NULL, NULL, key, (unsigned char *)iv);
 	if (options & OPENSSL_ZERO_PADDING) {
-		EVP_CIPHER_CTX_set_padding(&cipher_ctx, 0);
+		EVP_CIPHER_CTX_set_padding(cipher_ctx, 0);
 	}
-	EVP_DecryptUpdate(&cipher_ctx, outbuf, &i, (unsigned char *)data, data_len);
+	EVP_DecryptUpdate(cipher_ctx, outbuf, &i, (unsigned char *)data, data_len);
 	outlen = i;
-	if (EVP_DecryptFinal(&cipher_ctx, (unsigned char *)outbuf + i, &i)) {
+	if (EVP_DecryptFinal(cipher_ctx, (unsigned char *)outbuf + i, &i)) {
 		outlen += i;
 		outbuf[outlen] = '\0';
 		RETVAL_STRINGL((char *)outbuf, outlen, 0);
@@ -5386,7 +5700,8 @@ PHP_FUNCTION(openssl_decrypt)
 	if (base64_str) {
 		efree(base64_str);
 	}
- 	EVP_CIPHER_CTX_cleanup(&cipher_ctx);
+ 	EVP_CIPHER_CTX_cleanup(cipher_ctx);
+ 	EVP_CIPHER_CTX_free(cipher_ctx);
 }
 /* }}} */
 
@@ -5424,6 +5739,7 @@ PHP_FUNCTION(openssl_dh_compute_key)
 	zval *key;
 	char *pub_str;
 	int pub_len;
+	DH *dh;
 	EVP_PKEY *pkey;
 	BIGNUM *pub;
 	char *data;
@@ -5433,14 +5749,21 @@ PHP_FUNCTION(openssl_dh_compute_key)
 		return;
 	}
 	ZEND_FETCH_RESOURCE(pkey, EVP_PKEY *, &key, -1, "OpenSSL key", le_key);
-	if (!pkey || EVP_PKEY_type(pkey->type) != EVP_PKEY_DH || !pkey->pkey.dh) {
+	if (pkey == NULL) {
+		RETURN_FALSE;
+	}
+	if (EVP_PKEY_base_id(pkey) != EVP_PKEY_DH) {
+		RETURN_FALSE;
+	}
+	dh = EVP_PKEY_get0_DH(pkey);
+	if (dh == NULL) {
 		RETURN_FALSE;
 	}
 
 	pub = BN_bin2bn((unsigned char*)pub_str, pub_len, NULL);
 
-	data = emalloc(DH_size(pkey->pkey.dh) + 1);
-	len = DH_compute_key((unsigned char*)data, pub, pkey->pkey.dh);
+	data = emalloc(DH_size(dh) + 1);
+	len = DH_compute_key((unsigned char*)data, pub, dh);
 
 	if (len >= 0) {
 		data[len] = 0;
diff --git a/ext/openssl/xp_ssl.c b/ext/openssl/xp_ssl.c
index d5490331d634..c2d477c1db2b 100644
--- a/ext/openssl/xp_ssl.c
+++ b/ext/openssl/xp_ssl.c
@@ -935,7 +935,7 @@ static int set_local_cert(SSL_CTX *ctx, php_stream *stream TSRMLS_DC) /* {{{ */
 static const SSL_METHOD *php_select_crypto_method(long method_value, int is_client TSRMLS_DC) /* {{{ */
 {
 	if (method_value == STREAM_CRYPTO_METHOD_SSLv2) {
-#ifndef OPENSSL_NO_SSL2
+#if !defined(OPENSSL_NO_SSL2) && OPENSSL_VERSION_NUMBER < 0x10100000L
 		return is_client ? SSLv2_client_method() : SSLv2_server_method();
 #else
 		php_error_docref(NULL TSRMLS_CC, E_WARNING,
@@ -1588,12 +1588,26 @@ int php_openssl_setup_crypto(php_stream *stream,
 }
 /* }}} */
 
+#define PHP_SSL_MAX_VERSION_LEN 32
+
+static char *php_ssl_cipher_get_version(const SSL_CIPHER *c, char *buffer, size_t max_len) /* {{{ */
+{
+	const char *version = SSL_CIPHER_get_version(c);
+	strncpy(buffer, version, max_len);
+	if (max_len <= strlen(version)) {
+		buffer[max_len - 1] = 0;
+	}
+	return buffer;
+}
+/* }}} */
+
 static zval *capture_session_meta(SSL *ssl_handle) /* {{{ */
 {
 	zval *meta_arr;
 	char *proto_str;
 	long proto = SSL_version(ssl_handle);
 	const SSL_CIPHER *cipher = SSL_get_current_cipher(ssl_handle);
+	char version_str[PHP_SSL_MAX_VERSION_LEN];
 
 	switch (proto) {
 #if OPENSSL_VERSION_NUMBER >= 0x10001001L
@@ -1611,7 +1625,7 @@ static zval *capture_session_meta(SSL *ssl_handle) /* {{{ */
 	add_assoc_string(meta_arr, "protocol", proto_str, 1);
 	add_assoc_string(meta_arr, "cipher_name", (char *) SSL_CIPHER_get_name(cipher), 1);
 	add_assoc_long(meta_arr, "cipher_bits", SSL_CIPHER_get_bits(cipher, NULL));
-	add_assoc_string(meta_arr, "cipher_version", SSL_CIPHER_get_version(cipher), 1);
+	add_assoc_string(meta_arr, "cipher_version", php_ssl_cipher_get_version(cipher, version_str, PHP_SSL_MAX_VERSION_LEN), 1);
 
 	return meta_arr;
 }
diff --git a/ext/phar/util.c b/ext/phar/util.c
index 828be8f9a23f..06e4e55da7af 100644
--- a/ext/phar/util.c
+++ b/ext/phar/util.c
@@ -1531,7 +1531,7 @@ int phar_verify_signature(php_stream *fp, size_t end_of_phar, php_uint32 sig_typ
 			BIO *in;
 			EVP_PKEY *key;
 			EVP_MD *mdtype = (EVP_MD *) EVP_sha1();
-			EVP_MD_CTX md_ctx;
+			EVP_MD_CTX *md_ctx;
 #else
 			int tempsig;
 #endif
@@ -1608,7 +1608,8 @@ int phar_verify_signature(php_stream *fp, size_t end_of_phar, php_uint32 sig_typ
 				return FAILURE;
 			}
 
-			EVP_VerifyInit(&md_ctx, mdtype);
+			md_ctx = EVP_MD_CTX_create();
+			EVP_VerifyInit(md_ctx, mdtype);
 			read_len = end_of_phar;
 
 			if (read_len > sizeof(buf)) {
@@ -1620,7 +1621,7 @@ int phar_verify_signature(php_stream *fp, size_t end_of_phar, php_uint32 sig_typ
 			php_stream_seek(fp, 0, SEEK_SET);
 
 			while (read_size && (len = php_stream_read(fp, (char*)buf, read_size)) > 0) {
-				EVP_VerifyUpdate (&md_ctx, buf, len);
+				EVP_VerifyUpdate (md_ctx, buf, len);
 				read_len -= (off_t)len;
 
 				if (read_len < read_size) {
@@ -1628,9 +1629,9 @@ int phar_verify_signature(php_stream *fp, size_t end_of_phar, php_uint32 sig_typ
 				}
 			}
 
-			if (EVP_VerifyFinal(&md_ctx, (unsigned char *)sig, sig_len, key) != 1) {
+			if (EVP_VerifyFinal(md_ctx, (unsigned char *)sig, sig_len, key) != 1) {
 				/* 1: signature verified, 0: signature does not match, -1: failed signature operation */
-				EVP_MD_CTX_cleanup(&md_ctx);
+				EVP_MD_CTX_destroy(md_ctx);
 
 				if (error) {
 					spprintf(error, 0, "broken openssl signature");
@@ -1639,7 +1640,7 @@ int phar_verify_signature(php_stream *fp, size_t end_of_phar, php_uint32 sig_typ
 				return FAILURE;
 			}
 
-			EVP_MD_CTX_cleanup(&md_ctx);
+			EVP_MD_CTX_destroy(md_ctx);
 #endif
 
 			*signature_len = phar_hex_str((const char*)sig, sig_len, signature TSRMLS_CC);
diff --git a/ext/openssl/openssl.c b/ext/openssl/openssl.c
index 6c3ae3cde80a..b53114cdf34d 100644
--- a/ext/openssl/openssl.c
+++ b/ext/openssl/openssl.c
@@ -651,6 +651,8 @@ int DSA_set0_key(DSA *d, BIGNUM *pub_key, BIGNUM *priv_key)
 }
 
 #if OPENSSL_VERSION_NUMBER < 0x10002000L || defined (LIBRESSL_VERSION_NUMBER)
+#define EVP_PKEY_id(_pkey) _pkey->type
+#define EVP_PKEY_base_id(_key) EVP_PKEY_type(_key->type)
 
 static int X509_get_signature_nid(const X509 *x)
 {
EOP
        )->strip(1)];
    }
}
