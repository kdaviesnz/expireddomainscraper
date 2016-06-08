<?php
declare(strict_types = 1); // must be first line

namespace premiumwebtechnologies\expireddomainscraper; // use vendorname\subnamespace\classname;

set_time_limit(0);

class GoDaddy
{
    private $conn;

    /**
     * @return mixed
     */
    private function getConn():  \mysqli
    {
        return $this->conn;
    }

    /**
     * @param mixed $conn
     */
    private function setConn(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function __construct(\mysqli $conn)
    {
        $this->setConn($conn);

        $sql = "CREATE TABLE IF NOT EXISTS `ed_domainnames` (
  `domain_name` VARCHAR(100) NOT NULL,
  `valid_pr` TINYINT(4) NOT NULL,
  `last_checked` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `PR` INT(11) DEFAULT NULL,
  `month_created` INT(11) DEFAULT NULL,
  `year_created` INT(11) DEFAULT NULL,
  `no_google_backlinks` INT(11) DEFAULT NULL,
  `no_yahoo_backlinks` INT(11) DEFAULT NULL,
  `expiry_date` TIMESTAMP NULL DEFAULT NULL,
  `type` VARCHAR(20) NOT NULL,
  `check_pr_link` VARCHAR(100) NOT NULL,
  `google_index` INT(11) NOT NULL,
  `godaddy_traffic` INT(11) NOT NULL,
  `time_left` VARCHAR(20) NOT NULL,
  `price` VARCHAR(20) NOT NULL,
  `age` INT(11) NOT NULL,
  `from_godaddy` TINYINT(4) NOT NULL DEFAULT '0',
  `from_swiftdrops` TINYINT(4) NOT NULL DEFAULT '1',
  `gd_auction_link` VARCHAR(300) NOT NULL,
  `gd_auction_type` VARCHAR(20) NOT NULL,
  `gd_auction_end_time` DATETIME NOT NULL,
  `gd_asking_price_current_bid` FLOAT NOT NULL,
  `gd_number_of_bids` INT(11) NOT NULL,
  `gd_domain_age` INT(11) NOT NULL,
  `gd_traffic` INT(11) NOT NULL,
  `gd_valuation` FLOAT NOT NULL,
  `gd_is_adult` TINYINT(4) NOT NULL,
  `gd_guid` VARCHAR(300) NOT NULL,
  `gd_description` VARCHAR(300) NOT NULL,
  `domain_authority` DOUBLE DEFAULT NULL,
  `page_authority` DOUBLE DEFAULT NULL,
  `trust_flow` DOUBLE DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        mysqli_query($this->getConn(), $sql);

        $sql = "ALTER TABLE `ed_domainnames`
  ADD PRIMARY KEY (`domain_name`);";
        mysqli_query($this->getConn(), $sql);


        $sql = "ALTER TABLE `ed_domainnames` ADD `domain_authority` INT NULL AFTER `gd_description`, ADD `page_authority` INT NULL AFTER `domain_authority`, ADD `trust_flow` INT NULL AFTER `page_authority`;";

        mysqli_query($this->getConn(), $sql);

    }

    public function getExpired(int $daysLeft, int $page): array
    {

        $expired = array();

        if ($daysLeft < 0 || $daysLeft > 1000) {
            $daysLeft = 30;
        }

        if ($page < 1) {
            $page = 1;
        }

        $from = (int) ($page - 1) * TABLE_ROWS;

        $sql = "SELECT 
              `ed_domainnames`.`domain_name` as `Domain`,
              `gd_auction_link`,
              DATE_FORMAT(`ed_domainnames`.`gd_auction_end_time`, '%b %d %Y %h:%i %p') as `Expires`,
                DATEDIFF(`ed_domainnames`.`gd_auction_end_time`, NOW()) as `Time Left`,
                `ed_domainnames`.`domain_authority` as `DA`,
                `ed_domainnames`.`page_authority` as `PA`,
                `ed_domainnames`.`trust_flow` as `TF`                
                FROM `ed_domainnames`
               WHERE `gd_auction_end_time` IS NOT NULL
               AND DATEDIFF(`ed_domainnames`.`gd_auction_end_time`, NOW()) <= $daysLeft
               ORDER BY `gd_auction_end_time` DESC
        LIMIT $from, ". TABLE_ROWS;

        $res = mysqli_query($this->getConn(), $sql);
        if (empty(mysqli_error($this->getConn()))) {
            $expired = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $expired[] = $row;
            }
        }

        return $expired;

    }

