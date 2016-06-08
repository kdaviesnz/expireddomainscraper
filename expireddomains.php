<?php 
// Old code = not used

define('GODADDY_ONLY', true);

  
@session_start();


//error_reporting(E_ALL);

// ini_set('memory_limit', '500M');

date_default_timezone_set('PST');

set_time_limit(0);

include('phpwhois/whois.main.php');
include('pagerank.php');

@session_start();


function wpexpireddomains_get_yahoo_backlinks_count($url) {
	$appid = 'daGxWkbV34Fe9UrDWzKefIgbxzm3OaCpmXHmx1VLQutH5pynu9sAlDWMzPVmMez'; // get this from https://developer.apps.yahoo.com/wsregapp/
	$url = "http://search.yahooapis.com/WebSearchService/V1/webSearch?appid=$appid&query=site:$url&results=1";
 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.yoursite.com/');
	$response = curl_exec($ch);
	curl_close($ch);
	@$xml = simplexml_load_string($response);
	return $xml?$xml -> attributes() -> totalResultsAvailable:0;
}
 
function wpexpireddomains_google_backs($url){ 
    @$site = fopen('http://www.google.com/search?q=link:'.urlencode($url),'r'); 
    if(!$site){
        return '-1';
    }
    else{
        while($cont = fread($site,1024657)){ 
            $total .= $cont; 
        } 
        fclose($site); 
        $match_expression = '/of about <b>(.*)<\/b> linking to/Us'; 
        preg_match($match_expression,$total,$matches); 
        return $matches[1]; 
    }
}

function wpexpireddomains_getRankData($domain, $domainDetails, $recheck=false){
    global $wpdb;
    $domainSafe = mysql_real_escape_string($domain);
    if(empty($domainDetails)){
        $domainDetails = wpexpireddomains_getDomainDetails($domain);
    }
 if(1)
{
        $rawData = wpexpireddomains_getRemoteFileCurl("http://www.rankchecker.com/php/process.php", "POST","random=0.9786151547650515&s=gsearch&url=$domain");
        $rankData['valid'] = strpos(strtolower($rawData), '>valid<')!==false?1:0;

        if($rankData['valid']==0 && $recheck){
                 wpexpireddomains_getRankData($domain, $domainDetails, false);
        }
        $rawDataArr = (array)json_decode($rawData);
        $googleBacklinks = $rawDataArr['pop_google_links'];
        $noBackLinksSafe = mysql_real_escape_string($googleBacklinks);
        $isValidSafe = $rankData['valid'];
	$now = date("Y-m-d H:i:s");
        $sql = "UPDATE `ed_domainnames` SET `last_checked`='$now', `no_google_backlinks`='$noBackLinksSafe', `valid_pr`='$isValidSafe'
                WHERE `domain_name`='$domainSafe'";
    	$wpdb->query($sql);
        echo mysql_error();
        $rankData['noGoogleBacklinks'] = $rawDataArr['pop_google_links'];
    }
    else{
        $rankData['valid'] = $domainDetails['valid_pr'];
        $rankData['noGoogleBacklinks'] = $domainDetails['no_google_backlinks'];
    }
    return $rankData;
}

function wpexpireddomains_getBacklinks($se, $domain){
    global $wpdb;
    $domainSafe = mysql_real_escape_string($domain);
    $domainDetails = wpexpireddomains_getDomainDetails($domain);
    if($se=='google'){
         if(empty($domainDetails['no_google_backlinks']) || strtotime($domainDetails['last_checked'])<strtotime(date("Y-m-d"))){
	     $noBackLinks = wpexpireddomains_google_backs($domain);
             $noBackLinks = empty($noBackLinks)?0:$noBackLinks;
             $noBackLinksSafe = mysql_real_escape_string($noBackLinks);
	     $now = date("Y-m-d H:i:s");
             $sql = "UPDATE `ed_domainnames` SET `last_checked`='$now', `no_google_backlinks`='$noBackLinksSafe'
                     WHERE `domain_name`='$domainSafe'";
    	     $wpdb->query($sql);
             echo mysql_error();
             return $noBackLinks;
         }
	 else{
	     return $domainDetails['no_google_backlinks'];
         }

    }
    else{
         if(empty($domainDetails['no_yahoo_backlinks']) || strtotime($domainDetails['last_checked'])<strtotime("yesterday")){
	     $noBackLinks = wpexpireddomains_get_yahoo_backlinks_count($domain);
             $noBackLinks = empty($noBackLinks)?0:$noBackLinks;
             $noBackLinksSafe = mysql_real_escape_string($noBackLinks);
	     $now = date("Y-m-d H:i:s");
             $sql = "UPDATE `ed_domainnames` SET `last_checked`='$now', `no_yahoo_backlinks`='$noBackLinksSafe'
                     WHERE `domain_name`='$domainSafe'";
    	     $wpdb->query($sql);
             echo mysql_error();
             return $noBackLinks;
         }
	 else{
	     return $domainDetails['no_yahoo_backlinks'];
         }
    }
}


function wpexpireddomains_wpGetPageRank($domain, $domainDetails=null){
    global $wpdb;
    $domainSafe = mysql_real_escape_string($domain);
    if(empty($domainDetails)){
       $domainDetails = wpexpireddomains_getDomainDetails($domain);
    }
    if($domainDetails['PR']==null || strtotime($domainDetails['last_checked'])<strtotime("yesterday")){
         $PR = getPageRank($domain)+1;
         $PRSafe = mysql_real_escape_string($PR);
	 $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `ed_domainnames` SET `last_checked`='$now', `PR`='$PRSafe'
                 WHERE `domain_name`='$domainSafe'";
	 $wpdb->query($sql);
         echo mysql_error();
         return $PR;
    }
    else{
         return $domainDetails['PR'];
    }
}   

