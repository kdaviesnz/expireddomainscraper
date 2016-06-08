<?php


include(getcwd() . '/vendor/autoload.php');
include(getcwd() . '/vendor/seostats/SEOstats/SEOstats/bootstrap.php');
include(getcwd() . '/src/Analytics.php');
include(getcwd() . '/src/Stats.php');
include(getcwd() . '/defines.php');

use premiumwebtechnologies\expireddomainscraper\Analytics;




class AnalyticsTest extends PHPUnit_Framework_TestCase
{

    private $conn;
    private $analytics;

    public function __construct()
    {
       $conn = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);
        if (mysqli_connect_error()) {
            die('Could not connect to database');
        }
        $this->conn = $conn;
        $this->analytics = new Analytics($this->conn);
    }

    public function testGetSeoStats()
    {
//        echo "Testing Analytics: Analytics::getSEOStats()";
//        $stats = $this->analytics->getSeoStats('google.com');
//        print_r($stats->getAll());
    }

//    public function testCheckPrice()
//    {
//        /** @noinspection PhpUndefinedMethodInspection */
////        $this->assertTrue(false, "true didn't end up being false!");
//        $price = $this->analytics->checkPrice('1BQ2F.INFO');
//    }
//
//    public function testCheckStatus()
//    {
//        $this->analytics->checkStatus('1BQ2F.INFO');
//    }
//
////    public function testGetGoogleBacklinks()
////    {
////        $this->analytics->getGoogleBacklinks('http://foxnews.com');
////    }
//
//    public function testGetRankData()
//    {
//        $this->analytics->getRankData('IBQ2F.INFO', array(), false);
//    }
//
//    public function testGetWhoIs()
//    {
//        $this->analytics->getWhoIs('IBQ2F.INFO', array());
//    }
//
//    public function testGetDomainRankHistory()
//    {
//        $this->analytics->getDomainRankHistory('IBQ2F.INFO');
//    }
//
//    public function testGetBacklinks()
//    {
//        $this->analytics->getBacklinks('google', 'IBQ2F.INFO');
//    }
//
//    public function testGetPageRank()
//    {
//        $this->analytics->getPageRank('IBQ2F.INFO', array());
//    }
//
//    public function testGetCountryCode()
//    {
//        $this->analytics->getCountryCode();
//    }
//
//    public function testCheckEstimatedTimeBeforeExpiration()
//    {
//        $this->analytics->checkEstimatedTimeBeforeExpiration('IBQ2F.INFO');
//    }
//
//    public function testGetYahooBacklinksCount()
//    {
//        $this->analytics->getYahooBacklinksCount('IBQ2F.INFO');
//    }
//
}