    public function fiveLetterAuctions($path = null)
    {
        $this->downloadGodaddyZip('5_letter_auctions.xml.zip', $path);
    }

    public function auctionEndTomorrow($path = null)
    {
        $this->downloadGodaddyZip('auction_end_tomorrow.xml.zip', $path);
    }

    public function traffic200($path = null)
    {
        $this->downloadGodaddyZip('traffic200.xml.zip', $path);
    }

    public function traffic($path = null)
    {
        $this->downloadGodaddyZip('traffic.xml.zip', $path);
    }

    public function closeouts($path = null)
    {
        $this->downloadGodaddyZip('closeouts.xml.zip', $path);
    }

    public function biddingServiceAuctions($path = null)
    {
        $this->downloadGodaddyZip('bidding_service_auctions.xml.zip', $path);
    }

    public function tdnamAllListings3($path = null)
    {
        $this->downloadGodaddyZip('tdnam_all_listings3.xml.zip', $path);
    }

    public function tdnamAllListings2($path = null)
    {
        $this->downloadGodaddyZip('tdnam_all_listings2.xml.zip', $path);
    }

    public function tdnamAllListings($path = null)
    {
        $this->downloadGodaddyZip('tdnam_all_listings.xml.zip', $path);
    }

    public function auctionEndToday($path = null)
    {
        $this->downloadGodaddyZip('auction_end_today.xml.zip', $path);
    }

    private function downloadGodaddyZip(string $zipFile, $path = null, bool $tryAgain = true)
    {

        @unlink('uploads/' . $zipFile);

        $connection = new \Touki\FTP\Connection\Connection(
            'ftp.godaddy.com',
            'auctions',
            '',
            21,
            $timeout = 100000000,
            true
        );
        $connection->open();

        $wrapper = new \Touki\FTP\FTPWrapper($connection);

        $path = !empty($path) ? $path : getcwd() . 'api/godaddy/uploads/';

        $wrapper->get($path . $zipFile, $zipFile);

        $connection->close();

        if (!file_exists($path . $zipFile)) {
            if ($tryAgain) {
                $this->downloadGodaddyZip($zipFile, $path, false);
            }
        } else {
            $unzippedFile = $this->unzipFile($zipFile, $path);
            $this->parseGodaddyXML($unzippedFile, $path);
        }
    }

    public function unzipFile(string $filename, string $path): string
    {

        $unzipper = new \VIPSoft\Unzip\Unzip();
        $filenames = $unzipper->extract($path . $filename, $path);
        return $filenames[0];
    }

    public function parseGodaddyXML(string $filename, string $path): bool
    {
        $i = 0; // Don't process the first line
        foreach ($this->getLinesFromXMLGenerator($filename, $path) as $line) {
            if ($i > 0) {
                $this->parseGodaddyXMLEntry($line);
            }
            $i++;
        }
        return true;
    }

    private function getLinesFromXMLGenerator(string $filename, string $path) : \Generator
    {
        $handle = fopen($path . $filename, 'rb');
        if ($handle === false) {
            throw new Exception();
        }

        $line = "";
        while (($buffer = fgets($handle, 4096)) !== false) {

            $line .= $buffer;
            $lines = explode('</item>', $line);
            $lastLine = '';

            if (count($lines) > 1) {
                $lastLine = $lines[count($lines) - 1];
                unset($lines[count($lines) - 1]); // don't process the last time
                foreach ($lines as $item) {
                    yield $item . '</item>';
                }
            }

            // If the last line ends with </item> then yield, otherwise carry on
            if (substr($buffer, strlen($buffer) - 7) == '</item>') {
                yield $lastLine . '</item>';
                $line = '';
            } else {
                $line = $lastLine;
            }

        }

        fclose($handle);
    }