function wpexpireddomains_getWhoIs($domain, $domainDetails=null){
    global $wpdb;
    $domainSafe = mysql_real_escape_string($domain);
    if(empty($domainDetails)){
        $domainDetails = wpexpireddomains_getDomainDetails($domain);
    }
    if($domainDetails['createdMonth']==null || $domainDetails['createdMonth']==0 || $domainDetails['createdYear']==null || $domainDetails['createdYear']=='1970' || strtotime($domainDetails['last_checked'])>strtotime("yesterday")){
         $whois = new Whois();
         $query = $domain;
         $result = $whois->Lookup($query,false);
         if(!isset($result['regrinfo']) || !isset($result['regrinfo']['domain']) || ($result['regrinfo']['registered']=='no')){
            $result['createdMonth'] = '-1';
            $result['createdYear'] = '-1';
            $result['expiryDate'] = '-1';
         }
         else{
            $result['createdMonth'] = isset($result['regrinfo']['domain']['created'])?date('M', strtotime($result['regrinfo']['domain']['created'])):-1;
            $result['createdYear'] = isset($result['regrinfo']['domain']['created'])?date('Y', strtotime($result['regrinfo']['domain']['created'])):-1;
            $result['expiryDate'] = isset($result['regrinfo']['domain']['created'])?date('Y', strtotime($result['regrinfo']['domain']['expires'])):-1;
         }
         $monthCreatedSafe = mysql_real_escape_string($result['createdMonth']);
         $yearCreatedSafe = mysql_real_escape_string($result['createdYear']);
         $expiryDateSafe = mysql_real_escape_string($result['expiryDate']);
	 $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `ed_domainnames` SET `last_checked`='$now', `month_created`='$monthCreatedSafe', `year_created`='$yearCreatedSafe', `expiry_date`='$expiryDateSafe'
                 WHERE `domain_name`='$domainSafe'";
	 $wpdb->query($sql);
         echo mysql_error();
    }
    else{
         $results = $domainDetails;
    }
    return $result;
}

function wpexpireddomains_getExpiredDomains($memberID, $from, $noRecords, $searchTerm, $col, $sortDesc, $filterCols, $filters, $operators, $resource=false){
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
	//	$now = date("Y-m-d H:i:s", time()-(60*60*24));
	$now = date("Y-m-d H:i:s");
	$sql .= "and (`time_left`='0H' || `time_left`='0M' || (`gd_auction_end_time` IS NOT NULL AND `gd_auction_end_time`>'$now'))";

        if($sortDesc!=-1){
            if(in_array(strtolower($col), array("pr", "age", "price", "google_index", "godaddy_traffic", "no_yahoo_backlinks", "no_google_backlinks", "year_created", "month_created"))){
                  $sql .= " ORDER BY `gd_auction_end_time` ASC, ABS(`$col`) " . ($sortDesc==1?'DESC':'ASC');
   	    }
	    else{
                  $sql .= " ORDER BY `gd_auction_end_time` ASC, `$col` " . ($sortDesc==1?'DESC':'ASC');
            }
         }
	 else{
                  $sql .= " ORDER BY `gd_auction_end_time` ASC";
         }
     }
     else{
                  $sql .= " ORDER BY `gd_auction_end_time` ASC";
     }
     if(!empty($noRecords)){
         $sql .= "  LIMIT $from, $noRecords";
     }

     //             echo $sql;

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
            $estimatedTimeBeforeExpiration = wpexpireddomains_sec2Time($estimatedTimeBeforeExpiration);
	    $recs[$i]['time_left'] = $estimatedTimeBeforeExpiration;
	    if($rec['expires']==null){
		$recs[$i]['expires'] = $auctionEndTime;
            }
	    $recs[$i]['year_created'] = date("Y")-$recs[$i]['domain_age'];
        }
     }

     //    print_r($recs);
     //     die();
     return $recs;
     }
}

