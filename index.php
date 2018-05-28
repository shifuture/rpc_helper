<?php
ini_set('display_errors','On');

error_reporting(E_ALL);

require_once 'functions.php';

init();

// If get helper is requested
if (isset($_REQUEST['HELPER']))
{
    createHelper();
    die();
}

// If reset tester is requested we destroy the session and clean up the upload dir
if (isset($_REQUEST['RESET']))
{
    cleanUploadDir();
    session_destroy();
    session_start();
    $_POST['RSA'] = 1;
}

// If reload functions is requested we unset $_SESSION functions variables
if (isset($_REQUEST['RELOAD_FUNCTIONS'])) {
    unset($_SESSION['functions']);
    unset($_SESSION['selectedFunction']);
    unset($_SESSION['functionParams']);
    unset($_SESSION['appParams']);
    unset($_SESSION['configArr']);
}

// 从远程地址读取配置
if (isset($_GET['config']) && $_GET['config'] != '') {
    $configUrl = $_GET['config'];
    if (isset($_SESSION['configUrl']) && $_SESSION['configUrl'] != $configUrl) {
        unset($_SESSION['configArr']);
    }
} elseif (isset($_SESSION['configUrl'])) {
    $configUrl = $_SESSION['configUrl'];
} else {
    $configUrl = 'http://config.gsae-tech.com/rpc-helper-dev.json';
}
$_SESSION['configUrl'] = $configUrl;
if (isset($_SESSION['configArr'])) {
    $configArr = $_SESSION['configArr'];
} else {
    $configArr = loadRemoteConfig($configUrl);
    array_multisort(array_column($configArr, 'name'), SORT_ASC, $configArr);
    foreach ($configArr as $k => $v) {
        $a = $v['functions'];
        sort($a);
        $configArr[$k]['functions'] = $a;
    }
    $_SESSION['configArr'] = $configArr;
}

// Set server URL
$selectedServer = 0;
if (isset($_SESSION['selectedServer']) && isset($configArr[$_SESSION['selectedServer']])) {
    $selectedServer = $_SESSION['selectedServer'];
}
if (isset($_GET['server']) && isset($configArr[$_GET['server']])) {
    $selectedServer = $_GET['server'];
}
$_SESSION['selectedServer'] = $selectedServer;
$server = $configArr[$_SESSION['selectedServer']];

// pub key
$_SESSION['pubKey'] = $server['rsa']['pubKey'];
$_SESSION['priKey'] = $server['rsa']['priKey'];

// Define and set sessions
if (defined('LOGIN_METHOD') && !isset($_SESSION['storedSessions'])) {
    $_SESSION['storedSessions'] = array();
}
if (isset($_GET['session']) && isset($_SESSION['storedSessions'][$_GET['session']])) {
    $_SESSION['session'] = $_GET['session'];
    $_SESSION['selectedServer'] = $_SESSION['storedSessions'][$_GET['session']]['server'];
}

$_SESSION['URL'] = $server['url'];

// Set RPC protocol
$protocols = array('xmlrpc', 'jsonrpc');
if (isset($_GET['protocol']) && in_array($_GET['protocol'], $protocols)) {
    $_SESSION['protocol'] = $_GET['protocol'];
}
if (!isset($_SESSION['protocol'])) {
    $_SESSION['protocol'] = DEFAULT_PROTOCOL;
}

$functionsStr = implode(PHP_EOL, $server['functions']);
$_SESSION['functions'] = getFunctionNames($functionsStr);

// If a specific function was requested we update selectedFunction and load its parameters
if (isset($_GET['f']) && in_array($_GET['f'], $_SESSION['functions'])) {
    $_SESSION['selectedFunction'] = $_GET['f'];
    $_SESSION['appParams'] = isset($_SESSION['appParams']) ? $_SESSION['appParams']:array();
    $_SESSION['functionParams'] = parseFunction($functionsStr, $_SESSION['selectedFunction']);
    if ($_SESSION['functionParams'] === false) {
        die('Signature syntax error: in "' . $functionsStr . '" at function ' . $_SESSION['selectedFunction']);
    }
}