    private function godaddyXMLGenerator(string $unparsedData) : \Generator
    {

        //preg_match_all("/\<item\>\<title\>(.*?)\<\/title\>\<link\>(.*?)\<\/link\>\<description\>(.*?)\<\/description\>\<guid\>(.*?)\<\/guid\>\<\/item\>/i", $unparsedData, $matches);

        preg_match_all("/\<item\>.*?\<\/item\>/i", $unparsedData, $lines);

        foreach ($lines[0] as $item) {

            preg_match("/\<item\>\<title\>(.*?)\<\/title\>\<link\>(.*?)\<\/link\>\<description\>(.*?)\<\/description\>\<guid\>(.*?)\<\/guid\>\<\/item\>/i", $item, $matches);

            $title = $matches[1];
            $link = str_replace(array('<![CDATA[', ']]>'), array('', ''), $matches[2]);
            $description = str_replace(array('<![CDATA[', ']]>'), array('', ''), $matches[3]);
            $guid = str_replace(array('<![CDATA[', ']]>'), array('', ''), $matches[4]);

            $keyValues = explode(",", $description);
            while (list($i, $kv) = each($keyValues)) {
                $temp = explode(":", $kv);
                $propName = $temp[0];
                unset($temp[0]);
                $propVal = implode(":", $temp);
                $propName = strtolower(str_replace(array(" ", "/"), array("_", "_"), trim($propName)));
                $$propName = $propVal;
            }
            if (isset($auction_end_time) && isset($number_of_bids)) {
                $domainSafe = mysqli_real_escape_string($this->getConn(), $title);
                $auctionLinkSafe = mysqli_real_escape_string($this->getConn(), $link);
                /** @noinspection PhpUndefinedVariableInspection */
                $auctionTypeSafe = mysqli_real_escape_string($this->getConn(), $auction_type ?? '');
                /** @noinspection PhpUndefinedVariableInspection */

                $auctionEndTimeSafe = date(
                    "Y-m-d H:i:s",
                    strtotime(str_replace(" (PST)", "", mysqli_real_escape_string($this->conn, $auction_end_time)))
                );

                $askingPriceCurrentBidSafe = mysqli_real_escape_string($this->conn, $asking_price_current_bid ?? '0');
                /** @noinspection PhpUndefinedVariableInspection */
                $numberOfBidsSafe = mysqli_real_escape_string($this->conn, $number_of_bids);
                /** @noinspection PhpUndefinedVariableInspection */
                $domainAgeSafe = mysqli_real_escape_string($this->conn, $domain_age);
                /** @noinspection PhpUndefinedVariableInspection */
                $trafficSafe = mysqli_real_escape_string($this->conn, $traffic);
                /** @noinspection PhpUndefinedVariableInspection */
                $valuationSafe = mysqli_real_escape_string($this->conn, $valuation);
                /** @noinspection PhpUndefinedVariableInspection */
                $isAdultSafe = $isadult == 'true';
                $guidSafe = mysqli_real_escape_string($this->conn, $guid);
                $descriptionSafe = mysqli_real_escape_string($this->conn, $description);
                $sql = "INSERT INTO `ed_domainnames` (`from_godaddy`, `domain_name`, `gd_auction_link`, 
                    `gd_auction_type`, `gd_auction_end_time`, `gd_asking_price_current_bid`, 
                    `gd_number_of_bids`, `gd_domain_age`, `gd_traffic`, `gd_valuation`, `gd_is_adult`,
                     `gd_guid`, `gd_description`)
                      VALUES ('1', '$domainSafe', '$auctionLinkSafe', '$auctionTypeSafe', '$auctionEndTimeSafe', 
                      '$askingPriceCurrentBidSafe', '$numberOfBidsSafe', '$domainAgeSafe', '$trafficSafe', 
                      '$valuationSafe', '$isAdultSafe', '$guidSafe', '$descriptionSafe')
                       ON DUPLICATE KEY UPDATE `from_godaddy`='1', `gd_auction_link`='$auctionLinkSafe' ,
                        `gd_auction_end_time`='$auctionEndTimeSafe', 
                        `gd_asking_price_current_bid`='$askingPriceCurrentBidSafe', 
                        `gd_number_of_bids`='$numberOfBidsSafe', `gd_domain_age`='$domainAgeSafe', 
                        `gd_traffic`='$trafficSafe', `gd_valuation`='$valuationSafe', 
                        `gd_is_adult`='$isAdultSafe', `gd_guid`='$guidSafe', 
                        `gd_description`='$descriptionSafe'";
                yield $sql;
            }
        }
    }

    private
    function parseGodaddyXMLEntry(string $unparsedData): bool
    {

        foreach ($this->godaddyXMLGenerator($unparsedData) as $sql) {
            mysqli_query($this->conn, $sql);
        }

        return true;

    }
}
