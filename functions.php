<?php
/**
 * Initialiation actions.
 *
 * @return void
 */
function init()
{
    // Load configuration file
    loadConfig();
    
    if (!defined('NAME')) {
        define('NAME', 'Web Service Tester');
    }
    
    if ( !defined('LIB_PATH') ) {
        define( 'LIB_PATH', dirname( __FILE__ ) . '/lib/' );
    }

    if (defined('TIMEZONE')) {
        date_default_timezone_set(TIMEZONE);
    }
    
    // Setup content type
    header('Content-Type: text/html; charset=UTF-8');

    // load Text_Highlighter if possible
    @include_once('Text/Highlighter.php');
    
    // Session setup
    session_name('webservicetesttool');
    session_start();
}

/**
 * Loads configuration file.
 *
 * @return void
 */
function loadConfig()
{
    global $typesCast, $types;

    define('CONFIG_FILE_DIR', dirname(__FILE__));

    $defaultConfigFile = CONFIG_FILE_DIR . '/config.php';

    // Load configuration file
    if (file_exists($defaultConfigFile)) {
        require_once $defaultConfigFile;
    } else {
        die('Config file ' . $defaultConfigFile . ' does not exist!');
    }

    if (!defined('UPLOAD_DIR')) {
        die('UPLOAD_DIR not defined in config file!');
    } else {
        if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
            die('Upload dir ' . UPLOAD_DIR . ' does not exist or is not writable!');
        }
    }
}

/**
 * Transforms a realative path to an canonicalized absolute path.
 * Relative paths are resolved relative to this file directory.
 *
 * @param string $path the path to be transformed
 * @param string $pwd base folder for the relative paths
 * @param boolean $create attempt to create folder if it doesn't exist
 * @return string the absolute path
 */
function getAbsolutePath($path, $pwd, $create = false)
{
    if (DIRECTORY_SEPARATOR !== $path[0]) {
       $path = $pwd . DIRECTORY_SEPARATOR . $path;
    }
    if ($create && !is_dir($path) && !mkdir($path)) {
        throw new Exception('Cannot create folder ' . $path);
    }
    $realpath = realpath($path);
    // if realpath does not exist we will return it this way
    return $realpath ? $realpath : $path;
}

/**
 * Safe html output.
 *
 * @param string $str string to escape
 * @return string escaped string
 */
function esc($str)
{
    return htmlentities($str);
}

/**
 * Output a selectBox with $params and options from $values.
 *
 * @param string $params   Html parameters for the select (name, id, onChange etc)
 * @param array  $values   An array as $key=>$val
 * @param string $selected The key of the selected option
 * @return void Outputs directly the list
 */
function showList($params, $values, $selected = null)
{
    $arr = [];
    $s = '';
    foreach ($values as $k => $v) {
        $v1 = explode('.', $v);
        if ($s != $v1[0]) {
            $arr[] = [$v1[0], false, ''];
            $s = $v1[0];
        }
        unset($v1[0]);
        $arr[] = ['¦− '.implode('.', $v1), true, $v];
    }

    $list = '<select '.$params.'>';
    foreach ($arr as $v) {
        $list .= '<option value="'.esc($v[2]).'" '.($v[2] === $selected ? 'selected' : '').'" '.(! $v[1] ? 'disabled' : '').'>'.esc($v[0]).'</option>';
    }
    $list .= '</select>';
    echo $list;
}

/* Define array_combine for PHP4 */
if (!function_exists('array_combine'))
{
    function array_combine($arr1,$arr2)
    {
        $out = array();
        foreach($arr1 as $key1 => $value1)
        {
            $out[$value1] = $arr2[$key1];
        }
        return $out;
    }
}

/**
 * Returns HTML string with Appkey & AppSecret fields according to params $p
 *
 * @param string  $p   array of parameters
 * @return string HTML string
 */
function displayAppKey(&$p) {
    return '<table>'.
        '<tr><td><b>X-GSAE-UA</b></td><td><input style="width:400px" name="xGsaeUa" type="text" value="'
            .(isset($_SESSION['xGsaeUa']) ? $_SESSION['xGsaeUa'] : '').'"></td></tr>'.
        '<tr><td><b>X-GSAE-LF</b></td><td><input style="width:400px" name="xGsaeLf" type="text" value="'
            .(isset($_SESSION['xGsaeLf']) ? $_SESSION['xGsaeLf'] : '').'"></td></tr>'.
        '<tr><td><b>X-GSAE-AUTH</b></td><td><input style="width:400px" name="customHeader" type="text" value="'
            .(isset($_SESSION['customHeader']) ? $_SESSION['customHeader'] : '').'"></td></tr>'.
        '<tr><td><b>REQ-SN</b></td><td><input style="width:400px" name="reqSn" type="text" value="'.session_id().'-rpcHelper"></td></tr>'.
    '</table>';
}

/**
 * Returns a HTML string with the input fields accoding to array of params $p.
 * This function is called recursively.
 *
 * @param string  $p        array of parameters
 * @param string  $n        children name for substructures
 * @param boolean $multiply show multiplier for current parameter (used for array members)
 * @return string HTML string
 */
