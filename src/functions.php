<?php



function wpex_getTotals(){
    return array('totalExpiredCloseoutDomains'=>getTotalExpiredCloseoutDomains(), 'totalExpiringPublicDomains'=>getTotalExpiringPublicDomains(), 'totalExpiringPrivateDomains'=>getTotalExpiringPublicDomains(), 'dateOfLastUpdate'=>getDateOfLastUpdate());
}









/* Main functions end */



/*
if(isset($_GET['run'])){
    switch($_GET['run']){
        case 'time2Secs':
	     echo time2Secs("0D 8H");
	     break;
        case 'guid':
	     echo getRemoteFileCurl('https://auctions.godaddy.com/trpItemListing.aspx?miid=63859290', 'GET', array());
	     break;
        case 'five_letter_auctions':
             echo five_letter_auctions();
              break;
        case 'auction_end_tomorrow':
             echo auction_end_tomorrow();
              break;
        case 'traffic200':
             echo traffic200();
              break;
        case 'traffic':
             echo traffic();
              break;
        case 'closeouts':
             echo closeouts();
              break;
        case 'bidding_service_auctions':
             echo bidding_service_auctions();
              break;
        case 'tdnam_all_listings3':
             echo tdnam_all_listings3();
              break;
        case 'tdnam_all_listings2':
             echo tdnam_all_listings2();
              break;
        case 'tdnam_all_listings':
             echo tdnam_all_listings();
              break;
        case 'getTotals':
	    echo json_encode(getTotals());
	    break;
        case 'downloadGodaddyFiles':
	    echo downloadGodaddyFiles();
	    break;
        case 'checkEstimatedTimeBeforeExpiration':
            echo json_encode(checkEstimatedTimeBeforeExpiration($_POST['domain']));
            break;
        case 'checkPrice':
            echo json_encode(checkPrice($_POST['domain']));
            break;
        case 'checkStatus':
            echo json_encode(checkStatus($_POST['domain']));
            break;
        case 'generateBacklinkProfileReport':
            echo json_encode(generateBacklinkProfileReport($_POST['domain'], "3E9FA009E9BEFF2D8D350740C7D23663"));
            break;
        case 'downloadBacklinkReport':
            echo json_encode(downloadBacklinkProfileReport($_GET['report']));
            break;
        case 'generateCSV':
            if(isset($_GET['from'])){
//                 generateCSV($_GET['memberID'], $_GET['from'], $_GET['noRecords'], $_GET['search'], $_GET['col'], $_GET['sortDesc']);
                 generateHTML($_GET['memberID'], $_GET['from'], $_GET['noRecords'], $_GET['search'], 'PR', '1');
            }
	    else{
//                 generateCSV(null, 0, null, null, 'PR', 1);
                 generateHTML(null, 0, null, null, 'PR', 1);
            }
	    break;
        case 'cron_em':
            echo cron_em();
            break;
        case 'cron_wi':
            echo cron_wi();
            break;
        case 'cron_sd':
            echo cron_sd();
            break;
        case 'swiftdrops':
            echo json_encode(swiftdrops());
            break;
        case 'addExpiredDomainRow':
            echo json_encode(addExpiredDomainRow($_POST['domain']));
            break;
        case 'getValidPageRank':
            if(empty($_POST['domain'])){
                  // echo "http://alllimoservices.com";
                   $_POST['domain'] = "http://alllimoservices.com";
             }
	    $rankData = json_encode(getRankData($_POST['domain'], null));
	    echo $rankData;
            break;
        case 'getRankData':
	    echo json_encode(getRankData('cnn.com'));
            break;
        case 'loadDomainsFromCSV':
            echo loadDomainsFromCSV();
	    break;
        case 'ipn':
            echo ipn();
	    break;
        case 'checkEmailField':
            echo checkEmailField($_POST['email']);
	    break;
        case 'edLogout':
            echo edLogout();
	    break;
        case 'edLogin':
            echo edLogin($_POST['email'], $_POST['password']);
	    break;
        case 'addEDUser':
            echo addEDUser($_POST['email'], $_POST['firstName'], $_POST['lastName'], $_POST['password'], $_POST['type']);
	    break;
        case 'getExpiredDomains':
	    echo json_encode(getExpiredDomains($_GET['memberID'], $_GET['from'], $_GET['noRecords'], $_GET['search'], $_GET['col'], isset($_GET['sortDesc'])?$_GET['sortDesc']:1, isset($_GET['filterCols'])?explode(",", $_GET['filterCols']):array('type', 'type', 'PR', 'PR'), isset($_GET['filters'])?explode(",", $_GET['filters']):null, isset($_GET['operators'])?explode(",", $_GET['operators']):null));
            break;
        case 'storePreferences':
            $_POST['filterCols'] = isset($_POST['filterCols'])?explode(",", $_POST['filterCols']):array('type', 'type', 'PR', 'PR');
            $_POST['filters'] = isset($_POST['filters'])?explode(",", $_POST['filters']):null;
            $_POST['operators'] = isset($_POST['operators'])?explode(",", $_POST['operators']):null;
	    echo storePreferences($_POST);
            break;
        case 'getWhoIs':
	    echo json_encode(getWhoIs($_POST['domain']));
            break;
        case 'getPageRank':
	    echo wpGetPageRank($_POST['domain']);
            break;
        case 'getBacklinks':
	    echo getBacklinks($_GET['se'], $_POST['domain']);
            break;
     }
    die();
}
*/


