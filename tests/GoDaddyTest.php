<?php

include(getcwd() . '/vendor/autoload.php');
include(getcwd() . '/src/GoDaddy.php');
use premiumwebtechnologies\expireddomainscraper\GoDaddy;

class GoDaddyTest extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $goDaddy;

    public function __construct()
    {
       $conn = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);
       if (mysqli_connect_error()) {
            die('Could not connect to database');
       }
        $this->conn = $conn;
        $this->goDaddy = new GoDaddy($this->conn);
    }
    

    /*

       public function testtdnamAllListings3(){
        echo "Testing tdnamAllListings3";
        $this->goDaddy->tdnamAllListings3(getcwd().'/api/godaddy/uploads/');
    }
       public function testTdnamAllListings2(){
        echo "Testing tdnamAllListings2";
        $this->goDaddy->tdnamAllListings2(getcwd().'/api/godaddy/uploads/');
    }

       public function testTdnamAllListings(){
        echo "Testing tdnamAllListings";
        $this->goDaddy->tdnamAllListings(getcwd().'/api/godaddy/uploads/');
    }

    public function testTraffic200(){
        echo "Testing traffic200";
        $this->goDaddy->traffic200(getcwd().'/api/godaddy/uploads/');
    }
        public function testBiddingServiceAuctions(){
        echo "Testing biddingServiceAuctions";
        $this->goDaddy->biddingServiceAuctions(getcwd().'/api/godaddy/uploads/');
    }

    public function testTraffic(){
        echo "Testing traffic";
        $this->goDaddy->traffic(getcwd().'/api/godaddy/uploads/');
    }
         public function testAuctionEndToday()
    {
        echo "Testing auction end today auctions()";
        // This will get auctions from godaddy and import them into the database
        $this->goDaddy->auctionEndToday(getcwd().'/api/godaddy/uploads/');
    }

        public function testAuctionEndTomorrow(){
        echo "Testing auctionEndTomorrow";
        $this->goDaddy->auctionEndTomorrow(getcwd().'/api/godaddy/uploads/');
    }

    public function testCloseouts(){
        echo "Testing closeouts";
        $this->goDaddy->closeouts(getcwd().'/api/godaddy/uploads/');
    }

    public function testFiveLetterAuctions()
    {
       echo "Testing five letter auctions()";
       $this->goDaddy->fiveLetterAuctions(getcwd().'/api/godaddy/uploads/');
    }




    */



    /**
     *
     */
    public function testParseGodaddyXML() {

    }


    public function testGetExpired()
    {
        echo "Testing getExpired()";
        $daysLeft = 30;
        $page = 5;
        $domains = $this->goDaddy->getExpired($daysLeft, $page);
        print_r($domains);
        echo count($domains);
        $this->assertTrue(count($domains) > 0);
    }

}
