<?php

include_once('../../vendor/autoload.php');
include_once('../../src/GoDaddy.php');
include_once('../../defines.php');

$link = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);

if(!$link){
    echo "Could not connect to server or database";
    die();
}

$method = $_SERVER['REQUEST_METHOD'];

// ref http://premiumwebtechnologies.com/blog/?p=53
$goDaddy = new \premiumwebtechnologies\expireddomainscraper\GoDaddy($link);

switch($method){

    case 'GET':

        if (!isset($_GET['daysLeft'])) {
            http_response_code(400);
            echo "Missing daysLeft GET parameter";
            die();
        }

        if (!is_numeric($_GET['daysLeft'])) {
            http_response_code(400);
            echo "daysLeft GET parameter must be a number";
            die();
        }

        $page = $_GET['page'] ?? 1;

        if (!is_numeric($page)) {
            http_response_code(400);
            echo "page GET parameter must be a number";
            die();
        }

        header('Content-Type:application/json');
        http_response_code(200);
        echo json_encode($goDaddy->getExpired($_GET['daysLeft'], $page));
        break;

    case 'POST':

        // Load domain data from GoDaddy into the database
        if (!isset($_POST['type'])) {
            http_response_code(400);
            echo "Missing type POST parameter";
            die();
        }

        if (!is_string($_POST['type'])) {
            http_response_code(400);
            echo "type POST parameter must be a string";
            die();
        }

        switch ($_POST['type']) {

            case '5_letter_auctions':
                $goDaddy->fiveLetterAuctions('uploads/');
                break;
            case 'auction_end_tomorrow':
                $goDaddy->auctionEndTomorrow('uploads/');
                break;
            case 'traffic200':
                $goDaddy->traffic200('uploads/');
                break;
            case 'traffic':
                $goDaddy->traffic('uploads/');
                break;
            case 'closeouts':
                $goDaddy->closeouts('uploads/');
                break;
            case 'bidding_service_auctions':
                $goDaddy->biddingServiceAuctions('uploads/');
                break;
            case 'tdnam_all_listings3':
                $goDaddy->tdnamAllListings3('uploads/');
                break;
            case 'tdnam_all_listings2':
                $goDaddy->tdnamAllListings2('uploads/');
                break;
            case 'tdnam_all_listings':
                $goDaddy->tdnamAllListings('uploads/');
                break;
            case 'auction_end_today':
                $goDaddy->auctionEndToday('uploads/');
                break;
            default:
                http_response_code(400);
                echo 'Type parameter must be one of auction_end_today, tdnam_all_listings, tdnam_all_listings2,
                      tdnam_all_listings3, bidding_service_auctions, traffic, traffic200, auction_end_tomorrow,
                      5_letter_auctions, closeouts';
                die();
        }

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