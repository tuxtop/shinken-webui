<?php

/**
 * Run livestatus command in WebService mode
 */

# Include
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/autoload.php');

# Load configuration
$config = yaml_parse_file($_SERVER['DOCUMENT_ROOT'].'/config.yml');

# Build reference
$ref = new \stdClass();
$ref->config = &$config;
$ref->ws = true;

# Start user session
session_name('shinken');
session_cache_expire(24*60);
session_start();

# User must be authenticated
if (!isset($_SESSION['auth']))
{
    header('HTTP/1.1 403 Frobidden');
    exit(0);
}

# Get user infos
$auth = $_SESSION['auth'];

# Get action to do
$act = isset($_POST['action']) ? $_POST['action'] : null;
if (!$act)
{
    header('HTTP/1.1 400 Bad Request');
    exit(0);
}

# ---- Functions ----

/**
 * Force recheck on one service
 */
function recheck($host=null, $service=null, $time=null)
{

    # Init vars
    global $ref;
    $cmd = null;

    # URL decode
    $host = urldecode($host);
    $service = urldecode($service);

    # Build command
    $forced = $time ? '' : '_FORCED';
    $time = $time ? $time : time();
    if ($host and $service) $cmd = "COMMAND [${time}] SCHEDULE${forced}_SVC_CHECK;${host};${service};${time}";
    elseif ($host) $cmd = "COMMAND [${time}] SCHEDULE${forced}_SVC_CHECK;${host};${service};${time}";
    else return null;

    # Run command
    $app = new \App($ref);
    $res = $app->livestatus($cmd);

}

# ---- End of functions ----

# Perform action
switch ($act)
{
    case 'recheck':
        $host = isset($_POST['host']) ? $_POST['host'] : null;
        $service = isset($_POST['service']) ? $_POST['service'] : null;
        $time = isset($_POST['time']) ? $_POST['time'] : null;
        recheck($host, $service, $time);
        break;
    case 'recheck:list':
        $time = isset($_POST['time']) ? $_POST['time'] : null;
        foreach ($_POST['recheck'] as $row)
        {
            $host = isset($row['host']) ? $row['host'] : null;
            $service = isset($row['service']) ? $row['service'] : null;
            recheck($host, $service, $time);
        }
        break;
    default:
        header('HTTP/1.1 404 Not Found');
}

?>
