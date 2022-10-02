<?php

namespace Cloakings\CloakingsCloakIt;

use Cloakings\CloakingsCommon\CloakModeEnum;

class CloakItApiResponse
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
        );
    }
}