// Load current function into selectedFunction
if (!isset($_SESSION['selectedFunction'])) {
    $_SESSION['selectedFunction'] = current($_SESSION['functions']);
    $nextFunction = current($_SESSION['functions']);
    $prevFunction = end($_SESSION['functions']);
} else {
    // Update prevFunction and nextFunction
    $nextKey = (array_search($_SESSION['selectedFunction'], $_SESSION['functions'])+1)%count($_SESSION['functions']);
    $prevKey = (array_search($_SESSION['selectedFunction'], $_SESSION['functions'])-1)%count($_SESSION['functions']);
    $nextFunction = $_SESSION['functions'][$nextKey];
    if ($prevKey < 0) {
        $prevFunction = end($_SESSION['functions']);
    } else {
        $prevFunction = $_SESSION['functions'][$prevKey];
    }
}

// Load selectedFunction parameters if not already set
if(!isset($_SESSION['functionParams'])) {
    $_SESSION['appParams'] = isset($_SESSION['appParams'])?$_SESSION['appParams']:array();
    $_SESSION['functionParams'] = parseFunction($functionsStr, $_SESSION['selectedFunction']);
    if($_SESSION['functionParams'] === false) {
        die('Signature syntax error: in "' . $functionsStr . '" at function ' . $_SESSION['selectedFunction']);
    }
}

// Debug setup
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['DEBUG'] = isset($_POST['DEBUG']);
} else {
    $_SESSION['DEBUG'] = isset($_SESSION['DEBUG']) && $_SESSION['DEBUG'];
}

// RSA setup
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['RSA'] = isset($_POST['RSA']);
} else {
    $_SESSION['RSA'] = isset($_SESSION['RSA']) && $_SESSION['RSA'];
}

// If we have a file upload we upload the file, move it into upload dir, update the functionParams
if (isset($_FILES) && count($_FILES)) {
    foreach ($_FILES as $key => $value) {
        list($filename, $tmpfile) = updateFile($_SESSION['functionParams'], $value['name'], $value['tmp_name']);
        $uploadfile = UPLOAD_DIR . '/' . $filename;
        move_uploaded_file($tmpfile, $uploadfile);
    }
    die();
}

/* Update the values of the already completed input values */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['params'])) {
    $_SESSION['appParams'] = isset($_SESSION['appParams'])?$_SESSION['appParams']:array();
    $_SESSION['functionParams'] = updateVals($_SESSION['functionParams'], $_POST['params']);
}

/* For arrays parameters */
if (isset($_GET['multiply'])) {
    /* multiplies the selected array member */
    $_SESSION['functionParams'] = multiplyParameter($_GET['multiply'], $_SESSION['functionParams']);
}
if (isset($_GET['remove'])) {
    /* removes the selected array member */
    $_SESSION['functionParams'] = multiplyParameter($_GET['remove'], $_SESSION['functionParams'], true);
}

// Ajax request:

if (isAjax()) {
    if (isset($_GET['f'])) {
        showInputForm();
        die();
    }

    if (isset($_GET['l'])) {
        showLinks();
        die();
    }

    if (isset($_GET['server'])) {
        die();
    }

    if (isset($_GET['session'])) {
        die();
    }

    if (isset($_GET['protocol'])) {
        die();
    }

    if (isset($_GET['multiply']) || isset($_GET['remove'])) {
        showInputForm();
        die();
    }

    if (isset($_GET['functionsForm'])) {
        showFunctionsForm();
        die();
    }

    if (isset($_GET['sessionsForm'])) {
        showSessionsForm();
        die();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_GET['multiply'])) {
        showResult();
        die();
    }
}

// Normal request:

// get the input form
$inputForm = null;
ob_start();
showInputForm();
$inputForm = ob_get_contents();
ob_end_clean();

