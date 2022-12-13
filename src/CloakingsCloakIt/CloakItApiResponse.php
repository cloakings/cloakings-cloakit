<?php

namespace Cloakings\CloakingsCloakIt;

use Cloakings\CloakingsCommon\CloakerApiResponseInterface;
use Cloakings\CloakingsCommon\CloakModeEnum;

class CloakItApiResponse implements CloakerApiResponseInterface
{
    public function __construct(
        public readonly CloakModeEnum $mode,
        public readonly bool $standardIntegration,
        public readonly CloakItApiResponseTypeEnum $type,
        public readonly string $simplePage,
        public readonly string $redirectQuery,
        public readonly string $pageType,
        public readonly string $pageWithParams,
        public readonly string $originPage,
        public readonly int $responseStatus = 0,
        public readonly array $responseHeaders = [],
        public readonly string $responseBody = '',
        public readonly float $responseTime = 0.0,
    ) {
    }

    /** @noinspection SpellCheckingInspection */
    public static function create(array $a): self
    {
        return new self(
            mode: (bool)($a['isPassed'] ?? false) ? CloakModeEnum::Fake : CloakModeEnum::Real,
            standardIntegration: (bool)($a['standartIntegration'] ?? false), // yes, there is a typo
            type: CloakItApiResponseTypeEnum::tryFrom($a['type'] ?? '') ?? CloakItApiResponseTypeEnum::Unknown,
            simplePage: $a['simplePage'] ?? '',
            redirectQuery: (string)($a['redirectQuery'] ?? ''),
            pageType: $a['pageType'] ?? '',
            pageWithParams: $a['pageWithParams'] ?? '',
            originPage: $a['originPage'] ?? '',
            responseStatus: (int)($a['response_status'] ?? 0),
            responseHeaders: $a['response_headers'] ?? [],
            responseBody: $a['response_body'] ?? '',
            responseTime: $a['response_time'] ?? 0.0,
        );
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    public function isReal(): bool
    {
        return $this->mode === CloakModeEnum::Real;
    }

    public function isFake(): bool
    {
        return $this->mode === CloakModeEnum::Fake;
    }

    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'standard_integration' => $this->standardIntegration,
            'type' => $this->type->value,
            'simple_page' => $this->simplePage,
            'redirect_query' => $this->redirectQuery,
            'page_type' => $this->pageType,
            'page_with_params' => $this->pageWithParams,
            'origin_page' => $this->originPage,
            'response_status' => $this->responseStatus,
            'response_headers' => $this->responseHeaders,
            'response_body' => $this->responseBody,
            'response_time' => $this->responseTime,
        ];
    }
}
