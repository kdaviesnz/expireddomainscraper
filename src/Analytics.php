<?php
declare(strict_types = 1); // must be first line

namespace premiumwebtechnologies\expireddomainscraper; // use vendorname\subnamespace\classname;




class Analytics
{

    private $conn;
    private $guzzleClient;

    /**
     * @return mixed
     */
    public function getConn()
    {
        return $this->conn;
    }

    /**
     * @param mixed $conn
     */
    public function setConn($conn)
    {
        $this->conn = $conn;
    }


    public function __construct(\mysqli $conn)
    {
        $this->setConn($conn);
        $this->guzzleClient = new \GuzzleHttp\Client();
    }

    // Tested
    public function checkEstimatedTimeBeforeExpiration(string $domain): array
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        $sql = "SELECT `auction_end_time` FROM `ed_domainnames` 
                WHERE `domain_name`='$domainSafe'
                 AND `gd_auction_end_time`>NOW()";
        $res = mysqli_query($this->getConn(), $sql);
        if (mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $auctionEndTime = $row['gd_auction_end_time'];
            $estimatedTimeBeforeExpiration = strtotime($auctionEndTime) < time() ? 0 : strtotime($auctionEndTime) - time();
            $estimatedTimeBeforeExpiration = $this->sec2Time($estimatedTimeBeforeExpiration);
        }

        return array('estimatedTimeBeforeExpiration' => $estimatedTimeBeforeExpiration);
    }

    // tested
    public function checkPrice(string $domain): float
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        $sql = "SELECT `gd_asking_price_current_bid` 
                FROM `ed_domainnames` 
                WHERE `domain_name`='$domainSafe'";
        $res = mysqli_query($this->getConn(), $sql);
        $price = "-1";
        if (mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $price = trim($row['gd_asking_price_current_bid'], '$');
        }
        return $price * 1;
    }

    // tested
    public function checkStatus(string $domain): string
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        $sql = "SELECT `gd_auction_end_time`  FROM `ed_domainnames`
                WHERE `domain_name`='$domainSafe' 
                AND `gd_auction_end_time`>NOW()";
        $res = mysqli_query($this->getConn(), $sql);
        $status = "Not known";
        if (mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $status = $row['gd_auction_end_time'] > date("Y-m-d H:i:s") ? 'Available' : 'Taken';
        }
        return $status;
    }

