<?php
declare(strict_types = 1); // must be first line

namespace premiumwebtechnologies\expireddomainscraper;


class Stats
{

    private $url;
    private $pageRank;
    private $siteIndexTotal;
    private $backlinksTotal;
    private $searchResultsTotal;
    private $domainAuthority;
    private $pageAuthority;
    private $trustFlow;

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
    private function setConn()
    {
        $conn = mysqli_connect(DBSERVER, DBUSER, DBPASSWORD, DB);
        if (mysqli_connect_error()) {
            die('Could not connect to database');
        }
        $this->conn = $conn;
    }

    /**
     * Stats constructor.
     * @param $url
     */
    public function __construct(string $url)
    {
        $this->setUrl($url);
        $this->setPageRank();
        $this->setSiteIndexTotal();
        $this->setSearchResultsTotal();
        $this->setBacklinksTotal();
        $this->setDomainAuthority();
        $this->setPageAuthority();
        $this->setTrustFlow();

        $this->setConn();

        $this->_saveStats();

    }

    /**
     * @return mixed
     */
    public function getDomainAuthority()
    {
        return $this->domainAuthority;
    }

    /**
     * @param mixed $domainAuthority
     */
    public function setDomainAuthority()
    {
        // https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
        $temp = $this->_mozillaStats($this->getUrl(), 68719476736);
        $this->domainAuthority = $temp->pda ?? -1;
    }

    /**
     * @return mixed
     */
    public function getPageAuthority()
    {
        return $this->pageAuthority;
    }

    /**
     * @param mixed $pageAuthority
     */
    public function setPageAuthority()
    {
        // https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
        $temp = $this->_mozillaStats($this->getUrl(), 34359738368);
        $this->pageAuthority = $temp->upa ?? -1;
    }

    /**
     * @return mixed
     */
    public function getTrustFlow()
    {
        return $this->trustFlow;
    }

    /**
     * @param mixed $trustFlow
     */
    public function setTrustFlow()
    {
        // https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
        $temp = $this->_mozillaStats($this->getUrl(), 131072);
        $this->trustFlow = $temp->utrp ?? -1;
    }

    public function getAll(): array
    {
        return array(
            'pageRank' => $this->getPageRank(),
            'siteIndexTotal' => $this->getSiteIndexTotal(),
            'searchResultsTotal' => $this->getSearchResultsTotal(),
            'backlinksTotal' => $this->getBacklinksTotal(),
            'domainAuthority' => $this->getDomainAuthority(),
            'pageAuthority' => $this->getPageAuthority(),
            'trustFlow' => $this->getTrustFlow()
        );
    }

    /**
     * @return mixed
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getPageRank(): int
    {
        return $this->pageRank;
    }

    /**
     * @param mixed $pageRank
     */
    public function setPageRank()
    {
        $this->pageRank = \SEOstats\Services\Google::getPageRank($this->getUrl()) * 1;
    }

    /**
     * @return mixed
     */
    public function getSiteIndexTotal(): int
    {
        return $this->siteIndexTotal * 1;
    }

    /**
     * @param mixed $siteIndexTotal
     */
    public function setSiteIndexTotal()
    {
        $this->siteIndexTotal = \SEOstats\Services\Google::getSiteindexTotal($this->getUrl());
    }

    /**
     * @return mixed
     */
    public function getBacklinksTotal(): int
    {
        return $this->backlinksTotal * 1;
    }

    /**
     * @param mixed $backlinksTotal
     */
    public function setBacklinksTotal()
    {
        $this->backlinksTotal = \SEOstats\Services\Google::getBacklinksTotal($this->getUrl());
    }

    /**
     * @return mixed
     */
    public function getSearchResultsTotal(): int
    {
        return $this->searchResultsTotal * 1;
    }

    /**
     * @param mixed $searchResultsTotal
     */
    public function setSearchResultsTotal()
    {
        $this->searchResultsTotal = \SEOstats\Services\Google::getSearchResultsTotal($this->getUrl()) * 1;
    }

    private function _saveStats() : bool
    {
        $domainAuthoritySafe = mysqli_real_escape_string($this->getConn(), (string) $this->getDomainAuthority());
        $pageAuthoritySafe = mysqli_real_escape_string($this->getConn(), (string) $this->getPageAuthority());
        $trustFlowSafe = mysqli_real_escape_string($this->getConn(), (string) $this->getTrustFlow());
        $PRSafe = mysqli_real_escape_string($this->getConn(), (string) $this->getPageRank());
        $urlSafe = mysqli_real_escape_string($this->getConn(),
            str_replace(array('http://', 'HTTP://', 'https://', 'HTTPS://'),
                        array('','','',''),
                        $this->getUrl()
                    )
        );

        $sql = "UPDATE `ed_domainnames` 
                SET `domain_authority` = '$domainAuthoritySafe',
                `page_authority` = '$pageAuthoritySafe',
                `trust_flow` = '$trustFlowSafe',
                `PR` = '$PRSafe'
                WHERE `domain_name` = '$urlSafe'";

        mysqli_query($this->getConn(), $sql);


        return empty(mysqli_error($this->getConn())) ? true : mysqli_error($this->getConn());

    }

    private function _mozillaStats(string $url, int $cols) : \stdClass
    {
        // https://github.com/seomoz/SEOmozAPISamples/blob/master/php/signed_authentication_sample.php

        // Set your expires times for several minutes into the future.
        // An expires time excessively far in the future will not be honored by the Mozscape API.
        $expires = time() + 600;

        // Put each parameter on a new line.
        $stringToSign = MOZ_ACCESS_ID."\n".$expires;

        // Get the "raw" or binary output of the hmac hash.
        $binarySignature = hash_hmac('sha1', $stringToSign, MOZ_SECRET_KEY, true);

        // Base64-encode it and then url-encode that.
        $urlSafeSignature = urlencode(base64_encode($binarySignature));

        $requestUrl = "http://lsapi.seomoz.com/linkscape/url-metrics/".urlencode($url)."?Cols=".$cols."&AccessID=".MOZ_ACCESS_ID."&Expires=".$expires."&Signature=".$urlSafeSignature;

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $requestUrl);
        $statusCode = $res->getStatusCode();
        $contentType = $res->getHeader('content-type');

        return json_decode((string) $res->getBody());
    }
}
