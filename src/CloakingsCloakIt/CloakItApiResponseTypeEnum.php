<?php

namespace Cloakings\CloakingsCloakIt;

enum CloakItApiResponseTypeEnum: string
{
    case Unknown = 'unknown';
    case Load = 'load';
    case Redirect = 'redirect';
    case Iframe = 'iframe';
}
