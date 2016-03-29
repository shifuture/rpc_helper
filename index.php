<?php
ini_set('display_errors','On');

error_reporting(E_ALL);

require_once 'functions.php';

init();

// If reset tester is requested we destroy the session and clean up the upload dir
if (isset($_REQUEST['RESET']))
{
    cleanUploadDir();
    session_destroy();
    session_start();
}

// If reload functions is requested we unset $_SESSION functions variables
if (isset($_REQUEST['RELOAD_FUNCTIONS'])) {
    unset($_SESSION['functions']);
    unset($_SESSION['selectedFunction']);
    unset($_SESSION['functionParams']);
}

// Set server URL
if (isset($_GET['server']) && array_key_exists($_GET['server'], $servers)) {
    $_SESSION['selectedServer'] = $_GET['server'];
}
if (!isset($_SESSION['selectedServer'])) {
    $arrayKeys = array_keys($servers);
    $_SESSION['selectedServer'] = $arrayKeys[0];
}

// Define and set sessions
if (defined('LOGIN_METHOD') && !isset($_SESSION['storedSessions'])) {
    $_SESSION['storedSessions'] = array();
}
if (isset($_GET['session']) && isset($_SESSION['storedSessions'][$_GET['session']])) {
    $_SESSION['session'] = $_GET['session'];
    $_SESSION['selectedServer'] = $_SESSION['storedSessions'][$_GET['session']]['server'];
}

$_SESSION['URL'] = $servers[$_SESSION['selectedServer']];

// Set RPC protocol
$protocols = array('xmlrpc', 'jsonrpc');
if (isset($_GET['protocol']) && in_array($_GET['protocol'], $protocols)) {
    $_SESSION['protocol'] = $_GET['protocol'];
}
if (!isset($_SESSION['protocol'])) {
    $_SESSION['protocol'] = DEFAULT_PROTOCOL;
}

/* Functions setup */

// Load function names
if (!isset($_SESSION['functions'])) {
    $str = file_get_contents($functions);
    $_SESSION['functions'] = getFunctionNames($str);
}
// If a specific function was requested we update selectedFunction and load its parameters
if (isset($_GET['f']) && in_array($_GET['f'], $_SESSION['functions'])) {
    $_SESSION['selectedFunction'] = $_GET['f'];
    $_SESSION['functionParams'] = parseFunction(file_get_contents($functions), $_SESSION['selectedFunction']);
    if ($_SESSION['functionParams'] === false) {
        die('Signature syntax error: in "' . $functions . '" at function ' . $_SESSION['selectedFunction']);
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
    $_SESSION['functionParams'] = parseFunction(file_get_contents($functions), $_SESSION['selectedFunction']);
    if($_SESSION['functionParams'] === false) {
        die('Signature syntax error: in "' . $functions . '" at function ' . $_SESSION['selectedFunction']);
    }
}

// Debug setup
if (isset($_POST['DEBUG'])) {
    $_SESSION['DEBUG'] = true;
    unset($_POST['DEBUG']);
} else {
    $_SESSION['DEBUG'] = false;
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <title><?php echo esc(NAME); ?></title>
        <link href="css/style.css" rel="stylesheet" type="text/css" media="all" />
        <link href="css/highlight.css" rel="stylesheet" type="text/css" />
        <script src="//cdn.bootcss.com/jquery/1.2.6/jquery.min.js" type="text/javascript"></script>
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

                <?php if(count($servers) > 1) { ?>
                <div id="serversForm">
                    <form method="get" action="index.php">
                    <span>选择服务器:</span>
                    <select name="server" id="serverSelect" onchange="chooseServer();">
                        <?php foreach($servers as $server => $url) { ?>
                            <option value="<?php echo $server ?>" <?php if ($_SESSION['selectedServer'] == $server) { echo 'selected="selected" '; }?>><?php echo $server?></option>
                        <?php } ?>
                    </select>
                    <input class="button" type="submit" value="OK" onclick="" />
                    </form>
                </div>
                <?php } ?>

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
                echo "提示: unix_timestamp: " . time() . "<br/>";
                echo "提示: datetime: " . date("Y-m-d H:i:s") . "<br/>";
                echo "提示: dateTime.iso8601: " . date("Ymd\TH:i:s") . "<br/>";
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