function wpexpireddomains_activate(){
     global $wpdb;

     $sql = "CREATE TABLE IF NOT EXISTS `ed_domainnames` (
  `domain_name` varchar(100) NOT NULL,
  `valid_pr` tinyint(4) NOT NULL,
  `last_checked` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `PR` int(11) NOT NULL DEFAULT '-1',
  `month_created` int(11) DEFAULT NULL,
  `year_created` int(11) DEFAULT NULL,
  `no_google_backlinks` int(11) DEFAULT NULL,
  `no_yahoo_backlinks` int(11) DEFAULT NULL,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `check_pr_link` varchar(100) NOT NULL,
  `google_index` int(11) NOT NULL,
  `godaddy_traffic` int(11) NOT NULL,
  `time_left` varchar(20) NOT NULL,
  `price` varchar(20) NOT NULL,
  `age` int(11) NOT NULL,
  `from_godaddy` tinyint(4) NOT NULL DEFAULT '0',
  `from_swiftdrops` tinyint(4) NOT NULL DEFAULT '1',
  `gd_auction_link` varchar(300) NOT NULL,
  `gd_auction_type` varchar(20) NOT NULL,
  `gd_auction_end_time` datetime NOT NULL,
  `gd_asking_price_current_bid` float NOT NULL,
  `gd_number_of_bids` int(11) NOT NULL,
  `gd_domain_age` int(11) NOT NULL,
  `gd_traffic` int(11) NOT NULL,
  `gd_valuation` float NOT NULL,
  `gd_is_adult` tinyint(4) NOT NULL,
  `gd_guid` varchar(300) NOT NULL,
  `gd_description` varchar(300) NOT NULL,
  PRIMARY KEY (`domain_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
     $wpdb->query($sql);
     echo mysql_error();	       
}

register_activation_hook(__FILE__, 'wpexpireddomains_activate');

require_once(ABSPATH . WPINC . '/pluggable.php');
global $current_user;
get_currentuserinfo();
$userID = $current_user->ID;

$memberID = (current_user_can('manage_options') || $userID=='1') && isset($_GET['user_id'])?$_GET['user_id']:$userID;


function expired_domains_action_javascript(){
    global $memberID;
    global $userID;
?>
  <link rel="stylesheet" type="text/css" href="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/expireddomains.css">
  <script src="http://yui.yahooapis.com/3.5.1/build/yui/yui-min.js"></script>
   <script type="text/javascript" src="http://yui.yahooapis.com/combo?2.9.0/build/utilities/utilities.js&2.9.0/build/container/container-min.js&2.9.0/build/menu/menu-min.js&2.9.0/build/button/button-min.js&2.9.0/build/slider/slider-min.js&2.9.0/build/colorpicker/colorpicker-min.js&2.9.0/build/json/json-min.js&2.9.0/build/tabview/tabview-min.js&2.9.0/build/calendar/calendar-min.js"></script>
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/calendar/assets/skins/sam/calendar.css">
    <script src="http://yui.yahooapis.com/2.9.0/build/calendar/calendar-min.js"></script>
    <script type="text/javascript" src="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/expireddomains.js"></script>
    <script type="text/javascript" src="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/gsdom.js"></script>
    </style>
    <script type="text/javascript">
    YAHOO.util.Event.onAvailable('expiredDomainsTableContainer', EXPIREDDOMAINS.renderExpiredDomainsTable('<?php echo isset($_SESSION['edEmail'])?$_SESSION['edEmail']:""; ?>', '-1', '<?php echo home_url(); ?>'));
    YAHOO.util.Event.onAvailable('expiredDomainsTotalsContainer', EXPIREDDOMAINS.renderTotals());
    </script>
<?php
}

function expired_domains_admin_action_javascript(){
    global $userID;
    global $memberID;
?>
    <style>
    </style>
  <link rel="stylesheet" type="text/css" href="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/expireddomains.css">
  <script src="http://yui.yahooapis.com/3.5.1/build/yui/yui-min.js"></script>
    <script type="text/javascript" src="http://yui.yahooapis.com/combo?2.9.0/build/utilities/utilities.js&2.9.0/build/container/container-min.js&2.9.0/build/menu/menu-min.js&2.9.0/build/button/button-min.js&2.9.0/build/slider/slider-min.js&2.9.0/build/colorpicker/colorpicker-min.js&2.9.0/build/json/json-min.js&2.9.0/build/tabview/tabview-min.js&2.9.0/build/calendar/calendar-min.js"></script>
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/calendar/assets/skins/sam/calendar.css">
    <script type="text/javascript" src="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/messaging.js"></script>
    <script src="http://yui.yahooapis.com/2.9.0/build/calendar/calendar-min.js"></script>
    <script type="text/javascript" src="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/expireddomains.js"></script>
    <script type="text/javascript" src="<?php echo home_url(); ?>/wp-content/plugins/expireddomains/gsdom.js"></script>
    <script type="text/javascript">
    YAHOO.util.Event.onAvailable('expiredDomainsTableContainer', EXPIREDDOMAINS.renderExpiredDomainsTable('<?php echo $memberID; ?>'), '8', '<?php echo home_url(); ?>');

    YAHOO.util.Event.onAvailable('wpexpireddomains_import_button', EXPIREDDOMAINS.admin('<?php echo home_url(); ?>/wp-content/plugins/expireddomains/'));
   </script>
   <?php
}



function wpexpireddomains_addExpiredDomainRow($domain){
    global $wpdb;
    $domainSafe = mysql_real_escape_string($domain);
    $domainDetails = wpexpireddomains_getDomainDetails($domain);
//    if(strtotime($domainDetails['last_checked'])>strtotime("yesterday")){
    if(1){
       // Cached results
       $yahooBacklinks = $domainDetails['no_yahoo_backlinks']==null?wpexpireddomains_getBacklinks('yahoo', $domain):$domainDetails['no_yahoo_backlinks'];
       $yahooBacklinks = $yahooBacklinks==null?0:$yahooBacklinks;
       $PR = $domainDetails['PR']==null?wpexpireddomains_wpGetPageRank($domain, $domainDetails):$domainDetails['PR'];
       if($domainDetails['createdMonth']==null || $domainDetails['createdYear']==null || $domainDetails['createdYear']=='1970' || $domainDetails['createdYear']==0 || $domainDetails['expires']==null){
           $whois = wpexpireddomains_getWhoIs($domain, $domainDetails);
       }
       else{
           $whois = array('createdMonth'=>$domainDetails['createdMonth'], 'createdYear'=>$domainDetails['createdYear'], 'expiryDate'=>$domainDetails['expires']);
       }
       if($domainDetails['valid_pr']==null || $domainDetails['no_google_backlinks']==null){
            $rankData = wpexpireddomains_getRankData($domain, $domainDetails);
	    $rankData['valid'] = 1;
       }  
       else{
           $rankData = array('valid'=>$domainDetails['valid_pr'], 'noGoogleBacklinks'=>$domainDetails['no_google_backlinks']);
       }
    }
    else{
       $yahooBacklinks = wpexpireddomains_getBacklinks('yahoo', $domain);
       $yahooBacklinks = $yahooBacklinks==null?0:$yahooBacklinks;
       $PR = wpexpireddomains_wpGetPageRank($domain, $domainDetails);
       $whois = wpexpireddomains_getWhoIs($domain, $domainDetails);
       $rankData = wpexpireddomains_getRankData($domain, $domainDetails);
       $rankData = array('valid'=>$domainDetails['valid_pr'], 'noGoogleBacklinks'=>$domainDetails['no_google_backlinks']);
    }
    $isGoDaddy = wpexpireddomains_getIsGoDaddy($domain)?'1':'0';

    $pst_time = strtotime(date("Y-m-d H:i:s"));
    if(!empty($domainDetails['auction_end_time'])){
        $auctionEndTime = $domainDetails['auction_end_time'];
        $estimatedTimeBeforeExpiration = strtotime($auctionEndTime)<$pst_time?0:strtotime($auctionEndTime)-$pst_time;
        $domainDetails['time_left'] = wpexpireddomains_sec2Time($estimatedTimeBeforeExpiration, true);
    }
    elseif(!empty($domainDetails['last_checked'])&&!empty($domainDetails['time_left'])){
        $cachedTimeLeftSecs = wpexpireddomains_time2Secs($domainDetails['time_left']);
        $lastCheckedSecs = strtotime($domainDetails['last_checked']);
        $secsSinceLastChecked = $pst_time - $lastCheckedSecs;
        $timeLeftSecs = $cachedTimeLeftSecs - $secsSinceLastChecked;
        $domainDetails['time_left'] = wpexpireddomains_sec2Time($timeLeftSecs, false);
    }

    return array('domain'=>$domain, 'createdMonth'=>$whois['createdMonth'], 'createdYear'=>$whois['createdYear'], 'expiryDate'=>$whois['expiryDate'], 'PR'=>$PR, 'noGoogleBacklinks'=>$rankData['noGoogleBacklinks'], 'yahooBacklinks'=>$yahooBacklinks, 'valid'=>$rankData['valid'], 'type'=>$domainDetails['type'], 'googleIndex'=>$domainDetails['google_index'], 'godaddyTraffic'=>$domainDetails['godaddy_traffic'], 'age'=>$domainDetails['age'], 'time_left'=>'<a href="'.$domainDetails['auction_link'].'">'.$domainDetails['time_left'].'</a>', 'is_godaddy'=>$isGoDaddy, 'guid'=>isset($domainDetails['guid'])?$domainDetails['guid']:''); 

}

function wpexpireddomains_time2secs($time){
    preg_match("/([0-9]*)D/i", $time, $matches);
    $days = isset($matches[1])?$matches[1]:0;
    preg_match("/([0-9]*)H/i", $time, $matches);
    $hours = isset($matches[1])?$matches[1]:0;
    preg_match("/([0-9]*)M\s*/i", $time, $matches);
    $minutes = isset($matches[1])?$matches[1]:0;
    return ($days*60*60*24)+($hours*60*60)+($minutes*60);
}

function wpexpireddomains_getIsGoDaddy($domain){
    $domainSafe = mysql_real_escape_string($domain);
    $sql = "SELECT `domain_name` FROM `ed_domainnames` WHERE `domain_name`='$domainSafe' AND `from_godaddy`='1'";
    $res = mysql_query($sql);
    return mysql_num_rows($res)>0;
}

function wpexpireddomains_adjustTimeLeft($timeLeft){
 // 8D 15
    preg_match("/(.*?[0-9]*)H.*?/", $timeLeft, $matches);
    $timeLeft = str_replace($matches[1], $matches[1]<7?1:$matches[1]-6,$timeLeft);
    return $timeLeft;
}

function wpexpireddomains_storePreferences($settings, $edEmail=null){
    global $wpdb;
    @session_start();
    $settings['lastSent'] = time();
    $emailSafe = mysql_real_escape_string(empty($edEmail)?$_SESSION['edEmail']:$edEmail);
    $searchPreferencesSafe = mysql_real_escape_string(serialize($settings));
    $sql = "UPDATE `ed_users` SET `search_preferences`='$searchPreferencesSafe' WHERE `email`='$emailSafe'";
    $wpdb->query($sql);
    echo mysql_error();
}

function wpexpireddomains_checkStatus($domain){
/*
"Status" column. This column will have a link labeled "Check Now".
When clicked, Web Domaination will access the GoDaddy API. Upon
checking, this link will change to "Available" or "Taken". When
clicked again, Web Domaination will again check using GoDaddy's API
and display the real time status...
*/
   $domainSafe = mysql_real_escape_string($domain);
   $now = date("Y-m-d H:i:s");
   $sql = "SELECT `gd_auction_end_time`  FROM `ed_domainnames` WHERE `domain_name`='$domainSafe' AND `gd_auction_end_time`>'$now'";
   $res = mysql_query($sql);

   $status = "Not known";
   if(mysql_num_rows($res)>0){
	$row = mysql_fetch_assoc($res);
	$status = $row['gd_auction_end_time']>date("Y-m-d H:i:s")?'Available':'Taken';
   }
   return array('status'=>$status);
}

function wpexpireddomains_checkPrice($domain){
/*
"Price" column. A clickable link will be in this column labeled
"Check Now". When clicked, Web Domaination will check using GoDaddy's
API then display price as clickable link. When clicked again, Web
Domaination will check the price of the domain again...
*/
   $domainSafe = mysql_real_escape_string($domain);
   $sql = "SELECT `gd_asking_price_current_bid` FROM `ed_domainnames` WHERE `domain_name`='$domainSafe'";
   $res = mysql_query($sql);
   $price = "-1";
   if(mysql_num_rows($res)>0){
	$row = mysql_fetch_assoc($res);
	$price = trim($row['gd_asking_price_current_bid'],'$');
   }
   return array('price'=>$price);
}

function wpexpireddomains_checkEstimatedTimeBeforeExpiration($domain){
/*
"Estimated Time Before Expiration" column (for domains that are
expiring and for domains in auction". This will have a clickable link
labeled "Check Now". When clicked, Web Domaination will check using
GoDaddy's API and display the estimated time before expiration or
auction closeout as clickable link. When clicked again, Web
Domaination will again check using GoDaddy's API and display the real
time results...
*/
   $domainSafe = mysql_real_escape_string($domain);
   $now = date("Y-m-d H:i:s");
   $sql = "SELECT `gd_auction_end_time` FROM `ed_domainnames` WHERE `domain_name`='$domainSafe' AND `gd_auction_end_time`>'$now'";
   $res = mysql_query($sql);
   $auction_end_time = "-1";
   if(mysql_num_rows($res)>0){
	$row = mysql_fetch_assoc($res);
	$auctionEndTime = $row['gd_auction_end_time'];
        $estimatedTimeBeforeExpiration = strtotime($auctionEndTime)<time()?0:strtotime($auctionEndTime)-time();
        $estimatedTimeBeforeExpiration = wpexpireddomains_sec2Time($estimatedTimeBeforeExpiration);
   }
   else{
	$auctionEndTime = "2012-02-05 06:00:00"; // testing
        $estimatedTimeBeforeExpiration = strtotime($auctionEndTime)<time()?0:(time()-strtotime($auctionEndTime))*-1;
        $estimatedTimeBeforeExpiration = wpexpireddomains_sec2Time($estimatedTimeBeforeExpiration);
        $estimatedTimeBeforeExpiration = "Not known";
   }

   return array('estimatedTimeBeforeExpiration'=>$estimatedTimeBeforeExpiration);
}

function wpexpireddomains_sec2Time($time, $adjust=true){
   if($adjust){
       $time+=60*60*8;
   }
   $timeStr = '';
   // Days
   $dayInSeconds = 60*60*24;
   $days = floor($time / $dayInSeconds);
   $timeStr.="$days".'D';
   $time-=$dayInSeconds*$days;
   // Hours
   $hoursInSeconds = 60*60;
   $hours = floor($time / $hoursInSeconds);
   $timeStr.=" $hours".'H';
   $time-=$hoursInSeconds*$hours;
   // Minutes
   $minutesInSeconds = 60;
   $minutes = floor($time / $minutesInSeconds);
   $timeStr.=" $minutes".'M';
   $time-=$minutesInSeconds*$minutes;
   return $timeStr;
}

if(isset($_GET['run'])){
    switch($_GET['run']){
        case 'time2Secs':
	     echo wpexpireddomains_time2Secs("0D 8H");
	     break;
        case 'guid':
	     echo wpexpireddomains_getRemoteFileCurl('https://auctions.godaddy.com/trpItemListing.aspx?miid=63859290', 'GET', array());
	     break;
        case 'import_domains':
        case 'import_xml':
	  $xmls = explode(",", isset($_POST['xml'])?$_POST['xml']:$_GET['xml']);
	foreach ($xmls as $xml){
	  if(!empty($xml)){
	      echo wpexpireddomains_downloadGodaddyZip($xml);
	  }
	  }
	  break;
        case 'five_letter_auctions':
             echo wpexpireddomains_five_letter_auctions();
              break;
        case 'auction_end_tomorrow':
             echo wpexpireddomains_auction_end_tomorrow();
              break;
        case 'traffic200':
             echo wpexpireddomains_traffic200();
              break;
        case 'traffic':
             echo wpexpireddomains_traffic();
              break;
        case 'closeouts':
             echo wpexpireddomains_closeouts();
              break;
        case 'bidding_service_auctions':
             echo wpexpireddomains_bidding_service_auctions();
              break;
        case 'tdnam_all_listings3':
             echo wpexpireddomains_tdnam_all_listings3();
              break;
        case 'tdnam_all_listings2':
             echo wpexpireddomains_tdnam_all_listings2();
              break;
        case 'tdnam_all_listings':
             echo wpexpireddomains_tdnam_all_listings();
              break;
        case 'getTotals':
	    echo json_encode(wpexpireddomains_getTotals());
	    break;
        case 'downloadGodaddyFiles':
	    echo wpexpireddomains_downloadGodaddyFiles();
	    break;
        case 'checkEstimatedTimeBeforeExpiration':
            echo json_encode(wpexpireddomains_checkEstimatedTimeBeforeExpiration($_POST['domain']));
            break;
        case 'checkPrice':
            echo json_encode(wpexpireddomains_checkPrice($_POST['domain']));
            break;
        case 'checkStatus':
            echo json_encode(wpexpireddomains_checkStatus($_POST['domain']));
            break;
        case 'cron_wi':
            echo cron_wi();
            break;
        case 'cron_sd':
            echo cron_sd();
            break;
        case 'addExpiredDomainRow':
            echo json_encode(wpexpireddomains_addExpiredDomainRow($_POST['domain']));
            break;
        case 'getValidPageRank':
            if(empty($_POST['domain'])){
                  // echo "http://alllimoservices.com";
                   $_POST['domain'] = "http://alllimoservices.com";
             }
	    $rankData = json_encode(wpexpireddomains_getRankData($_POST['domain'], null));
	    echo $rankData;
            break;
        case 'loadDomainsFromCSV':
            echo loadDomainsFromCSV();
	    break;
        case 'getExpiredDomains':
	  echo json_encode(wpexpireddomains_getExpiredDomains($_GET['memberID'], $_GET['from'], $_GET['noRecords'], $_GET['search'], $_GET['col'], isset($_GET['sortDesc'])?$_GET['sortDesc']:1, isset($_GET['filterCols'])?explode(",", $_GET['filterCols']):array('type', 'type', 'type', 'type', 'PR', 'PR'), isset($_GET['filters'])?explode(",", $_GET['filters']):null, isset($_GET['operators'])?explode(",", $_GET['operators']):null));
            break;
        case 'storePreferences':
            $_POST['filterCols'] = isset($_POST['filterCols'])?explode(",", $_POST['filterCols']):array('type', 'type', 'PR', 'PR');
            $_POST['filters'] = isset($_POST['filters'])?explode(",", $_POST['filters']):null;
            $_POST['operators'] = isset($_POST['operators'])?explode(",", $_POST['operators']):null;
	    echo wpexpireddomains_storePreferences($_POST);
            break;
        case 'getWhoIs':
	    echo json_encode(wpexpireddomains_getWhoIs($_POST['domain']));
            break;
        case 'getPageRank':
	    echo wpexpireddomains_wpGetPageRank($_POST['domain']);
            break;
        case 'getBacklinks':
	    echo wpexpireddomains_getBacklinks($_GET['se'], $_POST['domain']);
            break;
     }
    die();
}

add_action('wp_head', 'expired_domains_action_javascript');

add_action('admin_enqueue_scripts', 'expired_domains_admin_action_javascript');

function wpexpireddomains_getRemoteFileCurl($url, $method, $postFields, $referrer="", $userAgent="", $username=null, $password=null){

   // create a new cURL resource
   $ch = curl_init();

   // set URL and other appropriate options
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_COOKIEFILE, plugin_dir_path(__FILE__).'cookies/cookie.txt');
   curl_setopt($ch, CURLOPT_COOKIEJAR, plugin_dir_path(__FILE__).'cookies/cookie.txt');
   //curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Expect: ')); // Stop 417 errors
   
   if(!empty($referrer)){
      curl_setopt($ch, CURLOPT_REFERER, $referrer);
   }
   
   if(!empty($userAgent)){
   	  curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
   }
   
   if(!empty($username)){
   	  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");   
   }
   
   if(strtoupper($method)=='POST'){
   	  curl_setopt($ch, CURLOPT_POST, 1);
      if(!empty($postFields)){
         curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
      }
   }
   
   // grab URL and pass it to the browser
   $content = curl_exec($ch);

   // close cURL resource, and free up system resources
   curl_close($ch);

   return $content;
  
}

function wpexpireddomains_ftpConnect($ftp_server, $ftp_user_name, $ftp_user_pass){
    $conn_id = ftp_connect($ftp_server); 
    if(!$conn_id){
         echo "ftp connect failed $ftp_server";
         return false;
    }		
    // login with username and password
    if(empty($ftp_user_name)){
         echo "no ftp username";
         return false;
    }
    @$login_result = ftp_login($conn_id, trim($ftp_user_name), trim($ftp_user_pass)); 
    // check connection
    if (!$login_result) { 
        $ftp_user_pass = urldecode($ftp_user_pass);
        @$login_result = ftp_login($conn_id, trim($ftp_user_name), trim($ftp_user_pass)); 
        if (!$login_result) { 
           echo "failed to login $ftp_server, $ftp_user_name, $ftp_user_pass";
           return false;
        }
    }
    return $conn_id;
}

function wpexpireddomains_ftpVerify($ftp_server, $ftp_user_name, $ftp_user_pass){
    @$conn_id = ftp_connect($ftp_server); 
    if(!$conn_id){
         return -1;
    }		
			
    // login with username and password
    if(empty($ftp_user_name)){
         return -2;
    }

    @$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
    // check connection
    if (!$login_result) { 
        $ftp_user_pass = urldecode($ftp_user_pass);
   	$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
        if (!$login_result) { 
           return -2;
        }
    }

    return 1;
  
}

function wpexpireddomains_traffic200(){
    wpexpireddomains_downloadGodaddyZip('traffic200.xml.zip');
}

function wpexpireddomains_five_letter_auctions(){
    wpexpireddomains_downloadGodaddyZip('5_letter_auctions.xml.zip');
}

function wpexpireddomains_auction_end_tomorrow(){
    wpexpireddomains_downloadGodaddyZip('auction_end_tomorrow.xml.zip');
}

function wpexpireddomains_traffic(){
    wpexpireddomains_downloadGodaddyZip('traffic.xml.zip');
}

function wpexpireddomains_closeouts(){
    wpexpireddomains_downloadGodaddyZip('closeouts.xml.zip');
}

function wpexpireddomains_bidding_service_auctions(){
    wpexpireddomains_downloadGodaddyZip('bidding_service_auctions.xml.zip');
}

function wpexpireddomains_tdnam_all_listings3(){
    wpexpireddomains_downloadGodaddyZip('tdnam_all_listings3.xml.zip');
}   

function wpexpireddomains_tdnam_all_listings2(){
    downloadGodaddyZip('tdnam_all_listings2.xml.zip');
}   

function wpexpireddomains_tdnam_all_listings(){
    wpexpireddomains_downloadGodaddyZip('tdnam_all_listings.xml.zip');
}   

function wpexpireddomains_downloadGodaddyFiles(){
    wpexpireddomains_downloadGodaddyZip('auction_end_today.xml.zip');
}

function wpexpireddomains_downloadGodaddyZip($zipFile, $tryAgain=true){
    @unlink(plugin_dir_path(__FILE__ ).'uploads/'.$zipFile);
    // auction_end_today.xml.zip
    // ftp://auctions@ftp.godaddy.com
    //    echo $zipFile;
    //    die();
    // 5_letter_auctions.xml.zip
    // 5_letter_auctions.xml.zip
    wpexpireddomains_ftpDownload(null, 'ftp.godaddy.com', 'auctions', '', plugin_dir_path(__FILE__ ).'uploads/'.$zipFile, $zipFile);
    if(!file_exists(plugin_dir_path(__FILE__ ).'uploads/'.$zipFile)){
        if($tryAgain){
           wpexpireddomains_downloadGodaddyZip($zipFile, false);
        }
    }
    else{
        wpexpireddomains_unzipFile(plugin_dir_path(__FILE__ ).'uploads/'.$zipFile, plugin_dir_path(__FILE__ ).'uploads/', $zipFile);
        // wpexpireddomains_parseGodaddyXML(plugin_dir_path(__FILE__ ).'uploads/auction_end_today.xml'); // unzipFile also parses data into the database so need to call this 
        unlink(plugin_dir_path(__FILE__ ).'uploads/'.$zipFile);
    }
    
}

function wpexpireddomains_ftpDownload($conn_id, $ftp_server, $ftp_user_name, $ftp_user_pass, $local_file, $server_file){

	$ret = false;

	if(!is_writable(dirname($local_file))){
	  echo "Directory is not writable: ".$dirname($local_file);
	  die();
	}

	$closeConnection = empty($conn_id);
	
	if(!empty($ftp_server) || !empty($conn_id)){

		if(empty($conn_id)){
			// set up basic connection
			$conn_id = ftp_connect($ftp_server); 
			if(!$conn_id){
				trigger_error("Could not connect to server $ftp_server. Please check that this server is valid.", E_USER_NOTICE);
				return $errors[] = "Could not connect to server $ftp_server. Please check that this server is valid.";
			}		
			
			// login with username and password
			$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
			// check connection
			if ((!$conn_id) || (!$login_result)) { 
                            $ftp_user_pass = urldecode($ftp_user_pass);
   	                    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass); 
			    if ((!$conn_id) || (!$login_result)) { 
		                  echo ("Could not connect to server $ftp_server. Please check that this server is valid.");
   		                  echo("Attempted to connect to $ftp_server for user $ftp_user_name");
				  die();
                           }
		        } else {
		        //echo "Connected to $ftp_server, for user $ftp_user_name";
		    }
		}
		// FTP_BINARY FTP_ASCII
		ftp_pasv($conn_id, true);
		/*
Successfully written to projects/wpexpireddomains/wp-content/plugins/expireddomains/uploads/5_letter_auctions.xml.zip
Trying to unzip ...Sucessfully unzipped the file ...
		 */
                if ($res = ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) {
                } 
		else {
                    echo "There was a problem\n $conn_id, $local_file, $server_file";
		    die();
                }
	    		    
		if($closeConnection){
			// close the FTP stream 
			ftp_close($conn_id);
		}
		
	}

	return $ret;
	
}

function wpexpireddomains_unzipFile($fullFilename, $zipDir, $zipFile){
   $zip = zip_open($fullFilename);
   if (!$zip) {
       echo "There was a problem unzipping the file";
       unlink(plugin_dir_path(__FILE__ ).'uploads/'.$zipFile);
       wpexpireddomains_downloadGodaddyZip($zipFile, false);
   }
   else{
       // get zip entry
       $unparsedData = '';
       while ($zip_entry = zip_read($zip)) {
            // open zip entry
            if(zip_entry_open($zip, $zip_entry, "r")) {
	       $continue = true;
	       while($continue){
	           $data = zip_entry_read($zip_entry, 1024);                         
		   if(!$data){
		      $continue = false;
		   }
		   else{
                      $unparsedData .= $data;
                      $unparsedData = wpexpireddomains_parseGodaddyXMLEntry($unparsedData);
   	              zip_entry_close($zip_entry);
                   }
               }
           }
       }
       zip_close($zip);
   }
   return 1;
}

function wpexpireddomains_parseGodaddyXMLEntry($unparsedData){
  /*
<item><title>XAIPO.COM</title><link><![CDATA[https://auctions.godaddy.com/trpItemListing.aspx?miid=94733798&isc=rssTD01]]></link><description><![CDATA[Auction Type: BuyNow, Auction End Time: 01/22/2013 08:00 AM (PST), Price: $9, Number of Bids: 0, Domain Age: 2, Description: , Traffic: 2, Valuation: $0, IsAdult: false]]></description><guid><![CDATA[https://auctions.godaddy.com/trpItemListing.aspx?miid=94733798]]></guid></item>
   */
        preg_match("/\<item\>\<title\>(.*?)\<\/title\>\<link\>(.*?)\<\/link\>\<description\>(.*?)\<\/description\>\<guid\>(.*?)\<\/guid\>\<\/item\>/i", $unparsedData, $matches);
          if(!empty($matches)){
	     $unparsedData = ""; // **** need to removed data only up to the last </item> tag
	     $title = strtolower($matches[1]);
	     if(strtolower($title)!="auctions ending today"){
	        $link = str_replace(array('<![CDATA[', ']]>'), array('',''), $matches[2]);
	        $description = str_replace(array('<![CDATA[', ']]>'), array('',''), $matches[3]);
	        $guid = str_replace(array('<![CDATA[', ']]>'), array('',''), $matches[4]);
                $keyValues = explode(",", $description);
	        while(list($i, $kv)=each($keyValues)){
	           $temp  =  explode(":", $kv);
		   $propName = $temp[0];
		   unset($temp[0]);
		   $propVal = implode(":", $temp);
	           $propName = strtolower(str_replace(array(" ", "/"), array("_", "_"), trim($propName)));		    
		   $$propName = $propVal;
                }
		global $wpdb;
		$domainSafe = mysql_real_escape_string($title);
		$auctionLinkSafe = mysql_real_escape_string($link);
		$auctionTypeSafe = trim(mysql_real_escape_string($auction_type));
		$auctionEndTimeSafe = date("Y-m-d H:i:s", strtotime(str_replace(" (PST)", "", mysql_real_escape_string($auction_end_time))));
		$askingPriceCurrentBidSafe = mysql_real_escape_string($asking_price_current_bid);
                $numberOfBidsSafe = mysql_real_escape_string($number_of_bids);
		$domainAgeSafe = mysql_real_escape_string($domain_age);
		$trafficSafe = mysql_real_escape_string($traffic);
		$valuationSafe = mysql_real_escape_string($valuation);
		$isAdultSafe = $is_adult=='true';
		$guidSafe = mysql_real_escape_string($guid);
		$descriptionSafe = mysql_real_escape_string($description);
		$sql = "INSERT INTO `ed_domainnames` (`from_godaddy`, `type`, `domain_name`, `gd_auction_link`, `gd_auction_type`, `gd_auction_end_time`, `gd_asking_price_current_bid`, `gd_number_of_bids`, `gd_domain_age`, `gd_traffic`, `gd_valuation`, `gd_is_adult`, `gd_guid`, `gd_description`) VALUES ('1', '$auctionTypeSafe',  '$domainSafe', '$auctionLinkSafe', '$auctionTypeSafe', '$auctionEndTimeSafe', '$askingPriceCurrentBidSafe', '$numberOfBidsSafe', '$domainAgeSafe', '$trafficSafe', '$valuationSafe', '$isAdultSafe', '$guidSafe', '$descriptionSafe') ON DUPLICATE KEY UPDATE `from_godaddy`='1', `gd_auction_link`='$auctionLinkSafe' , `gd_auction_end_time`='$auctionEndTimeSafe', `gd_asking_price_current_bid`='$askingPriceCurrentBidSafe', `gd_number_of_bids`='$numberOfBidsSafe', `gd_domain_age`='$domainAgeSafe', `gd_traffic`='$trafficSafe', `gd_valuation`='$valuationSafe', `gd_is_adult`='$isAdultSafe', `gd_guid`='$guidSafe', `gd_description`='$descriptionSafe
';";
		$wpdb->query($sql);
		echo mysql_error();
	        //     [3] => <![CDATA[Auction Type: Bid, Auction End Time: 02/01/2012 06:00 AM (PST), Asking Price/Current Bid: $10, Number of Bids: 0, Domain Age: 0, Description: , Traffic: 0, Valuation: $0, IsAdult: false]]> 
	     }
          }
          return $unparsedData;
  
}

function wpexpireddomains_parseGodaddyXML($file){
   $handle = fopen($file, 'r');
   if(!$handle){
       return false;
   }
   else{
       $chunksize = 1024;
       $i = 0;
       $unparsedData = '';
       while ( !feof( $handle ) && $i<100) { 
          $unparsedData = wpexpireddomains_parseGodaddyXMLEntry($unparsedData);
          $unparsedData .= fread( $handle, $chunksize );
       } 
   }
}

function wpexpireddomains_getTotals(){
  return array('totalBidDomains'=>wpexpireddomains_getTotalBidDomains(), 'totalBuyNowDomains'=>wpexpireddomains_getTotalBuyNowDomains(), 'totalExpiredCloseoutDomains'=>wpexpireddomains_getTotalExpiredCloseoutDomains(), 'totalExpiringPublicDomains'=>wpexpireddomains_getTotalExpiringPublicDomains(), 'totalExpiringPrivateDomains'=>wpexpireddomains_getTotalExpiringPublicDomains(), 'dateOfLastUpdate'=>wpexpireddomains_getDateOfLastUpdate());
}

function wpexpireddomains_getTotalBuyNowDomains(){
    global $wpdb;
    $now = date("Y-m-d H:i:s");
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalBuyNowDomains` FROM `ed_domainnames` WHERE `type`='BuyNow' and (`time_left`='0H' || `time_left`='0M' || (`gd_auction_end_time` IS NOT NULL AND `gd_auction_end_time`>'$now'))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalBuyNowDomains` FROM `ed_domainnames` WHERE `type`='BuyNow' and (`time_left`='0H' || `time_left`='0M' || (`gd_auction_end_time` IS NOT NULL AND `gd_auction_end_time`>'$now'))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalBuyNowDomains'];
}

function wpexpireddomains_getTotalBidDomains(){
    global $wpdb;
    $now = date("Y-m-d H:i:s");
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalBidDomains` FROM `ed_domainnames` WHERE `type`='Bid' and (`time_left`='0H' || `time_left`='0M' || (`gd_auction_end_time` IS NOT NULL AND `gd_auction_end_time`>'$now'))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalBidDomains` FROM `ed_domainnames` WHERE `type`='Bid' and (`time_left`='0H' || `time_left`='0M' || (`gd_auction_end_time` IS NOT NULL AND `gd_auction_end_time`>'$now'))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalBidDomains'];
}

function wpexpireddomains_getTotalExpiredCloseoutDomains(){
    global $wpdb;
    $now = date("Y-m-d H:i:s");
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiredClosoutDomains` FROM `ed_domainnames` WHERE `type`='Closeouts' and (`time_left`='0H' || `time_left`='0M' || (`expiry_date` IS NOT NULL AND `expiry_date`<'$now'))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiredClosoutDomains` FROM `ed_domainnames` WHERE `type`='Closeouts' and (`time_left`='0H' || `time_left`='0M' || (`expiry_date` IS NOT NULL AND `expiry_date`<'$now'))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiredClosoutDomains'];
}

function wpexpireddomains_getTotalExpiringPublicDomains(){
    global $wpdb;
    $now = date("Y-m-d H:i:s");
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiringPublicDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>'$now'))";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiringPublicDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>'$now'))";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiringPublicDomains'];
}

function wpexpireddomains_getTotalExpiringPrivateDomains(){
    global $wpdb;
    $now = date("Y-m-d H:i:s");
    if(GODADDY_ONLY){
        $sql = "SELECT count(`domain_name`) as `totalExpiringPrivateDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>'$now')";
    }
    else{
        $sql = "SELECT count(`domain_name`) as `totalExpiringPrivateDomains` FROM `ed_domainnames` WHERE `type`='Expiring' and ((`time_left`<>'0H' AND `time_left` <>'0M') OR (`expiry_date` IS NOT NULL AND `expiry_date`>'$now')";
    }
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['totalExpiringPrivateDomains'];
}

