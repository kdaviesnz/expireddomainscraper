<?php

include(getcwd() . '/vendor/autoload.php');
include(getcwd() . '/vendor/seostats/seostats/SEOstats/bootstrap.php');
include(getcwd() . '/src/Analytics.php');
include(getcwd() . '/src/Stats.php');
include(getcwd() . '/defines.php');

use premiumwebtechnologies\expireddomainscraper\Analytics;


$conn = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);
if (mysqli_connect_error()) {
    die('Could not connect to database');
}
$analytics = new Analytics($conn);

echo "Testing Analytics: Analytics::getSEOStats()";
$stats = $analytics->getSeoStats('http:google.com');
print_r($stats->getAll());