function displayParams($p, $n = 'params', $multiply = false, $depth = 0) {
    global $types;
    $str = "";
    $str .= '<table id="mainTable">';
    $paramNr = count($p);
    $str .= '<tr><td>Name</td><td>Value</td><td>Type</td><td>Set</td><td>Null</td></tr>';
    for($i = 0 ; $i < $paramNr; $i++) {
        $val = $p[$i];
        $str .= '<tr valign="top"><td class="label">'.$val['name'];

        // Identifiers
        $toChildren = $n.'['.$i.']';
        $toChildrenValue    = $toChildren . "[value]";
        $toChildrenNull     = $toChildren . "[null]";
        $toChildrenEnabled  = $toChildren . "[enabled]";
        // error messages
        $toChildrenBlank    = $toChildren . "[blank]";
        $toChildrenInt      = $toChildren . "[int]";
        // special parameter for files
        $toChildrenFile     = $toChildren . "[file]";

        // parameter value
        //
        $isDisabled = (boolean)(!$val['enabled']);

        $disabledOptions = ($isDisabled) ? ' class="disabled"' : '';

        // show multiply link
        if ($multiply) {
            $str .= '<br /><a href="index.php?multiply=' . $toChildren . '" onclick="multiplyParameter(\'' . $toChildren .'\', false);return false;">增加</a>';
        }
        // show remove link
        if ($multiply && $i > 0) {
            $str .= '<br /><a href="index.php?remove=' . $toChildren . '" onclick="multiplyParameter(\'' . $toChildren .'\', true);return false;">去除</a>';
        }
        $str .= '</td>'."\n";
        // call recursive for complex types
        if ($val['type'] == 'array' || $val['type'] == 'struct') {
            $str .= '<td><span id="'. $toChildrenValue . '"' . $disabledOptions . '>' . displayParams($val['members'], $toChildren . "[members]", $val['type'] == 'array', 1) . '</span></td>';
        } else {
            $str .= '<td>';
            if ($val['type'] == 'file') {
                //$str .= '<input type="file" name="' . $toChildrenValue . '"  id="' . $toChildrenValue . '" value=""' . $disabledOptions . '/><input type="text" name="' . $toChildrenFile . '" style="display: none;"/>';
                $str .= '<div id="'. $toChildrenValue . '" name="' .  $toChildrenValue . '" ' . $disabledOptions . '><input type="text" id="' .  $toChildrenFile . '" name="' . $toChildrenFile . '" value="' . $val['value']. '"/><input type="button" class="button" value="Upload"/></div>';
            }
            elseif ($val['type'] == 'boolean') {
                $str .= '<select name="' . $toChildrenValue . '" id="' . $toChildrenValue . '"' . $disabledOptions . '">';
                $str .= '<option value=""></option>';
                $str .= '<option value="0"' . ((isset($val['value']) && '0' == $val['value']) ? 'selected="selected"' : '') . '>false</option>';
                $str .= '<option value="1"' . ((isset($val['value']) && '1' == $val['value']) ? 'selected="selected"' : '') . '>true</option>';
                $str .= '</select>';
            } else {
                if (0 == $i && 0 == $depth && count($_SESSION['storedSessions']) && FILL_FIRST_METHOD_PARAMETER_WITH_SESSION) {
                    $paramValue = $_SESSION['session'];
                } else {
                    $paramValue = isset($val['value']) ? htmlentities($val['value']) : "";
                }
                $str .= '<input type="text" id="' . $toChildrenValue . '" name="' . $toChildrenValue . '" value="' . $paramValue . '" ' . $disabledOptions .'/>';
                if ( $val['type'] == 'datetime' ) {
                    $str .= "<script>$('#".str_replace(array('[', ']'), array('\\\\[', '\\\\]'), $toChildrenValue)."').datetimepicker({lang:'zh', format:'Y-m-d H:i:s'});</script>";
                }
            }
            // add error messages labels for integer blank fields
            if($val['type'] == 'int') {
                $str .= '<div class="error" id="' . $toChildrenBlank . '" style="display: none;">Field cannot be left blank.</div>';
                $str .= '<div class="error" id="' . $toChildrenInt . '" style="display: none;">Field should be integer.</div>';
            }
            $str .= '</td>'."\n";
        }
        // parameter type
        //
        $str .= '<td>' . (isset($types[trim($val['type'])]) ? $types[trim($val['type'])] : $val['type']) . ', ' . ($val['optional'] == true ? (empty($val['comment']) ? "<span style=\"color: #008B00\">选填</span>" : "<span style=\"color: #8B0000\">选填</span>") : "<span style=\"color: red\">必填</span>") . '</td>' . "\n";

        // enable/disable checkbox
        //
        $isEnabled = true;
        $isChecked = (boolean)($val['enabled']);
        $isHidden  = false;

        $enabledOptions = ($isEnabled) ? ' onclick="enableField(\'' . $toChildren . '\')"' : ' disabled="disabled"';
        $checkedOptions = ($isChecked) ? ' checked="checked"' : '';
        $hiddenOptions  = ($isHidden) ? ' style="visibility: hidden;"' : '';

        $str .= '<td><input name = "' . $toChildrenEnabled . '" type="checkbox"' . $enabledOptions . $checkedOptions . $hiddenOptions . '/></td>';

        // null checkbox
        //
        $isDisabled = (boolean)($val['optional'] && $val['enabled'] == false);
        $isChecked  = (boolean)(is_null($val['value']) && $val['enabled']);
        $isHidden   = false;

        $disabledOptions = ($isDisabled) ? ' disabled="disabled"' : '';
        $checkedOptions  = ($isChecked) ? ' checked="checked"' : '';
        $hiddenOptions   = ($isHidden) ? ' style="visibility: hidden;"' : '';

        $str .= '<td><input name = "' . $toChildrenNull . '" id = "' . $toChildrenNull . '" type="checkbox" onclick="nullField(\'' . $toChildren . '\')"' .  $disabledOptions . $checkedOptions . $hiddenOptions . '">';

        // null the field if nullbox is checked
        if ($isChecked) {
            $str .= '<script type="text/javascript">nullField(\'' . $toChildren . '\');</script>';
        }
        $str .= '</td>'."\n";

        $str .= '<td style="vertical-align: middle">';
        $comment = isset($val['comment']) ? $val['comment'] : '';
        $str .= "<span style=\"color: #8B0000\">$comment</span>";
        $str .= '</td>'."\n";
        
        // parameter description if any
        $str .= '<td class="description">' . $val['description'] . '</td>';
        
        $str .= '</tr>';
    }
    $str .= '</table>';
    return $str;
}

/**
 * Makes the request and outputs the HTML response.
 * Uses $_SESSION['functionParams'] to get the function parameters.
 *
 * @return string outputs directly the HTML response
 */
function showResult()
{
    if ($_SESSION['DEBUG']) {
        echo "***************************************************************<br/>";
    }
    $params = makeMessageParams($_SESSION['functionParams'], $_POST['protocol']);
    if (!empty($_POST['customPacket'])) {
        $response = sendRequest($_SESSION['selectedFunction'], $params, $_POST['protocol'], $_SESSION['URL'], $_POST['customPacket']);
    } else {
        $response = sendRequest($_SESSION['selectedFunction'], $params, $_POST['protocol'], $_SESSION['URL']);
    }
    echo "***************************************************************";
    
    // save the attachments
    foreach ($response['attachments'] as $filename => $content) {
        file_put_contents(dirname(__FILE__) . '/upload/' . $filename, $content);
    }

    $serverURL = $_SESSION['URL'];
    displayResponse($serverURL, $response);
}