function wpexpireddomains_getDateOfLastUpdate(){
    global $wpdb;
    $sql = "SELECT `last_checked` FROM `ed_domainnames` ORDER BY `last_checked` DESC LIMIT 0,1";
    $res = mysql_query($sql);
    echo mysql_error();
    $row = mysql_fetch_assoc($res);
    return $row['last_checked'];  
}


function wpexpireddomains_plugin_menu(){
  if (current_user_can('manage_options')) {
     add_menu_page('WP Expired Domains', "WP Expired Domains", 'publish_posts', 'wpexpireddomains_menu_setup', "wpexpireddomains_about");    
     add_submenu_page('wpexpireddomains_menu_setup', 'Importing', 'Importing', 'publish_posts', "wpexpireddomains_manage_importing", "wpexpireddomains_manage_importing" ); 
     add_submenu_page('wpexpireddomains_menu_setup', 'Shortcodes', 'Shortcodes', 'publish_posts', "wpexpireddomains_shortcodes", "wpexpireddomains_shortcodes" ); 
  }
}

function wpexpireddomains_about(){
  ?>
    <div class="wrap">
       <?php screen_icon('plugins'); ?>
       <h2>WP Expired Domains</h2>
about
    </div>
<?php
}

function wpexpireddomains_shortcodes(){
  ?>
    <div class="wrap">
       <?php screen_icon('plugins'); ?>
       <h2>WP Expired Domains</h2>
       <h3>Shortcodes</h3>
<form>
   <fieldset>
       <ul>
          <li class="row">
              <h4>[wpexp_domains_table]</h4>
              <p>To display a domains table add [wpexp_domains_table] where you want the domains table to appear.</p>
          </li>
          <li class="row">
              <h4>[wpexp_domains_totalstable]</h4>
              <p>To display a domains totals table add [wpexp_domains_totals_table] where you want the domains totals table to appear.</p>
          </li>
       </ul>
   </fieldset>
</form>
    </div>
<?php
}

