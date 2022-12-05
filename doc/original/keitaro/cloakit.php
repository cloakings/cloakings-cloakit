<?php
namespace Filters;

use Core\Filter\AbstractFilter;
use Core\Locale\LocaleService;
use Traffic\Model\StreamFilter;
use Traffic\RawClick;

/**
 * Filter Cloakit
 */
class cloakit extends AbstractFilter
{
    public function getModes()
    {
        return [
            StreamFilter::ACCEPT => LocaleService::t('filters.binary_options.' . StreamFilter::ACCEPT),
            StreamFilter::REJECT => LocaleService::t('filters.binary_options.' . StreamFilter::REJECT),
        ];
    }
    /**
     * Filter settings template
     */
    public function getTemplate()
    {
        return '';
        //return '<div><span>Campaign ID</span><input class="form-control" ng-model="filter.payload" /></div>';
    }

    /**
     * Check if $rawClick passes the filter (true - passed, false - failed)
     */
    public function isPass(StreamFilter $filter, RawClick $rawClick)
    {
        $campaignId = $filter->getPayload();
        // or override it with
        //$campaignId = 'XXX';

        $getParams = $this->getServerRequest()->getQueryParams();
        $serverObj = $this->getServerRequest()->getServerParams();
        error_reporting(0);
        $data = array(
            'companyId' => $campaignId,
            'referrerCF' => $getParams["referrerCF"],
            'urlCF' => $getParams["urlCF"],
            'QUERY_STRING' => $serverObj["QUERY_STRING"],
            'HTTP_REFERER' => $serverObj["HTTP_REFERER"],
            'HTTP_USER_AGENT' => $serverObj["HTTP_USER_AGENT"],
            'REMOTE_ADDR' => $serverObj["REMOTE_ADDR"],
            'HTTP_CF_CONNECTING_IP' => $serverObj["HTTP_CF_CONNECTING_IP"],
            'CF_CONNECTING_IP' => $serverObj["CF_CONNECTING_IP"],
            'X_FORWARDED_FOR' => $serverObj["X_FORWARDED_FOR"],
            'TRUE_CLIENT_IP' => $serverObj["TRUE_CLIENT_IP"],
        );
        $curl = curl_init('http://api.clofilter.com/v1/check');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
        $api = json_decode(curl_exec($curl));
        curl_close($curl);
        $arrContextOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false), 'http' => array('header' => 'User-Agent: ' . $serverObj["HTTP_USER_AGENT"]));
        if ($filter->getMode() == StreamFilter::ACCEPT) {
            return !$api->isPassed;
        } else {
            return $api->isPassed;
        }
    }
}
