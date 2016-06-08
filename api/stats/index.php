<?php

include('../../vendor/autoload.php');
include('../../vendor/seostats/seostats/SEOstats/bootstrap.php');
include('../../src/Analytics.php');
include('../../src/Stats.php');
include('../../defines.php');

$link = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);

if (!$link) {
    echo "Could not connect to server or database";
    die();
}

$method = $_SERVER['REQUEST_METHOD'];


switch ($method) {

    case 'GET':


    case 'POST':
        
        if (!isset($_POST['url'])) {
            http_response_code(400);
            echo "Missing url POST parameter";
            die();
        }

        $prefix = isset($_POST['prefix']) ? $_POST['prefix'] : 'http';

        if (isset($_POST['prefix']) && !in_array(strtolower($_POST['prefix']), array('http', 'https'))) {
            http_response_code(400);
            echo "Optional prefix POST parameter must be one of: http, https";
            die();
        }

        $url = $prefix . '://' . $_POST['url'];

        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            http_response_code(400);
            echo "url POST parameter must be a valid url with http  / https removed.";
            die();
        }

        $analytics = new \premiumwebtechnologies\expireddomainscraper\Analytics($link);
        $stats = $analytics->getSeoStats($_POST['url']);

        header('Content-Type:application/json');
        http_response_code(200);
        echo json_encode($stats->getAll());
        break;
        http_response_code(201);

        break;

    case 'PUT':

        // $data = array();
        // $incoming = file_get_contents("php://input");
        // parse_str($incoming, $data);
        break;
    case 'DELETE':
        // 204 if delete successful, 404 if not found, 500 if something goes wrong, 405 if not allowed
        break;
}