function wpexpireddomains_manage_importing(){
  if(isset($_POST['update']) && $_POST['update']=="Y"){
    wpexpireddomains_plugin_setOptions($_POST);
  }
  ?>
    <div class="wrap">
       <div id="wpExpiredDomainsContainer">
       <?php screen_icon('plugins'); ?>
       <h2>WP Expired Domains</h2>
    <form id="frmWPExpiredDomains" method="post" name="frmWPExpiredDomainsPlugin" action=""  enctype="multipart/form-data" >   
       <fieldset>
          <input type="hidden" name="update" value="Y"/>
          <h3>Import</h3>
	  <ul class="wpexpireddomains_import_list">
	          <li class="row">
	             <label for="">5 letter auctions</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="5_letter_auctions.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">end today</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="auction_end_today.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">end tomorrow</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="auction_end_tomorrow.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>

	          <li class="row">
	             <label for="">bidding service</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="bidding_service_auctions.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">bidding service no adult</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="bidding_service_no_adult_auctions.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">closeouts</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="closeouts.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">closeouts 200</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="closeouts200.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">feature</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="featured_feed.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">most active</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="most_active_feed.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">most active 200</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="most_active_feed200.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">recent</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="recent_feed.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">recent all</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="recent_feed_all.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_listings.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all 2</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_listings2.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all 3</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_listings3.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all no adult</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_no_adult_listings.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all no adult 2</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_no_adult_listings2.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">tdnam all no adult 3</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="tdnam_all_no_adult_listings3.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row">
	             <label for="">traffic</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="traffic.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row" style="width:100% !important">
	             <label for="">traffic200</label>
		     <div>
				     <input class="wpexpireddomains_auction_xml_checkbox"  type="checkbox" xml="traffic200.xml.zip" />
                     </div>
                     <div class="help_text">
                     </div>						     
                  </li>
	          <li class="row" style="clear:both !important">
				     <input class="button-primary" type="button" value="Import selected" id="wpexpireddomains_import_button"/>
		  </li>
	  </ul>
	  <p id="cron_command_container"><b>Cron command</b><br/><textarea id="wpexpireddomains_import_xml_cron" rows="5" cols="80"><?php echo site_url(); ?>?run=import_domains&xml=</textarea></p>
			  
       </fieldset>
     </form>	
    </div>
  </div>
<?php
}

function wpexpireddomains_plugin_setOptions($options){
}


add_action('admin_menu', 'wpexpireddomains_plugin_menu');

function wpexpireddomains_expiredDomainsTable_sc(){
  return "<div id=\"expiredDomainsTableContainer\"></div>";
}

add_shortcode('wpexp_domains_table', 'expiredDomainsTable_sc');

function wpexpireddomains_expiredDomainsTotalsTable_sc(){
  return "<div id=\"expiredDomainsTableContainer\"></div>";
}

add_shortcode('wpexp_domains_totals_table', 'wpexpireddomains_expiredDomainsTotalsTable_sc');
add_shortcode('wpexp_domains_table', 'wpexpireddomains_expiredDomainsTable_sc');

?>