function wpex_swiftdrops(){
    $loginPage = getRemoteFileCurl("http://auctions.swiftdrops.com/user/login","GET",array());
    preg_match("/\<input type\=\"hidden\" name\=\"ci_csrf_token\" value\=\"(.*?)\" \/\>/uis", $loginPage, $matches);
    if(isset($matches[1])){
        $token = $matches[1];
        $login  = getRemoteFileCurl("http://auctions.swiftdrops.com/user/login","POST", "ci_csrf_token=$token&username=supremeoverlord%40searchenginerankingsolutions.com&password=LHyJubWp&Submit=Submit", "http://auctions.swiftdrops.com/user/login");
    }
    else{
        $login = $loginPage; // we're already logged in
    }
    loadSwiftDrops($login);

    for($i=1;$i<80;$i++){
        $page = getRemoteFileCurl("http://auctions.swiftdrops.com/user/auctions/show_list/200/pr:desc/".(200*$i), "GET", array());
        loadSwiftDrops($page);
    }
}

function wpex_loadSwiftDrops($page){
    global $wpdb;
    echo $page;
    preg_match("/\<\!\-\- Auctions Table \-\-\>.*?(\<table.*?\<\/table\>)/uis", $page, $matches);
    $domainsTable = $matches[1];
    preg_match_all("/\<tr.*?\>.*?\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>\<a href\=\"(.*?)\" target\=\"_blank\"\>(.*?)\<\/a\>\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>\<td\>(.*?)\<\/td\>.*?<\/tr\>/uis", $domainsTable, $matches);

    $domains = $matches[1];
    $types = $matches[2];
    $checkPRLinks = $matches[3];
    $prs=$matches[4];
    $backlinks=$matches[5];
    $googleIndexes=$matches[6];
    $ages=$matches[7];
    $godaddyTraffic=$matches[8];
    $timesLeft=$matches[9];
    $prices=$matches[10];
    while(list($i, $domain)=each($domains)){
        $domainNameSafe = mysql_real_escape_string($domain);
        $typeSafe = mysql_real_escape_string($types[$i]);
        $checkPRLinkSafe = mysql_real_escape_string($checkPRLinks[$i]);
        $prSafe = mysql_real_escape_string($prs[$i]+1);
        $noBacklinksSafe = mysql_real_escape_string($backlinks[$i]);
        $googleIndexSafe = mysql_real_escape_string($googleIndexes[$i]);
        $ageSafe = mysql_real_escape_string($ages[$i]);
        $godaddyTrafficSafe = mysql_real_escape_string($godaddyTraffic[$i]);
        $timeLeftSafe = mysql_real_escape_string(adjustTimeLeft($timesLeft[$i]));
        $priceSafe = mysql_real_escape_string($prices[$i]);
        $ageSafe = mysql_real_escape_string($ages[$i]);
        if($typeSafe!='0'){
            $sql = "INSERT INTO `ed_domainnames` (`domain_name`, `last_checked`, `PR`, `month_created`, `year_created`, `no_google_backlinks`, `no_yahoo_backlinks`, `expiry_date`, `type`, `check_pr_link`, `google_index`, `godaddy_traffic`, `time_left`, `price`,`age`) VALUES ('$domainNameSafe', CURRENT_TIMESTAMP, '$prSafe', NULL, NULL, '$noBacklinksSafe', NULL, NULL,'$typeSafe','$checkPRLinkSafe','$googleIndexSafe','$godaddyTrafficSafe','$timeLeftSafe','$priceSafe','$ageSafe') ON DUPLICATE KEY UPDATE `last_checked`=NOW(), `PR`='$prSafe', `type`='$typeSafe', `check_pr_link`='$checkPRLinkSafe', `google_index`='$googleIndexSafe', `godaddy_traffic`='$godaddyTrafficSafe', `no_google_backlinks`='$noBacklinksSafe', `time_left`='$timeLeftSafe', `price`='$priceSafe', `age`='$ageSafe';";
            $wpdb->query($sql);
            echo mysql_error();
        }
    }
    $sql = "DELETE FROM `ed_domainnames` WHERE `domain_name` LIKE '<%'";
    $wpdb->query($sql);
    echo mysql_error();
    $sql = "DELETE FROM `ed_domainnames` WHERE `type`=''";
    $wpdb->query($sql);
    echo mysql_error();
}



