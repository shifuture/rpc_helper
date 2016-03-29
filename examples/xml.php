<?php
require dirname(__FILE__).'/Application.php';

/**
function return_time($method, $args) {
       return date('Ymd\THis');
}
  
$server = xmlrpc_server_create( ) or die("Can't create server");
xmlrpc_server_register_method($server, 'return_time', 'get_time') 
        or die("Can't register method.");
  
$request = $GLOBALS['HTTP_RAW_POST_DATA'];
$options = array('output_type' => 'xml', 'version' => 'xmlrpc');

print xmlrpc_server_call_method($server, $request, NULL, $options)
        or die("Can't call method");
  
xmlrpc_server_destroy($server);
 */

$method = null;
$request = $GLOBALS['HTTP_RAW_POST_DATA'];
$params = xmlrpc_decode_request($request, $method, 'utf8');
preg_match('/(\w+)\.(\w+)/', $method, $matches);
$class = $matches[1];
$method = $matches[2];
$app = new $class;
$res = call_user_func_array(array($app, $method), $params);
print xmlrpc_encode($res);
