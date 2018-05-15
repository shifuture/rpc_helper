<?php
/**
 * Config file for WebService tester
 */

// The default timezone used by all date/time functions
// Example: Europe/Paris
define('TIMEZONE', 'Asia/Shanghai');

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