function wpex_getExpiredDomains($memberID, $from, $noRecords, $searchTerm, $col, $sortDesc, $filterCols, $filters, $operators, $resource=false){
    global $wpdb;
    if($searchTerm=='Insert keyword here...'){
        $searchTerm = null;
    }
    if(empty($col) || $col=='undefined'){
        $col='PR';
        $sortDesc = 1;
    }

    if(!GODADDY_ONLY){
        $sql = "SELECT `ed_domainnames`.`last_checked`,
              `ed_domainnames`.`PR`,
              `ed_domainnames`.`month_created` as `createdMonth`,
              `ed_domainnames``year_created` as `createdYear`,
              `ed_domainnames`.`no_google_backlinks`,
              `ed_domainnames`.`no_yahoo_backlinks`,
              `ed_domainnames`.`expiry_date` as `expires`,
	      `ed_domainnames`.`valid_pr`,
	      `ed_domainnames`.`type`,
	      `ed_domainnames`.`google_index`,
	      `ed_domainnames`.`godaddy_traffic`,
	      `ed_domainnames`.`age`,
	      `ed_domainnames`.`time_left`,
              `ed_domainnames`.`domain_name` as `domain` FROM `ed_domainnames` WHERE 1";
    }
    else{
        $sql = "SELECT `ed_domainnames`.`last_checked`,
              `ed_domainnames`.`PR`,
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
 	       FROM `ed_domainnames` WHERE 1 ";
    }
    if(!empty($searchTerm)){
        $searchTermSafe = mysql_real_escape_string($searchTerm);
        $sql .= " AND `ed_domainnames`.`domain_name` LIKE '%$searchTermSafe%'";
    }
    if(!empty($col)){
        if(!empty($filters)&&!empty($operators)){
            if(in_array(">", $operators) && in_array("<", $operators)){
                $colSafe = mysql_real_escape_string($filterCols[array_search(">", $operators)]);
                $val1Safe = mysql_real_escape_string($filters[array_search(">", $operators)]);
                $val2Safe = mysql_real_escape_string($filters[array_search("<", $operators)]);
                if($colSafe=='PR'){
                    $val1Safe++;
                    $val2Safe++;
                }
                $sql .= " AND ABS(`$colSafe`) > $val1Safe AND ABS(`$colSafe`) < $val2Safe ";
                unset($filterCols[array_search(">", $operators)]);
                unset($filterCols[array_search("<", $operators)]);
                unset($filters[array_search(">", $operators)]);
                unset($filters[array_search("<", $operators)]);
                unset($operators[array_search("<", $operators)]);
                unset($operators[array_search(">", $operators)]);
                $filters = array_values($filters);
                $filterCols = array_values($filterCols);
                $operators = array_values($operators);
            }
            if(!empty($filters)&&!empty($operators)){
                if(array_search("<>",$operators)>-1){
                    $sql .= "AND ";
                    $lastFilterCol = '';
                    while(list($i, $filterCol)=each($filterCols)){
                        $colSafe = mysql_real_escape_string($filterCol);
                        $valSafe = mysql_real_escape_string($filters[$i]);
                        $operatorSafe = mysql_real_escape_string($operators[$i]);
                        if($operatorSafe=='<>'){
                            if($lastFilterCol != $colSafe){
                                if(!empty($lastFilterCol)){
                                    $sql = ") AND ";
                                }
                                $sql .= "`$colSafe` NOT IN (";
                            }
                            $sql .= "'$valSafe'";
                            unset($operators[$i]);
                            if(in_array("<>", $operators)){
                                $sql.=",";
                            }
                            else{
                                $sql.=")";
                            }
                            $lastFilterCol = $colSafe;
                            unset($filterCols[$i]);
                            unset($filters[$i]);
                        }
                    }
                }
                if(array_search("=",$operators)>-1){
                    $sql .= " AND ";
                    $lastFilterCol = '';
                    $filterCols = array_values($filterCols);
                    $filters = array_values($filters);
                    $operators = array_values($operators);
                    reset($filterCols);
                    $i=0;
                    while(list($i, $filterCol)=each($filterCols)){
                        $colSafe = mysql_real_escape_string($filterCol);
                        $valSafe = mysql_real_escape_string($filters[$i]);
                        $operatorSafe = mysql_real_escape_string($operators[$i]);
                        if($operatorSafe=='='){
                            if($lastFilterCol != $colSafe){
                                if(!empty($lastFilterCol)){
                                    $sql = ") AND ";
                                }
                                $sql .= "`$colSafe` IN (";
                            }
                            $sql .= "'$valSafe'";
                            if(isset($filterCols[$i+1]) && $filterCols[$i+1]==$filterCol && $operators[$i+1]=='='){
                                $sql.=",";
                            }
                            else{
                                $sql.=")";
                            }
                            $lastFilterCol = $colSafe;
                        }
                    }
                }
            }
        }
        if($sortDesc!=-1){
            if(in_array(strtolower($col), array("pr", "age", "price", "google_index", "godaddy_traffic", "no_yahoo_backlinks", "no_google_backlinks", "year_created", "month_created"))){
                $sql .= " ORDER BY `from_godaddy` DESC, ABS(`$col`) " . ($sortDesc==1?'DESC':'ASC');
            }
            else{
                $sql .= " ORDER BY `from_godaddy` DESC, `$col` " . ($sortDesc==1?'DESC':'ASC');
            }
        }
        else{
            $sql .= " ORDER BY `from_godaddy` DESC";
        }
    }
    else{
        $sql .= " ORDER BY `from_godaddy` DESC";
    }
    if(!empty($noRecords)){
        $sql .= "  LIMIT $from, $noRecords";
    }
//echo $sql;
    if($resource){
        $res = mysql_query($sql);
        echo mysql_error();
        return $res;
    }
    else{
        $recs = $wpdb->get_results($sql, ARRAY_A);
        echo mysql_error();
        while(list($i,$rec)=each($recs)){
            if(isset($rec['auction_end_time'])){
                $auctionEndTime = $rec['auction_end_time'];
                $estimatedTimeBeforeExpiration = strtotime($auctionEndTime)<time()?0:strtotime($auctionEndTime)-time();
                $estimatedTimeBeforeExpiration = sec2Time($estimatedTimeBeforeExpiration);
                $recs[$i]['time_left'] = $estimatedTimeBeforeExpiration;
                if($rec['expires']==null){
                    $recs[$i]['expires'] = $auctionEndTime;
                }
                $recs[$i]['year_created'] = date("Y")-$recs[$i]['domain_age'];
            }
        }

//     print_r($recs);
        //    die();
        return $recs;
    }
}