// do the request and store the result data
$resultData = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['params'])) {
    ob_start();
    showResult();
    $resultData = ob_get_contents();
    ob_end_clean();
}
/*
$mydata = '{"jsonrpc":"2.0","id":"1164602116","result":{"name":"queyimeng","age":36}}';
echo rsaDecode("Nqiz5beNwkB/2DD03V4fcGl7fPW537PRPTGSL/enD0NfmB4cK+u4TgFJYT6X2bx8ejA45AnPROQ7" .
               "D8ydOzSzM9dGxxery0zloCBDyyKIWPUFhbKEOKrRqcpLk1y4yByHABz8Jh23RzPpbFJl9nLrkLvc" .
               "QUXlMZN3CLBF9z9IrLM=");
exit;
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <title><?php echo esc(NAME); ?></title>
        <link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/highlight.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" type="text/css" href="//cdn.bootcss.com/jquery-datetimepicker/2.5.1/jquery.datetimepicker.min.css"/ >
        <script src="//cdn.bootcss.com/jquery/2.2.0/jquery.min.js" type="text/javascript"></script>
        <script src="js/jquery.datetimepicker.full.js" type="text/javascript"></script>
        <script src="js/jquery.ajax_upload.0.3.js" type="text/javascript"></script>
        <script src="js/functions.js" type="text/javascript"></script>
    </head>
    <body onload="hideLoad(); createUploadElements();">
        <span id="loader" style="display:block;"><img src="images/loader.gif" alt="Loading"/> Loading...</span>
        <div id="header">
            <span class="intense">应用测试器 - <?php echo esc(NAME); ?></span>
            <br/>
            <div>
                <form name="reset" method="post" action="index.php">
                    <input type="hidden" name="RESET" value="1" />
                    <input class="button" type="submit" value="重置所有"/>
                </form>
            </div>
            <br/>
                <div id="serversForm">
                    <form method="get" action="index.php">
                    <span>选择服务器:</span>
                    <?php
                    $arr = [];
                    $s = '';
                    foreach ($configArr as $k => $v) {
                        if ($s != $v['name']) {
                            $arr[] = [$v['name'], false, '', -1];
                            $s = $v['name'];
                        }
                        $arr[] = ['¦− '.$v['url'], true, $v, $k];
                    }
                    ?>
                    <select name="server" id="serverSelect" onchange="chooseServer();">
                        <?php foreach($arr as $v) { ?>
                            <option <?php echo $v[1] == false ? 'disabled' : '' ?> value="<?php echo $v[3] ?>" <?php if ($_SESSION['selectedServer'] == $v[3]) { echo 'selected="selected" '; }?>><?php echo $v[0] ?></option>
                        <?php } ?>
                    </select>
                    <input class="button" type="submit" value="OK" onclick="" />
                    </form>
                </div>

                <span id="sessionsFormContainer">
                    <?php showSessionsForm(); ?>
                </span>

                <div id="functionsForm">
                    <form method="get" action="index.php">
                    <span>选择函数:</span>
                    <?php showList('id="functionsSelect" name="f" onchange="chooseFunction();"', array_combine(array_values($_SESSION['functions']), $_SESSION['functions']), $_SESSION['selectedFunction']); ?>
                    <input class="button" type="submit" value="OK" />
                    <input class="button" type="button" onclick="window.location = 'index.php?RELOAD_FUNCTIONS=1'" value="重新读取函数列表"/>
                    </form>
                </div>
                <div id="links"><?php showLinks(); ?></div>
        </div>
        <div id="content">
                <div id="inputData">
                    <?php echo $inputForm; ?>
                </div>
                <div id="helpers">
                <?php
		 createHelper();
                ?>
                </div>
                <div id="resultData">
                <?php
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_GET['multiply'])) {
                        echo $resultData;
                    }
                ?>
                </div>
        </div>
    </body>
</html>