//    public function generateBacklinkProfileReport(string $itemToQuery, string $app_api_key): array
//    {
//        require_once 'majesticseo/majesticseo-external-rpc/APIService.php';
//        $endpoint = "http://enterprise.majesticseo.com/api_command"; // http://developer.majesticseo.com/api_command
//
//        $parameters = array();
////   $parameters["MaxSourceURLs"] = 10;
//        $parameters["URL"] = $itemToQuery;
//        $parameters["GetUrlData"] = 1;
//        $parameters["MaxSourceURLsPerRefDomain"] = 1;
////   $parameters["datasource"] = "fresh";
//
//        $api_service = new APIService($app_api_key, $endpoint);
//        $response = $api_service->executeCommand("GetTopBackLinks", $parameters);
//
//        $report = 'Domain:'.$parameters["URL"];
//
//        if ($response->isOK() == "true") {
//            $results = $response->getTableForName("URL");
//            $tableParams = $results->getParams();
//            $report.="\nTotal # backlinks:".$tableParams['TotalBackLinks'];
//            $headers = $results->getTableHeaders();
//            $rows = $results->getTableRows();
//            $report.="\n".implode(",", $headers);
//            while (list($i, $row)=each($rows)) {
//                $report.="\n".implode(",", $row);
//            }
//        }
//
//        $filename = time().".csv";
//        file_put_contents(plugin_dir_path(__FILE__)."/majesticseo/backlinkprofilereports/$filename", $report);
////   return array('downloadReportLink'=>plugin_dir_url(__FILE__)."/majesticseo/backlinkprofilereports/$filename");
//        return array('downloadReportLink'=>$filename);
//
//    }

    public function getSeoStats(string $url): \premiumwebtechnologies\expireddomainscraper\Stats
    {
        $stats = array();
        try {
            $stats = new \premiumwebtechnologies\expireddomainscraper\Stats($url);
        }
        catch (\Exception $e) {
            echo 'Caught SEOstatsException: ' .  $e->getMessage();
        }

        return $stats;

    }

    // Tested
    public function getRankData(string $domain, array $domainDetails, bool $recheck = false): bool
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        if (empty($domainDetails)) {
            $domainDetails = $this->getDomainDetails($domain);
        }
        
        $sql = "UPDATE `ed_domainnames` SET `last_checked`= NOW(), 
                    `no_google_backlinks`='$noBackLinksSafe', 
                    `valid_pr`='1'
                    WHERE `domain_name`='$domainSafe'";
        mysqli_query($this->getConn(), $sql);
        $rankData['noGoogleBacklinks'] = $rawDataArr['pop_google_links'];

        return $rankData;
    }

    // Tested
    public function getWhoIs(string $domain, array $domainDetails = null)
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        if (empty($domainDetails)) {
            $domainDetails = $this->getDomainDetails($domain);
        }
        if ($domainDetails['createdMonth'] == null ||
            $domainDetails['createdMonth'] == 0 ||
            $domainDetails['createdYear'] == null ||
            $domainDetails['createdYear'] == '1970' ||
            strtotime($domainDetails['last_checked']) > strtotime("yesterday")
        ) {
            $whois = new Whois();
            $query = $domain;
            $result = $whois->Lookup($query, false);
            if (!isset($result['regrinfo'])
                || !isset($result['regrinfo']['domain']) || ($result['regrinfo']['registered'] == 'no')
            ) {
                $result['createdMonth'] = '-1';
                $result['createdYear'] = '-1';
                $result['expiryDate'] = '-1';
            } else {
                $result['createdMonth'] =
                    isset($result['regrinfo']['domain']['created']) ?
                        date('M', strtotime($result['regrinfo']['domain']['created'])) : -1;
                $result['createdYear'] =
                    isset($result['regrinfo']['domain']['created']) ?
                        date('Y', strtotime($result['regrinfo']['domain']['created'])) : -1;
                $result['expiryDate'] =
                    isset($result['regrinfo']['domain']['created']) ?
                        date('Y', strtotime($result['regrinfo']['domain']['expires'])) : -1;
            }
            $monthCreatedSafe = mysqli_real_escape_string($this->getConn(), $result['createdMonth']);
            $yearCreatedSafe = mysqli_real_escape_string($this->getConn(), $result['createdYear']);
            $expiryDateSafe = mysqli_real_escape_string($this->getConn(), $result['expiryDate']);
            $sql = "UPDATE `ed_domainnames` SET `last_checked`=NOW(), 
                    `month_created`='$monthCreatedSafe', 
                    `year_created`='$yearCreatedSafe', 
                    `expiry_date`='$expiryDateSafe'
                 WHERE `domain_name`='$domainSafe'";
            mysqli_query($this->getConn(), $sql);
        } else {
            $result = $domainDetails;
        }
        return $result;
    }

    public function getYahooBacklinksCount(string $url): int
    {
        // get this from https://developer.apps.yahoo.com/wsregapp/
        $appid = 'daGxWkbV34Fe9UrDWzKefIgbxzm3OaCpmXHmx1VLQutH5pynu9sAlDWMzPVmMez';
        $url = "http://search.yahooapis.com/WebSearchService/V1/webSearch?appid=$appid&query=site:$url&results=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.yoursite.com/');
        $response = curl_exec($ch);
        curl_close($ch);
        @$xml = simplexml_load_string($response);
        return $xml ? $xml->attributes()->totalResultsAvailable : 0;
    }

    // Tested
    public function getGoogleBacklinks(string $url): array
    {
        @$site = fopen('http://www.google.com/search?q=link:' . urlencode($url), 'r');
        $total = '';
        if (!$site) {
            return '-1';
        } else {
            while ($cont = fread($site, 1024657)) {
                $total .= $cont;
            }
            fclose($site);
            $match_expression = '/of about <b>(.*)<\/b> linking to/Us';
            preg_match($match_expression, $total, $matches);
            return $matches[1];
        }
    }

    // tested
    public function getDomainRankHistory(string $domain): string
    {
        // return in json format
        $url = 'http://us.backend.semrush.com/?action=report&domain='
            . str_replace(
                array('http://', 'https://'),
                array('', ''),
                strtolower($domain)
            )
            . '&type=domain_rank_history';
        $res = $this->guzzleClient->request('GET', $url);
        $response = json_decode((string)$res->getBody());
        return $response;
    }

    // tested
    public function getBacklinks(string $se, string $domain): array
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        $domainDetails = $this->getDomainDetails($domain);
        if ($se == 'google') {
            if (empty($domainDetails['no_google_backlinks'])
                || strtotime($domainDetails['last_checked']) < strtotime(date("Y-m-d"))
            ) {
                $noBackLinks = $this->getGoogleBacklinks($domain);
                $noBackLinks = empty($noBackLinks) ? 0 : $noBackLinks;
                $noBackLinksSafe = mysqli_real_escape_string($this->getConn(), $noBackLinks);
                $sql = "UPDATE `ed_domainnames` SET `last_checked`=NOW(), `no_google_backlinks`='$noBackLinksSafe'
                     WHERE `domain_name`='$domainSafe'";
                mysqli_query($this->getConn(), $sql);
                return $noBackLinks;
            } else {
                return $domainDetails['no_google_backlinks'];
            }
        } else {
            if (empty($domainDetails['no_yahoo_backlinks'])
                || strtotime($domainDetails['last_checked']) < strtotime("yesterday")
            ) {
                $noBackLinks = $this->getYahooBacklinksCount($domain);
                $noBackLinks = empty($noBackLinks) ? 0 : $noBackLinks;
                $noBackLinksSafe = mysqli_real_escape_string($this->getConn(), $noBackLinks);
                $sql = "UPDATE `ed_domainnames` SET `last_checked`=NOW(), 
                        `no_yahoo_backlinks`='$noBackLinksSafe'
                     WHERE `domain_name`='$domainSafe'";
                mysqli_query($this->getConn(), $sql);
                return $noBackLinks;
            } else {
                return $domainDetails['no_yahoo_backlinks'];
            }
        }
    }

    // tested
    public function getPageRank(string $domain, array $domainDetails = null): int
    {
        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);
        if (empty($domainDetails)) {
            $domainDetails = $this->getDomainDetails($domain);
        }
        if ($domainDetails['PR'] == null || strtotime($domainDetails['last_checked']) < strtotime("yesterday")) {
            $PR = $this->getPageRank($domain) + 1;
            $PRSafe = mysqli_real_escape_string($this->getConn(), $PR);
            $sql = "UPDATE `ed_domainnames` SET `last_checked`=NOW(), `PR`='$PRSafe'
                 WHERE `domain_name`='$domainSafe'";
            mysqli_query($this->getConn(), $sql);
            return $PR;
        } else {
            return $domainDetails['PR'];
        }
    }

    // tested
    public function getCountryCode(): string
    {
        $ipParts = explode(".", $_SERVER['REMOTE_ADDR']);
        if (count($ipParts) == 4) {
            $ipNumber = (16777216 * $ipParts[0]) + (65536 * $ipParts[1]) + (256 * $ipParts[2]) + $ipParts[3];
            $sql = "SELECT `country_code_1` as `country_code` FROM wp_country WHERE
               $ipNumber BETWEEN ip_start AND ip_end";
            $res = mysqli_query($this->getConn(), $sql);
            if (mysqli_num_rows($res) == 0) {
                return '';
            } else {
                $row = mysqli_fetch_assoc($res);
                return $row['country_code'];
            }
        }
        return '';
    }

    private function getDomainDetails(string $domain) : array
    {

        $domainSafe = mysqli_real_escape_string($this->getConn(), $domain);

        $sql = "SELECT `ed_domainnames`.`last_checked`,
              `ed_domainnames`.`PR`,
              `ed_domainnames`.`domain_authority`,
              `ed_domainnames`.`page_authority`,
              `ed_domainnames`.`trust_flow,`
              `ed_domainnames`.`month_created` as `createdMonth`,
              `ed_domainnames`.`year_created` as `createdYear`,
              `ed_domainnames`.`no_google_backlinks`,
              `ed_domainnames`.`no_yahoo_backlinks`,
              `ed_domainnames`.`expiry_date` as `expires`,
	      `ed_domainnames`.`valid_pr`,
	      `ed_domainnames`.`type`,
	      `ed_domainnames`.`google_index`,
	      `ed_domainnames`.`godaddy_traffic`,
	      `ed_domainnames`.`age`,
	      `ed_domainnames`.`last_checked`,
	      `ed_domainnames`.`time_left`,
              `ed_domainnames`.`domain_name` as `domain`,
              `ed_domainnames`.`gd_auction_end_time` as `auction_end_time`,
              `ed_domainnames`.`gd_auction_link` as `auction_link`,
              `ed_domainnames`.`gd_auction_type` as `auction_type`,
              `ed_domainnames`.`gd_asking_price_current_bid` as `asking_price_current_bid`,
	      `ed_domainnames`.`gd_number_of_bids` as `number_of_bids`,
	      `ed_domainnames`.`gd_domain_age` as `domain_age`,
	      `ed_domainnames`.`gd_traffic` as `traffic`,
	      `ed_domainnames`.`gd_valuation` as `valuation`,
	      `ed_domainnames`.`gd_is_adult` as `is_adult`,
	      `ed_domainnames`.`gd_guid` as `guid`,
	      `ed_domainnames`.`gd_description` as `description`
 	       FROM `ed_domainnames` WHERE 
	       `ed_domainnames`.`domain_name`='$domainSafe'";


        $res = mysqli_query($this->getConn(), $sql);
        $recs = array();
        if (mysqli_num_rows($res) == 0) {
            return array();
        } else {
            $row = mysqli_fetch_assoc($res);
            $auctionEndTime = $row['auction_end_time'];
            $estimatedTimeBeforeExpiration = strtotime($auctionEndTime) < time() ? 0 : strtotime($auctionEndTime) - time();
            $estimatedTimeBeforeExpiration = $this->sec2Time($estimatedTimeBeforeExpiration);
            $row['time_left'] = $estimatedTimeBeforeExpiration;
            if ($row['expires'] == null) {
                $row['expires'] = $auctionEndTime;
            }
            return $row;
        }
    }

    private function sec2Time(float $time, $adjust = true) : string
    {
        if ($adjust) {
            $time += 60 * 60 * 8;
        }
        $timeStr = '';
        // Days
        $dayInSeconds = 60 * 60 * 24;
        $days = floor($time / $dayInSeconds);
        $timeStr .= "$days" . 'D';
        $time -= $dayInSeconds * $days;
        // Hours
        $hoursInSeconds = 60 * 60;
        $hours = floor($time / $hoursInSeconds);
        $timeStr .= " $hours" . 'H';
        $time -= $hoursInSeconds * $hours;
        // Minutes
        $minutesInSeconds = 60;
        $minutes = floor($time / $minutesInSeconds);
        $timeStr .= " $minutes" . 'M';
        $time -= $minutesInSeconds * $minutes;
        return $timeStr;
    }

}
