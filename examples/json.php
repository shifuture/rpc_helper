<?php

require_once dirname(__FILE__).'/jsonrpc/jsonRPCServer.php';
require dirname(__FILE__).'/Application.php';

$request = json_decode(file_get_contents('php://input'),true);
preg_match('/(\w+)\.(\w+)/', $request['method'], $matches);
$class = $matches[1];
$method = $matches[2];
$app = new $class;
jsonRPCServer::handle($app, $method) or print 'no request';
