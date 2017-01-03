<?php
/**
 * Config file for WebService tester
 */

// an array of key => value pairs representing the testing servers:
// $servers = array(ServerName => URL);
$servers = array(
    #'xml' => 'http://vmws/examples/xml.php',
    #'json' => 'http://vmws/examples/json.php',
    'rsa' => 'http://192.168.200.1:8080/index',
);

// The default timezone used by all date/time functions
// Example: Europe/Paris
define('TIMEZONE', 'Asia/Shanghai');

// The methods signatures file
// Can be absolute or relative to the path of this config file.
// example: methods.signatures.txt
//define('FUNCTIONS', 'methods.txt');
$FUNCTIONS = array(
    #'xml' => 'methods.xml.txt',
    #'json' => 'methods.json.txt',
    'rsa' => 'methods.rsa.txt',
);

define('RSA_PRIVATE_KEY', "-----BEGIN RSA PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAL1chAk2ewrqjrC1
oYKtrBjESbm40UqwqqtoIwTh1V4qg1Nz092PJDHRERsTSHE/jrlmBFn4nZOXs8g7
rbwDZ1lbQZ4R2Lnm5tsM/AxCN2PWkOG9YloSlxUzYT48lMB0U/x6gF+n1EX6KCmo
dkuY9Bq12+q4k1ZSZS6Nh4UmM+Q5AgMBAAECgYEAo3sv8tQ6Ph5qulzU54EQxwEP
tWu+JQGJFxp8wUZHc1i4sF+bVfiygt/AKOPo8vywN5e/wf1F7ZpW+FOtllhJ6/hJ
mXbAbnF6rqGR3xri3pQkU9uM6YmawNuihdvlOot6DVFb3dfh3Y0Y0Iv7Tt9wz5Ic
iK7/oyEPegIex9eW4UECQQD4i6IJ2aIQNMAn+ydBbVlbY2DoPsjfTpCwi/t+w7jM
vrT0XXYQUgk0dO0+UOqeYp609ON13X4HyWE5HvHD9fgtAkEAwwp1k96QrBG3u6WM
rht+n0nq45PvNZ3boBUor+cwcealRpHlCD6B1wcPSsOnQpCMOXWFf4ThI2n/9iGM
mrI3vQJAQ4Jh4/0KKQ669uEgG4RhFhKbOtn647TKVjnfeOIeqvZN3mYYcHxn5aiw
3BFMePLemtY9hkFAP0syrjo6fvirjQJBAKM5V5i+RBicY0T4kLkMbXVk6Nw365LV
Xv2jd39uXQ6VVW+vnRq/JO4NDHEnOAzu50sW3PgQ/lEi3oDfJso3p4kCQQChbc5p
DhxZEgFRouBMEOyBu6FfV0mv5UbQEP8xnQToAAQU0a9xjdTV+3YWYRLa9aEzGcPm
SGK6byq+I2QKLFb7
-----END RSA PRIVATE KEY-----");
define('RSA_PUBLIC_KEY', "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC9XIQJNnsK6o6wtaGCrawYxEm5
uNFKsKqraCME4dVeKoNTc9PdjyQx0REbE0hxP465ZgRZ+J2Tl7PIO628A2dZW0Ge
Edi55ubbDPwMQjdj1pDhvWJaEpcVM2E+PJTAdFP8eoBfp9RF+igpqHZLmPQatdvq
uJNWUmUujYeFJjPkOQIDAQAB
-----END PUBLIC KEY-----");

// The default rpc procotol ('xmlrpc' or 'jsonrpc')
define('DEFAULT_PROTOCOL', 'jsonrpc');

// The default client type
define('CLIENT_TYPE', 'WSTESTER');

// Used by the decorator of the result data
define('TAB_WIDTH', 4);

// Debug of the client
define('DEBUG', false);
// Debug of the client
define('RSA', false);
// Allow debug of the client on test page
define('DEBUG_ALLOW', true);
// Some name to identify what is in test
define('NAME', 'jsonrpc/xmlrpc');

// The folder where uploaded files will be stored
define('UPLOAD_DIR', dirname(__FILE__) . '/upload');

// send disabled fields as null in the request (default: false)
define('SEND_DISABLED_FIELDS_AS_NULL', false);

// Stored session parameters:
// The login and logout methods - used for stored sessions [OPTIONAL]
define('LOGIN_METHOD', '');
define('LOGOUT_METHOD', '');
// automatically fill the first parameter field of each method
// with the active session id (default: false) [OPTIONAL]
define('FILL_FIRST_METHOD_PARAMETER_WITH_SESSION', false);

// This method sets the username and password for authorizing the client to a server. With the default (HTTP) transport, this information is used for HTTP Basic authorization.
// leave empty if not needed
define("HTTP_BASIC_AUTH_USER", "");
define("HTTP_BASIC_AUTH_PASS", "");

// types aliases used in methods signatures
$typesCast = array (
    "bool"              => "string",
    "boolean"           => "boolean",
    "dateTime"          => "dateTime",
    "datetime"          => "string",
    "double"            => "double",
    "file"              => "base64",
    "base64"            => "base64",
    "i4"                => "int",
    "int"               => "int",
    "i8"                => "long",
    "long"              => "long",
    "str"               => "string",
    "string"            => "string",
    "time"              => "string",
);

// types descriptions (used for WUI display)
$types = array (
    "bool"              => "Boolean, TRUE || FALSE", //TRUE, FALSE
    "boolean"           => "Boolean", //TRUE, FALSE
    "dateTime"          => "dateTime.iso8601, YYYYMMDDThh:mm:ss",
    "datetime"          => "YYYY-MM-DD HH:mm:ss",
    "double"            => "Double",
    "file"              => "base64-encoded binary",
    "base64"            => "base64-encoded string",
    "i4"                => "Integer",
    "int"               => "Integer",
    "i8"                => "Long Integer (64bits)",
    "long"              => "Long Integer (64bits)",
    "str"               => "String",
    "string"            => "String",
    "time"              => "Unix Timestamp",
    "struct"            => "Structure",
    "array"             => "Array"
);

/* EOF */
