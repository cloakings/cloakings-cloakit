<?php

namespace Cloakings\CloakingsCloakIt;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class CloakItPreloader
{
    public function __construct(
        private readonly string $userAgent = 'Chrome 100',
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function load($url): string
    {
        $context = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'header' => 'User-Agent: ' . $this->userAgent,
            ]
        ];

        try {
            $result = file_get_contents(
                filename: $url,
                context: stream_context_create($context),
            );
        } catch (Throwable $e) {
            $this->logger->error('cloaking_preload_error', ['service' => 'cloakit', 'url' => $url, 'exception' => $e]);
            $result = '';
        }

        return $result;
    }
}