/**
 * Check if a request is AjaxRequest
 *
 * @return boolean
 */
function isAjax()
{
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER ['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
}

/**
 * Return the result from xmlRpc server.
 *
 * @param string $functionName The name of the function to call
 * @param array  $params       Array of parameters to send
 * @param string $protocol     'xmlrpc', 'jsonrpc' or 'soap 1.1'
 * @param string $serverURL    url of the RPC server
 * @param string $payload      Used when we want to send a custom packet
 * @param string $encoding     encoding of the request
 * @return array('response', 'attachments') Response from server
 */
function sendRequest($functionName, $params, $protocol, $serverURL, $payload = '', $encoding = 'utf-8')
{
    // correspondence rpc protocol - language (used for highlighter)
    $language = array(
        'xmlrpc'   => 'xml',
        'jsonrpc'  => 'javascript'
    );

    // correspondence rpc protocol - Content-Type (used for HTTP request)
    $contentType = array(
        'xmlrpc'   => 'text/xml',
        'jsonrpc'  => 'application/json'
    );

    $options = array('version'  => $protocol,
                     'encoding' => $encoding
               );
    $message = ($payload !== '') ? $payload : rpc_encode_request($functionName, $params, $options);

    if ($_SESSION['DEBUG'])
    {
        echo '---SENT---';
        echo '<pre id="request">';
        echo htmlspecialchars($message). "<br/>";
        echo '</pre>';
    }

    // Prepare to send the request using cURL (Client URL Library)

    // will inject the message through a file
    $tmpfile = UPLOAD_DIR . '/' . uniqid('message_');

    // write the message to temp file
    if ($_SESSION['RSA']) {
        $message = rsaEncode($message);
    }
    file_put_contents($tmpfile, $message);

    $fh = fopen($tmpfile, 'r');

    // initialize the curl session
    $ch = curl_init($serverURL);
    // set options
    $header = array(
        'X-Client: ' . CLIENT_TYPE,
        'Content-Type: ' . $contentType[$protocol],
        'Content-Length: ' . strlen($message),
        'Expect:'
    );
    // set customHeader
    if (isset($_REQUEST['customHeader'])) {
        $_SESSION['customHeader'] = $_REQUEST['customHeader'];
        $customHeader = $_REQUEST['customHeader'];
        if ($customHeader != '') {
            $header[] = 'X-GSAE-AUTH: '.$customHeader;
        }
    }
    if (isset($_REQUEST['reqSn'])) {
        $reqSn = $_REQUEST['reqSn'];
        if ($reqSn != '') {
            $header[] = 'REQ-SN: '.$reqSn;
        }
    }
    $_SESSION['xGsaeUa'] = '';
    if (isset($_REQUEST['xGsaeUa'])) {
        $_SESSION['xGsaeUa'] = $_REQUEST['xGsaeUa'];
        $xGsaeUa = $_REQUEST['xGsaeUa'];
        if ($xGsaeUa != '') {
            $header[] = 'X-GSAE-UA: '.$xGsaeUa;
        }
    }
    $_SESSION['xGsaeLf'] = '';
    if (isset($_REQUEST['xGsaeLf'])) {
        $_SESSION['xGsaeLf'] = $_REQUEST['xGsaeLf'];
        $xGsaeLf = $_REQUEST['xGsaeLf'];
        if ($xGsaeLf != '') {
            $header[] = 'X-GSAE-LF: '.$xGsaeLf;
        }
    }
    $options = array(
        CURLOPT_HTTPHEADER => $header,            // set custom headers
        CURLOPT_POST       => true,               // do a POST request
        CURLOPT_INFILE     => $fh,                // read the message from file handler
        CURLOPT_INFILESIZE => strlen($message),   // lenght of the message
        CURLOPT_USERAGENT  => $_SERVER['HTTP_USER_AGENT'], // set User-Agent to the server user agent
        CURLOPT_HEADER     => true,               // get the headers also in the response
        CURLOPT_RETURNTRANSFER => true            // return the response
    );

    // set session cookie
    if (isset($_SESSION['session']) && $functionName !== LOGIN_METHOD) {
        $options[CURLOPT_COOKIE] = sprintf('%s=%s', $_SESSION['storedSessions'][$_SESSION['session']]['sessionName'], $_SESSION['session']);
    }
    // set http basic auth
    if (defined("HTTP_BASIC_AUTH_USER") && defined("HTTP_BASIC_AUTH_PASS")
        && HTTP_BASIC_AUTH_USER != "" && HTTP_BASIC_AUTH_PASS != "") {
        $userpwd = HTTP_BASIC_AUTH_USER . ':' .  HTTP_BASIC_AUTH_PASS;
        $options[CURLOPT_USERPWD] = $userpwd;
    }
    
    curl_setopt_array($ch, $options);

    // get the response
    $response = curl_exec($ch);

    // close curl session
    curl_close($ch);

    // close the file handler and remove the file
    fclose($fh);
    unlink($tmpfile);

    $data = $response;

    if (!$data || is_null($data)) {
        $exception = new Exception('There is no response from server ' . $serverURL, 1);
        throw $exception;
    }
    
    // separate the headers and the data from the response
    $headers = null;
    if ($n = strpos($response, "\r\n\r\n")) { // CRLF CRLF
        $headers = substr($response, 0, $n);
        $data = substr($response, $n + 4);
    }
    if ( $_SESSION['RSA']) {
        $data = rsaDecode($data);
    }

    // get content type
    preg_match('/Content-Type:.*/', $headers, $matches);
    $contentType = isset($matches[0])?$matches[0]:"";

    if ($_SESSION['DEBUG']) {
        echo '---GOT---' . "\n";

        echo '<pre id="headers">' . $headers . '</pre>';
        echo '<pre id="response">' . htmlspecialchars($data). '</pre><br/>';
        echo '---END---<br/>' . "\n";
        echo '</pre>';
    }

    //forward X HTTP headers
    $headersArray = explode("\n", $headers);
    foreach ($headersArray as $header) {
        if(substr($header, 0, 2) === 'X-') {
            // header($header);
        }
    }

    // Session handling
    // if it was a login action store the new session
    if (defined('LOGIN_METHOD') && $functionName === LOGIN_METHOD) {
        if (preg_match('/Set-Cookie: (\w*)=(.*?);/m', $headers, $matches)) {
            $sessionId = $matches[2];
            $sessionData = array(
                'sessionName' => $matches[1],
                'server'      => $_SESSION['server'],
                'params'      => rpc_encode($params[0], 'jsonrpc')
            );
            $_SESSION['storedSessions'][$sessionId] = $sessionData;
            $_SESSION['session'] = $sessionId;
        }
    }
    // if it was a logout action destroy the session
    if (defined('LOGOUT_METHOD') && $functionName === LOGOUT_METHOD) {
        $sessionId = $_SESSION['session'];
        unset($_SESSION['storedSessions'][$sessionId]);
        if (count($_SESSION['storedSessions'])) {
            $_SESSION['session'] = key($_SESSION['storedSessions']);
        } else {
            unset($_SESSION['session']);
        }
    }

    $attachments = array();
    
    // if content type is multipart/mixed
    if (0 === strpos($contentType, 'Content-Type: multipart/mixed')) {
        
        // get the boundary - try with and without quoted value
        if (!preg_match('/boundary="(.*)"/', $contentType, $matches)) {
            preg_match('/boundary=(.*)$/', $contentType, $matches);
        }
        $boundary = $matches[1];

        // split message body into parts
        $message = Zend_Mime_Message::createFromMessage($data, $boundary);

        // iterate over message parts, and map name to content
        foreach($message->getParts() as $part) {
            
            if (!isset($methodResponse)) {
                // the first one is the method response
                $methodResponse = $part->getContent();
            } else {
                // get the name
                $headers = $part->getHeaders();
                if (0 !== preg_match('/name="(.*)"/', $headers, $matches) || 0 !== preg_match('/name=(.*)$/', $headers, $matches)) {
                    $name = $matches[1];
                }
                
                $attachments[$name] = $part->getContent();
            }
        }
    } else {
        $methodResponse = $data;
    }

    // decode response
    $decodedResponse = rpc_decode(trim($methodResponse), $protocol);
    if ($protocol == 'jsonrpc' && is_array($decodedResponse) && isset($decodedResponse['result'])) {
        $decodedResponse = $decodedResponse['result'];
    }

    return array('response' => $decodedResponse, 'attachments' => $attachments);

    /*
    // SSL REQUESTS
    if (strtolower($url['scheme']) == "https" || strtolower($url['scheme']) == "ssl")
    {
        if (defined("SSL_CERTIFICATE") && SSL_CERTIFICATE != "") {
            $client->setCertificate(SSL_CERTIFICATE, SSL_PASSPHRASE);
        } else {
            $client->setSSLVerifyPeer(false);
            $client->setSSLVerifyHost(false);
        }
    }
    */
}

/**
 * Create the formated params for the rpc message.
 *
 * @param string $p Array of parameters to send
 * @param string $protocol rpc protocol
 * @return array the final array of formatted parameters ready to be encoded into rpc
 */
function makeMessageParams($p, $protocol)
{
    // creates the processed array of params
    $parsedParams = formatArray($p);

    // create final function parameters
    $params = array();
    foreach($parsedParams as $paramProperties) {
        if ($protocol == 'soap 1.1') {
            // for SOAP we have named parameters
            $params[$paramProperties['name']] = parseInternalParams($paramProperties['value'], $paramProperties['type']);
        } else {
            // for XML-RPC and JSON-RPC we have positional params
            $params[] = parseInternalParams($paramProperties['value'], $paramProperties['type'], $protocol);
        }
    }

    return $params;
}

/**
 * Create the final php value for parameter.
 * Parameter values are casted to the associated data type.
 *
 * @param mixed  $p        parameter
 * @param string $type     rpc type
 * @return mixed a final php variable representating the parameters ready to be encoded into rpc
 */
function parseInternalParams($p, $type)
{
    $return = null;

    // if value is null return
    if (is_null($p)) {
        return $return;
    }

    switch ($type) {
        case 'array':
            $params = array();
            foreach($p as $paramProperties) {
                $params[] = parseInternalParams($paramProperties['value'], $paramProperties['type']);
            }
            $return = $params;
            break;
        case 'struct':
            $params = array();
            foreach($p as $paramProperties) {
                $params[$paramProperties['name']] = parseInternalParams($paramProperties['value'], $paramProperties['type']);
            }
            if(empty($params)) {
                $params = new stdClass();
            }
            $return = $params;
            break;
        case 'int':
            $return = (int)$p;
            break;
        case 'string':
            $return = (string)$p;
            break;
        case 'boolean':
            $return = (boolean)$p;
            break;
        case 'base64':
            rpc_set_type($p, 'base64');
            $return = $p;
            break;
        case 'dateTime':
            rpc_set_type($p, 'datetime');
            $return = $p;
            break;
        case 'long':
            rpc_set_type($p, 'long');
            $return = $p;
            break;
        default:
            $return = $p;
            break;
    }

    return $return;
}

/**
 * Create a processed array of params
 *
 * @param  array $arr Array of parameters
 * @return array processed array of params
 */
function formatArray($arr)
{
    global $typesCast;
    $newArray = array();

    foreach ($arr as $key => $element) {
        // element type is a complex type (struct or array)
        if (($element['type'] == 'array' || $element['type'] == 'struct')) {
            if ($element['enabled']) {
                $newArray[$key] = array(
                    'name' => $element['name'],
                    'type' => $element['type'],
                    'value' => is_null($element['value']) ? null : formatArray($element['members']),
                );
            } elseif (SEND_DISABLED_FIELDS_AS_NULL) {
                $newArray[$key] = array(
                    'name' => $element['name'],
                    'type' => $typesCast[$element['type']],
                    'value' => null,
                );
            }
        // element type is a simple type
        } else {
            if($element['enabled']) {
                // for file parameters put file contents into element's value
                if($element['type'] == 'file') {
                    $element['value'] = file_get_contents(UPLOAD_DIR . '/' . $element['value']);
                }
                $newArray[$key] = array(
                    'name' => $element['name'],
                    'type' => $typesCast[$element['type']],
                    'value' => $element['value'],
                );
            } elseif (SEND_DISABLED_FIELDS_AS_NULL) {
                $newArray[$key] = array(
                    'name' => $element['name'],
                    'type' => $typesCast[$element['type']],
                    'value' => null,
                );
            }
        }
    }

    return $newArray;
}

/**
 * Multiply or remove a member of an array in params array.
 *
 * @param string  $member member to multiply (ex: params[1][members][0])
 * @param array   $params
 * @param boolean $remove if set to true it will remove the parameter from array members
 * @return array|boolean updated params array or false if there was an error
 */
function multiplyParameter($member, $params, $remove = false)
{
    if (preg_match('/(.*)\[(\d*)\]$/', $member, $matches)) {
        $members = str_replace('members', '"members"', $matches[1]);
        $key = $matches[2];

        $code = '$membersArray = $' . $members .';';
        eval($code);
        if (!$remove) {
            // replace $membersArray[$key] with $membersArray[$key] x 2
            array_splice($membersArray, $key, 1, array($membersArray[$key], $membersArray[$key]));
        } else {
            // remove $membersArray[$key]
            array_splice($membersArray, $key, 1);
        }

        // replace the members with the new membersArray
        $code = '$' . $members . ' = $membersArray;';
        eval($code);

        return $params;
    } else {
        return false;
    }
}

/**
 * Updates a file parameter.
 *
 * @param array $p        parameters array
 * @param array $name     array with filename ($_FILES['file']['name'])
 * @param array $tmp_name array with temporary filename ($_FILES['file']['tmp_name'])
 * @return array (string filename, string tmpfilename)
 */
function updateFile(&$p, $name, $tmp_name)
{
    $fileValue = &$p;
    while(is_array($name)) {
        $key = key($name);
        $name = $name[$key];
        $tmp_name = $tmp_name[$key];
        $fileValue = &$fileValue[$key];
    }
    $fileValue = $name;
    return array($name, $tmp_name);
}

/**
 * Update array of params with $vals.
 *
 * @param array $p    parameters array
 * @param array $vals new values
 * @return array new array of params
 */
function updateVals($p, $vals)
{
    foreach($vals as $key => $val)
    {
        // to skip 'enabled' and 'null' fields recursive structured type
        if (is_array($val)) {
            // set enabled field
            // is always enabled for non-optional values
            $p[$key]['enabled'] = isset($val['enabled']) ? true : false;

            // structured type - call update recursively for members
            if (isset($val['members']))
            {
                $p[$key]['value'] = isset($val['null']) ? null : '';
                $p[$key]['members'] = updateVals($p[$key]['members'], $val['members']);
            }
            else
            {
                if (isset($val['null'])) {
                    $p[$key]['value'] = null;
                } elseif (isset($val['value']) && $p[$key]['type'] != 'file') {
                    // Exception: values for 'file' parameters are set on file upload
                    $p[$key]['value'] = $val['value'];
                }
            }
        }
    }
    return $p;
}

/**
 * Pad a string on the left to certain lenght with spaces.
 *
 * @param string  $input  input string
 * @param integer $lenght the padding length
 * @return string padded string
 */
function pad($input, $lenght)
{
    return str_pad($input, $lenght + strlen($input), ' ', STR_PAD_LEFT);
}

/**
 * Returns a parsable string representation of an introspection array
 *
 * @param mixed   $var       variable
 * @param integer $pad       the number of spaces to pad the result
 * @param integer $highlight return a highlighted result
 * @return string the string represention of the introspection array
 */
function decorator($var, $pad = 0)
{
    switch(true) {
        case is_array($var):
            $return = "array(\n";

            $end = count($var);
            $count = 0;

            // if it's a numeric indexed array
            if (isset($var[0])) {
                foreach($var as $value) {
                    $suffix = (++$count == $end) ? "" : ","; // the last comma
                    $return .= pad(decorator($value, $pad + TAB_WIDTH) . "$suffix\n", $pad + TAB_WIDTH);
                }
                $return .= pad(")", $pad);
            // if it's a hash
            } else {
                foreach($var as $key => $value) {
                    // transform $value to a decorated string:
                    switch(true)
                    {
                        // null
                        case is_null($value):
                            $processedValue = 'null';
                            break;
                        // array
                        case is_array($value):
                            $processedValue = decorator($value, $pad + TAB_WIDTH);
                            break;
                        // numeric
                        case (is_bool($value) || is_int($value) || is_double($value)):
                            $processedValue = $value;
                            break;
                        // string
                        case is_string($value):
                            $processedValue = "'" . $value . "'";
                        break;
                    }
                    $suffix = (++$count == $end) ? "" : ","; // the last comma
                    $return .= pad(sprintf("'%s' => %s\n", $key, $processedValue . $suffix), $pad + TAB_WIDTH);
                }
                $return .= pad(")", $pad);
            }
            break;
            // null
            case is_null($var):
                $return = 'null';
                break;
            // array
            case is_array($var):
                $return = decorator($var, $pad + TAB_WIDTH);
                break;
            // numeric
            case (is_bool($var) || is_int($var) || is_double($var)):
                $return = $var;
                break;
            // string
            case is_string($var):
                $return = "'$var'";
            break;
    }

    return $return;
}

/**
 * Highlights php code and strips the begin and closing tags
 *
 * @param string $code code to highlight
 * @return string the highlighted code
 */
function highlight($code)
{
    // prepare data for highlight
    $data = "<?php " . $code . " ?>";

    $data = highlight_string($data, true);
    // eliminate php begin and closing tags
    $data = str_replace('&lt;?php&nbsp;', '', $data);
    $data = str_replace('?&gt;', '', $data);

    $data = str_replace("\n", '', $data);
    return $data;
}

/**
 * Outputs or returns decorated html of a parsable string representation of a variable
 *
 * @param mixed   $var    variable
 * @param boolean $return if true will return the variable representation instead of outputing it
 * @return string highlighted html code
 */
function var_decorate($var, $return = false)
{
    $decorated = highlight(decorator($var));
    if (!$return) {
        echo $decorated;
    }
    return $return ? $decorated : null;
}

/**
 * Outputs the response
 *
 * @param string $url      RPC server url
 * @param string $response Response from server
 * @return void Outputs directly
 */
function displayResponse($url, $response)
{
        ?>
        <br/><label style="color:blue;">Response from server ("<?php print $url ?>"):</label><br/>
            <div id="parsedResponse" style="margin-bottom: 10px;"><pre><?php if(is_null($response['response'])) { echo '<label style="color: red;">There is a problem with the response: enable debugging to examine incoming payload.</label>'; } else {
                if ($fault = getFault($response['response'])) {
                    echo '<label><span style="color: blue;">' . $fault['faultCode'] . '</span>&nbsp;&nbsp;<span style="color: red;">' . $fault['faultString'] . '</span></label>';
                } else {
                    // decorate and highlight the response
                    var_decorate($response['response']);
                }
            } ?></pre></div>
        <?php
        if (!empty($response['attachments'])) {
            echo '<span id="attachments">Attachments:</span> ';
            $attachmentBaseUrl = 'upload/';
            foreach (array_keys($response['attachments']) as $filename) {
                
                echo '<a href="'. $attachmentBaseUrl . $filename . '">' . $filename . '</a> &nbsp; ';
            }
        }
}

/**
 * Tests whether a variable is a RPC fault structure
 *
 * @param mixed $var a variable
 * @return mixed array with 'faultCode' and 'faultString' set if the variable is a fault structure, false otherwise
 */
function getFault($var)
{
    if (isXmlRpcFault($var) || isJsonRpcError($var) || isSoapFault($var)) {
        $fault = array();
        if (isXmlRpcFault($var)) {
            $fault['faultCode']   = $var['faultCode'];
            $fault['faultString'] = $var['faultString'];
        }
        else if (isJsonRpcError($var)) {
            $fault['faultCode']   = $var['code'];
            $fault['faultString'] = $var['message'];
        }
        else if (isSoapFault($var)) {
            $fault['faultCode']   = $var['faultcode'];
            $fault['faultString'] = $var['faultstring'];
        }
        return $fault;
    }
    return false;
}

/**
 * Tests whether a variable is a XML-RPC fault structure
 *
 * @param mixed $var a variable
 * @return boolean true if the variable is a fault structure
 */
function isXmlRpcFault($var)
{
    return (is_array($var)
            && count($var) == 2
            && isSet($var['faultCode'])
            && isSet($var['faultString']));
}

/**
 * Tests whether a variable is a JSON-RPC error structure
 *
 * @param mixed $var a variable
 * @return boolean true if the variable is an error structure
 */
function isJsonRpcError($var)
{
    return (is_array($var)
            && count($var) == 3
            && ( isset( $var['name'] ) && $var['name'] == 'JSONRPCError' )
            && isSet($var['code'])
            && isSet($var['message']));
}

/**
 * Tests whether a variable is a SOAP fault structure
 *
 * @param mixed $var a variable
 * @return boolean true if the variable is a fault structure
 */
function isSoapFault($var)
{
    return (is_array($var)
            && count($var) == 2
            && isSet($var['faultcode'])
            && isSet($var['faultstring']));
}

/*
   The following six functions are used
   in parsing method signatures file
   and creating parameters structure
*/

/**
 * Get an array of function names from a string (methods.signatures.txt)
 *
 * @param string $str string to search
 * @return array array of function names
 */
function getFunctionNames($str)
{
    preg_match_all('/([a-zA-Z0-9_\.]*)\s*\[/', $str, $matches);
    sort($matches[1]);
    return $matches[1];
}

/**
 * Clear all comments from a string.
 * Comments start with #. It causes all remaining characters on that line to be ignored.
 *
 * @param string $str
 * @return string original string without comments
 */
function clear_comments($str)
{
    return preg_replace('/#.*/', '', $str);
}

/**
 * Clear white spaces.
 * Preserves spaces in substrings between simple quotes ('str');
 *
 * @param string $str
 * @return string|boolean original string without white spaces, false if string has not matching quotes
 */
function clearWhiteSpaces($str)
{
    $separator = "'";
    
    // split on parameter description
    $parts = explode($separator, $str);

    // parts must be odd
    if (count($parts) % 2) {
        for($i=0; $i<count($parts); $i+=2) {
            $parts[$i] = preg_replace('/\s*/', '', $parts[$i]);
        }
        return implode($separator, $parts);
    } else {
        return false;
    }
}

/**
 * Extract a method signature from a methods signature string.
 *
 * @param string $contents string with methods signatures
 * @param string $function function name
 * @return string|boolean function signature or false if there is no match
 */
function getMethodSignature($contents, $function)
{
    $regexp = '/\b' . $function . '\s*\[([^\]]*)\];/s';
    if (preg_match($regexp, $contents, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * Parse a function from methods signatures file
 *
 * @param string $contents file content
 * @param string $fn       The function name we want to retrive
 * @return array|boolean function parameters array or false if there was a parsing error
 */
function parseFunction($contents, $fn)
{
    $contents = clear_comments($contents);

    $signature = getMethodSignature($contents, $fn);

    if (false !== $signature) {

        $signature = clearWhiteSpaces($signature);

        if (false === $signature) {
            echo 'Signature syntax error: Unmatched quotes in method signature for ' . $fn;
            die();
        }

        list($result, $i) = parseParameters($signature, 0);

        return $result;
    }
    return false;
}

/**
 * Parse function parameters and create parameters array.
 * Uses a linear parsiong algorithm - it simulates a finite state machine.
 *
 * @param &string $signature function signature string
 * @param int     $p         string pointer
 * @return array($parameters, $new_pointer) function params or false if there is an error in parsing
 */
function parseParameters(&$signature, $p)
{
    $parameters = array();
    $i = $p;
    if (strlen($signature)) {
        do
        {
            $continue = false;
            // get parameter name
            // allowed characters for parameter name: [A-Za-z_])
            preg_match('/^(\w+):/', substr($signature, $i), $matches);
            if ($matches[1]) {
                $parameterName = $matches[1];
                if ($parameterName == '') return array(false);
                // move pointer
                $i += strlen($parameterName) + 1;
                // create parameter struct
                $parameter = array(
                                    'name'        => $parameterName,
                                    'optional'    => false,
                                    'enabled'     => true,
                                    'value'       => '',
                                    'type'        => null,
                                    'description' => null,
                                    'members'     => null // set when type is 'array' or 'struct'
                            );
                // get parameter type
                $stype = $signature[$i];
                switch($stype) {
                    case '(': // struct
                    case '{': // array
                            $i++;
                            list($members, $i) = parseParameters($signature, $i);
                            if (false !== $members) {
                                $parameter['type'] = ($stype == '(') ? 'struct' : 'array';
                                $parameter['members'] = $members;
                            } else {
                                return array(false);
                            }
                            break;
                    default: // scalar types
                            // get the type name (allowed characters for type names: [A-Za-z_])
                            preg_match('/^\w+/', substr($signature, $i), $matches);
                            if ($matches[0]) {
                                $parameter['type'] = $matches[0];
                                // move pointer
                                $i += strlen($matches[0]);
                            } else {
                                return array(false);
                            }
                            break;
                }
                // check if we have an optional tag set
                if ($i < strlen($signature) && $signature[$i] == ':') {
                    $i++;
                    if (preg_match('/^\w+/', substr($signature, $i), $matches)) {
                        if ('true' == $matches[0] || 'false' == $matches[0]) {
                            $parameter['optional'] = (boolean)('true' == $matches[0]);
                            $i += strlen($matches[0]);
                        } else {
                            return array(false);
                        }
                    }
                }
                // set enabled state
                $parameter['enabled'] = (boolean)(!$parameter['optional']);

                // check if we have a parameter description
                if ($i < strlen($signature) && $signature[$i] == ':') {
                    $i++;
                    $description = '';
                    if ("'" == $signature[$i]) {
                        while($i++ && $signature[$i] != "'") {
                            $description .= $signature[$i];
                        }
                        $i++;
                        $parameter['description'] = $description;
                    } else {
                        return array(false);
                    }
                }
                // check if we have ',' as the end parameter token
                $endtag = $i < strlen($signature) ? $signature[$i] : false;

                switch($endtag)
                {
                    case ',':   // end of parameter
                            $continue = true;
                            break;
                    /* DEPRECATED: to stay compatible with older versions of this script
                       we allow defining arrays with * end token
                    */
                    case '*': $name = $parameter['name'];
                              $parameter['name'] = 'item';
                              $arrayParameter = array(
                                      'name'     => $name,
                                      'optional' => $parameter['optional'],
                                      'enabled'  => $parameter['enabled'],
                                      'value'    => '',
                                      'type'     => 'array',
                                      'members'  => array($parameter)
                              );
                              $parameter = $arrayParameter;
                              break;
                    case ')':   // end of struct
                    case '}':   // end of array
                    case false: // end of signature
                            break;
                    default:    // parse error
                            return array(false);
                }
                $i++;
                $parameters[] = $parameter;
            } else {
                return array(false);
            }
        } while($continue);
    }

    return array($parameters, $i);
}

/**
 * Outputs the main input form
 *
 * @return void Outputs HTML code
 */
function showInputForm()
{

?>
<span class="intense">函数对应参数 <em><?php echo $_SESSION['selectedFunction']; ?></em></span>
                    <div><form name="testFunction" id="testFunction" action="index.php" method="post" enctype="multipart/form-data">
                    <?php echo displayParams($_SESSION['functionParams']);?>
                    <?php echo displayAppKey($_SESSION['appParams']);?>
                    <div <?php echo (empty($_POST['customPacket']))?'style="display:none;"':''; ?> id="customPacket">
                        <textarea name="customPacket" id="customPacketText" rows="10" cols="10"><?php echo (!empty($_POST['customPacket']))?$_POST['customPacket']:''; ?></textarea>
                    </div>
                    <span style="font-size: 0.6em;"><br/></span>
                    <span style="font-weight: bold;">协议类型:</span>
                    <select name="protocol" id="protocolSelect" onchange="chooseProtocol()">
                        <option <?php echo ($_SESSION['protocol'] == 'xmlrpc') ? 'selected="selected"' : '' ?> value="xmlrpc">XML-RPC</option>
                        <option <?php echo ($_SESSION['protocol'] == 'jsonrpc') ? 'selected="selected"' : '' ?> value="jsonrpc">JSON-RPC</option>
                    </select> <br />
                    <input type="checkbox" name="RSA" value="1" id="RSA"<?php echo ($_SESSION['RSA'])?" checked=\"checked\"":"";?>/> <label for="RSA">RSA</label>
                    <?php if (DEBUG_ALLOW == 1) { ?>
                    <input type="checkbox" name="DEBUG" value="1" id="DEBUG"<?php echo ($_SESSION['DEBUG'])?" checked=\"checked\"":"";?>/> <label for="DEBUG">Debug</label>
                    <?php }// end if ?>
                    <a href="javascript:;" onclick="toggleDiv();">自定义包</a><br/>
                    <input class="button" type="button" onclick="getResponse();" value="测试" style="margin-top: 8px;" />
                    </form></div>
<?php
}

/**
 * Given helper info.
 * Help valid data info
 */
function createHelper() {
    echo "<input class=\"button\" type=\"button\" onclick=\"updateHelper();\" value=\"更新提示\" style=\"margin-top: 8px;\" /><br/>";
    echo "提示: unix_timestamp: " . time() . "000<br/>";
    echo "提示: datetime: " . date("Y-m-d H:i:s") . "<br/>";
    echo "提示: dateTime.iso8601: " . date("Ymd\TH:i:s") . "<br/>";
}

/**
 * Outputs the functions
 *
 * @return void Outputs HTML code
 */
function showFunctionsForm()
{
?>
                <form method="get" action="index.php">
                Select function: <?php showList('id="functionsSelect" name="f" onchange="chooseFunction();"', array_combine(array_values($_SESSION['functions']), $_SESSION['functions']), $_SESSION['selectedFunction']); ?>
                <input type="submit" value="OK" onclick="chooseFunction(); return false;" />
                </form>
<?php
}

/**
 * Outputs the sessions
 *
 * @return void Outputs HTML code
 */
function showSessionsForm()
{
?><?php if (count($_SESSION['storedSessions'])) { ?>
            <div id="sessionsForm">
            <form method="get" action="index.php">
            <span>Select session:</span>
            <select name="session" id="sessionSelect" onchange="chooseSession();">
                <?php foreach($_SESSION['storedSessions'] as $sessionId => $sessionData) { ?>
                    <option value="<?php echo $sessionId ?>" <?php if($sessionId == $_SESSION['session']) echo 'selected="selected"'?> style="font-family: monospace;"><?php echo $sessionData['server'] . ' ' . $sessionData['sessionName'] . '=' . $sessionId . ' ' . $sessionData['params'] ?></option>
                <?php } ?>
            </select>
            <input class="button" type="submit" value="OK" onclick="" />
            </form>
            </div>
            <?php } ?>
<?php
}

/**
 * Outputs the links
 *
 * @return void Outputs HTML code
 */
function showLinks()
{
global $prevFunction, $nextFunction;
?>
<a href="index.php?f=<?php echo esc($prevFunction); ?>" onclick="choseFunction('<?php echo $prevFunction; ?>');return false;">Previous</a> <a href="index.php?f=<?php echo esc($nextFunction); ?>" onclick="choseFunction('<?php echo $nextFunction; ?>');return false;">Next</a>
<?php
}

/**
 * Sort array of parameters by putting the mandatory parameters first
 *
 * @param array $ParamsArray
 * @return array of params
 */
function sortParamsMandatoryFirst($ParamsArray)
{
    if (!is_array($ParamsArray)) {
        return $ParamsArray;
    }


    $MandatoryParams = array();
    $OptionalParams = array();

    foreach ($ParamsArray as $Key => $Param) {
        if (isset($Param['optional']) && $Param['optional'] === 'true') {
            $OptionalParams[$Key] = $Param;
        }
        else {
            $MandatoryParams[$Key] = $Param;
        }
    }

    $ParamsArray = array_merge($MandatoryParams, $OptionalParams);
    $ParamsArray = array_slice($ParamsArray, 0);

    return $ParamsArray;

}

/**
 * Logs a message to a log file.
 *
 * @param string $message message to log
 * @param string $file log file
 */
function log_message($message, $file = null)
{
    if (is_null($file)) {
        $file = LOG_FILE;
    }
    file_put_contents($file, $message . "\n", FILE_APPEND);
}

/**
 * Cleans up uploaded files.
 */
function cleanUploadDir()
{
    shell_exec('rm ' . UPLOAD_DIR . "/*");
}

/**
 * Wrap rpc request
 */
function rpc_encode_request($functionName, $params, $options)
{
    switch($options['version']) {
    case 'jsonrpc':
        return jsonrpc_request($functionName, $params);
    case 'xmlrpc':
        return xmlrpc_request($functionName, $params);
    }
}

/**
 * jsonrpc request
 */
function jsonrpc_request($functionName, $params)
{
    global $servers;
    $_SESSION['id'] = isset($_SESSION['id'])?intval($_SESSION['id'])+1:1; 
    $request = array(
        'id' => $_SESSION['id'],
        'jsonrpc'=> "2.0",
        'method' => $functionName,
        'params' => $params,
    );
    $request = json_encode($request);
    return $request;
}

/**
 * jsonrpc request
 */
function xmlrpc_request($functionName, $params)
{
    return xmlrpc_encode_request($functionName, $params);
}

/**
 * rpc response decode
 */
function rpc_decode($methodResponse, $protocol)
{
    switch($protocol) {
    case 'jsonrpc':
        return jsonrpcDecode($methodResponse);
    case 'xmlrpc':
        return xmlrpcDecode($methodResponse);
    }
}

/**
 * jsonrpc response decode
 */
function jsonrpcDecode($methodResponse)
{
    return json_decode($methodResponse, true);
}

/**
 * xmlrpc response decode
 */
function xmlrpcDecode($methodResponse)
{
    return xmlrpc_decode($methodResponse, 'utf-8');
}

/**
 * rsa encrypt
 */
function rsaEncode($str) {
    $b64Str = base64_encode($str);
    $pos = 0;
    $dstStr = '';
    while( $pos < strlen($b64Str)) {
        $tmpStr = '';
        if ( $pos + 117 > strlen($b64Str) ) {
            $tmpStr = substr($b64Str, $pos, strlen($b64Str)-$pos);
            $pos = strlen($b64Str);
        } else {
            $tmpStr = substr($b64Str, $pos, 117);
            $pos += 117;
        }
        openssl_public_encrypt($tmpStr, $encStr,
            openssl_get_publickey("-----BEGIN PUBLIC KEY-----".PHP_EOL.chunk_split($_SESSION['pubKey'])."-----END PUBLIC KEY-----"));
        $dstStr .= base64_encode($encStr);
    }
    return $dstStr;
}

/**
 * rsa decrypt
 */
function rsaDecode($str) {
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);
    $pos = 0;
    $dstStr = '';
    while( $pos < strlen($str)) {
        $tmpStr = '';
        if ( $pos + 172 > strlen($str) ) {
            $tmpStr = substr($str, $pos, strlen($str)-$pos);
            $pos = strlen($str);
        } else {
            $tmpStr = substr($str, $pos, 172);
            $pos += 172;
        }
        openssl_private_decrypt(base64_decode($tmpStr), $decStr,
            openssl_get_privatekey("-----BEGIN PRIVATE KEY-----".PHP_EOL.chunk_split($_SESSION['priKey'])."-----END PRIVATE KEY-----"));
        $dstStr .= $decStr;
    }
    $dstStr = base64_decode($dstStr);
    return $dstStr;
}

/**
 * 从远程配置地址获取
 *
 * @param string $configUrl 远程配置地址
 * @return array
 */
function loadRemoteConfig($configUrl) {
    // 获取数据
    $res = @file_get_contents($configUrl);
    $res === false && die('Cannot read config info from: ' . $configUrl);
    $res = @json_decode($res, true);
    $res === false && die('Cannot parse config from: ' . $configUrl);

    // 解析数据
    $servers = $res['server'];
    if (count($servers) <= 0) {
        die('远程配置地址中 server数量为0');
    }
    return $servers;
}
