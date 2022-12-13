<?php

namespace Cloakings\CloakingsCloakIt;

use CurlHandle;
use Gupalo\Json\Json;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CloakItHttpClient
{
    public function __construct(
        private readonly string $apiUrl = 'http://api.clofilter.com/v1/check',
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(array $params): CloakItApiResponse
    {
        $paramsString = Json::toString($params);

        $curl = $this->getCurl($paramsString);
        if (!$curl) {
            return CloakItApiResponse::create([]);
        }

        $result = null;
        $status = 0;
        $time = microtime(true);
        $responseString = curl_exec($curl);
        if ($responseString) {
            try {
                $responseArray = Json::toArray();
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($status === Response::HTTP_OK) {
                    $result = $responseArray;
                }
            } catch (Throwable $e) {
                $this->logger->error('cloaking_request_error', ['service' => 'cloakit', 'status' => $status, 'params' => $params, 'response_string' => $responseString, 'time' => $this->elapsedTime($time), 'exception' => $e]);
            }
        } else {
            $this->logger->error('cloaking_request_empty', ['service' => 'cloakit', 'params' => $params, 'time' => $this->elapsedTime($time)]);
        }

        $this->logger->info('cloaking_request', ['service' => 'cloakit', 'result' => $result, 'params' => $params, 'time' => $this->elapsedTime($time)]);
        curl_close($curl);

        $result = array_merge(
            $result,
            [
                'response_status' => 200,
                'response_headers' => [],
                'response_body' => $responseString,
                'response_time' => $this->elapsedTime($time),
            ],
        );

        return CloakItApiResponse::create($result);
    }

    private function getCurl(string $paramsString): ?CurlHandle
    {
        $curl = curl_init($this->apiUrl);
        if ($curl) {
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl, CURLOPT_TIMEOUT, 3);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, Request::METHOD_POST);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $paramsString);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: '.strlen($paramsString),
            ]);
        } else {
            $this->logger->error('cloaking_request_error', ['service' => 'cloakit', 'reason' => 'no curl']);
            $curl = null;
        }

        return $curl;
    }

    private function elapsedTime(float $startTime): float
    {
        return round(microtime(true) - $startTime, 4);
    }
}
