<?php

/**
 * Alternative Web User Interface for Shinken 
 *
 * @author Julien "TuxTop" Dumont
 * @license GPLv3
 */

# Force redirection
if (!isset($_SERVER['REDIRECT_URL']))
{
    header('Location: /dashboard');
    exit(0);
}

# Include
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/autoload.php');

# Load configuration
$config = yaml_parse_file($_SERVER['DOCUMENT_ROOT'].'/config.yml');

# Build reference
$ref = new \stdClass();
$ref->config = &$config;

# Manage routes
$app = new \App($ref); 

# Dashboard (default page)
$app->url('/dashboard', 'Misc\Dashboard');

# Configuration pages
$app->url('/hostgroups', 'Config\HostGroups');
$app->url('/contactgroups', 'Config\ContactGroups');
$app->url('/contacts', 'Config\Contacts');

# Services status
$app->url('/search', 'Status\Search');
$app->url('/status/:host/:service', 'Status\Details');

# Shinken status
$app->url('/shinken', 'Misc\Status');

?>