function wpex_loadDomainsFromCSV(){
    global $wpdb;
    $domains = file('wp-content/plugins/expireddomains/expireddomains.csv');
    while(list($i, $domain)=each($domains)){
        $domainNameSafe = mysql_real_escape_string(trim($domain));
        $sql = "INSERT IGNORE INTO `ed_domainnames` (`domain_name`, `last_checked`, `PR`, `month_created`, `year_created`, `no_google_backlinks`, `no_yahoo_backlinks`, `expiry_date`) VALUES ('$domainNameSafe', CURRENT_TIMESTAMP, NULL, NULL, NULL, NULL, NULL, NULL);";
        $wpdb->query($sql);
        echo mysql_error();
    }
}


function wpex_parseGodaddyXML($file){
    $handle = fopen($file, 'r');
    if(!$handle){
        return false;
    }
    else{
        $chunksize = 1024;
        $i = 0;
        $unparsedData = '';
        while ( !feof( $handle ) && $i<100) {
            $unparsedData = parseGodaddyXMLEntry($unparsedData);
            $unparsedData .= fread( $handle, $chunksize );
        }
    }
}


function wpex_getTotalExpiredCloseoutDomains(){
    global $wpdb;
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiredClosoutDomains` FROM `ed_domainnames` WHERE `type`='Closeouts' and (`time_left`='0H' || `time_left`='0M' || (`expiry_date` IS NOT NULL AND `expiry_date`<NOW()))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiredClosoutDomains` FROM `ed_domainnames` WHERE `type`='Closeouts' and (`time_left`='0H' || `time_left`='0M' || (`expiry_date` IS NOT NULL AND `expiry_date`<NOW()))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiredClosoutDomains'];
}

function wpex_getTotalExpiringPublicDomains(){
    global $wpdb;
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiringPublicDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>NOW()))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiringPublicDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>NOW()))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiringPublicDomains'];
}

function wpex_getTotalExpiringPrivateDomains(){
    global $wpdb;
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiringPrivateDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>NOW()))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiringPrivateDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>NOW()))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiringPrivateDomains'];
}