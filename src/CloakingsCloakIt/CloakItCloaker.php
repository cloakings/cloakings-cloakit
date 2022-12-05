<?php

namespace Cloakings\CloakingsCloakIt;

use Cloakings\CloakingsCommon\CloakerInterface;
use Cloakings\CloakingsCommon\CloakerResult;
use Cloakings\CloakingsCommon\CloakModeEnum;
use Symfony\Component\HttpFoundation\Request;

class CloakItCloaker implements CloakerInterface
{
    public function __construct(
        private readonly string $companyId,
        private readonly CloakItHttpClient $httpClient = new CloakItHttpClient(),
    ) {
    }

    public function handle(Request $request): CloakerResult
    {
        return $this->handleParams($this->collectParams($request));
    }

    private function collectParams(Request $request): array
    {
        return [
            'companyId' => (string)$this->companyId,
            'referrerCF' => (string)$request->query->get('referrerCF', ''),
            'urlCF' => (string)$request->query->get('urlCF', ''),
            'QUERY_STRING' => (string)$request->server->get('QUERY_STRING', ''),
            'HTTP_REFERER' => (string)$request->server->get('HTTP_REFERER', ''),
            'HTTP_USER_AGENT' => (string)$request->server->get('HTTP_USER_AGENT', ''),
            'REMOTE_ADDR' => (string)$request->server->get('REMOTE_ADDR', ''),
            'HTTP_CF_CONNECTING_IP' => (string)$request->server->get('HTTP_CF_CONNECTING_IP', ''),
            'CF_CONNECTING_IP' => (string)$request->server->get('CF_CONNECTING_IP', ''),
            'X_FORWARDED_FOR' => (string)$request->server->get('X_FORWARDED_FOR', ''),
            'TRUE_CLIENT_IP' => (string)$request->server->get('TRUE_CLIENT_IP', ''),
        ];
    }

    public function handleParams(array $params): CloakerResult
    {
        $apiResponse = $this->httpClient->execute($params);

        return $this->createResult($apiResponse);
    }

    private function createResult(CloakItApiResponse $apiResponse): CloakerResult
    {
        return new CloakerResult(
            mode: match (true) {
                $apiResponse->mode === CloakModeEnum::Fake => CloakModeEnum::Fake,
                $apiResponse->mode === CloakModeEnum::Real => CloakModeEnum::Real,
                default => CloakModeEnum::Error,
            },
            apiResponse: $apiResponse,
        );
    }
}
