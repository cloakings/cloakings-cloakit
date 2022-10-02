<?php

namespace Cloakings\CloakingsCloakIt;

use Cloakings\CloakingsCommon\CloakerResult;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CloakItRenderer
{
    public function __construct(
        private readonly string $baseIncludeDir,
        private readonly CloakItPreloader $preloader = new CloakItPreloader(),
    ) {
    }

    public function render(CloakerResult $cloakerResult): Response {
        $r = $cloakerResult->apiResponse;
        if (!($r instanceof CloakItApiResponse)) {
            return new Response();
        }

        if ($r->standardIntegration) {
            if (is_file($this->makePath($r->simplePage))) {
                if ($r->type === CloakItApiResponseTypeEnum::Load) {
                    if ($r->redirectQuery) {
                        return new RedirectResponse('?' . $r->redirectQuery);
                    } else {
                        return new Response($this->include($this->makePath($r->simplePage)));
                    }
                }
                if ($r->type === CloakItApiResponseTypeEnum::Redirect) {
                    return new RedirectResponse($r->pageWithParams);
                }
            }
            if ($r->type === CloakItApiResponseTypeEnum::Load) {
                if ($r->redirectQuery) {
                    return new RedirectResponse('?' . $r->redirectQuery);
                } else {
                    $content = $this->preloader->load($r->simplePage);
                    $content = str_replace('<head>', sprintf('<head><base href="%s" />', $r->simplePage), $content);

                    return new Response($content);
                }
            }
            if ($r->type === CloakItApiResponseTypeEnum::Redirect) {
                return new RedirectResponse($r->pageWithParams);
            }
            if ($r->type === CloakItApiResponseTypeEnum::Iframe) {
                return new Response(sprintf(
                    '<iframe src="%s" width="100%%" height="100%%" align="left"></iframe> <style> body { padding: 0; margin: 0; } iframe { margin: 0; padding: 0; border: 0; } </style>',
                    $r->pageWithParams
                ));
            }
        } else {
            if ($r->pageType === 'white') {
                return new Response();
            }
            $html = '';
            if (is_file($this->makePath($r->simplePage))) {
                if ($r->type === CloakItApiResponseTypeEnum::Load) {
                    if ($r->redirectQuery) {
                        $html = sprintf('<head><meta http-equiv="refresh" content="0; URL=%s?%s" /></head>', $r->originPage, $r->redirectQuery);
                    } else {
                        $html = $this->preloader->load($r->simplePage);
                    }
                }
                if ($r->type === CloakItApiResponseTypeEnum::Redirect) {
                    $html = sprintf('<head><meta http-equiv="refresh" content="0; URL=%s" /></head>', $r->pageWithParams);
                }
            } else {
                if ($r->type === CloakItApiResponseTypeEnum::Load) {
                    $html = sprintf('<head><meta http-equiv="refresh" content="0; URL=%s?%s" /></head>', $r->originPage, $r->redirectQuery);
                } else {
                    $html = $this->preloader->load($r->simplePage);
                    $html = str_replace('<head>', sprintf('<head><base href="%s" />', $r->simplePage), $html);
                }
                if ($r->type === CloakItApiResponseTypeEnum::Redirect) {
                    $html = sprintf('<head><meta http-equiv="refresh" content="0; URL=%s" /></head>', $r->pageWithParams);
                }
            }
            if ($r->type === CloakItApiResponseTypeEnum::Iframe) {
                return new Response(sprintf(
                    '<iframe src="%s" width="100%%" height="100%%" align="left"></iframe> <style> body { padding: 0; margin: 0; } iframe { margin: 0; padding: 0; border: 0; } </style>',
                    $r->pageWithParams
                ));
            }

            $html = sprintf('document.open();document.write(`%s`);document.close();', $html);

            return new Response($html);
        }

        return new Response();
    }

    private function makePath(string $filename): string
    {
        return rtrim($this->baseIncludeDir, '/') . '/' . ltrim($filename, '/');
    }

    private function include(string $filename): string
    {
        ob_start();
        include($filename);

        return ob_get_clean();
    }
}

// original code
/*

<?php

error_reporting(0);
$data = array(
    'companyId' => "XXX",
    'referrerCF' => $_GET["referrerCF"],
    'urlCF' => $_GET["urlCF"],
    'QUERY_STRING' => $_SERVER["QUERY_STRING"],
    'HTTP_REFERER' => $_SERVER["HTTP_REFERER"],
    'HTTP_USER_AGENT' => $_SERVER["HTTP_USER_AGENT"],
    'REMOTE_ADDR' => $_SERVER["REMOTE_ADDR"],
    'HTTP_CF_CONNECTING_IP' => $_SERVER["HTTP_CF_CONNECTING_IP"],
    'CF_CONNECTING_IP' => $_SERVER["CF_CONNECTING_IP"],
    'X_FORWARDED_FOR' => $_SERVER["X_FORWARDED_FOR"],
    'TRUE_CLIENT_IP' => $_SERVER["TRUE_CLIENT_IP"],
    );
$curl = curl_init('http://api.clofilter.com/v1/check');
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
));
$api = json_decode(curl_exec($curl));
curl_close($curl);
$arrContextOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false), 'http' => array('header' => 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT']));
if ($api->standartIntegration) {
    if (file_exists($api->simplePage)) {
        if ($api->type == 'load') {
            if ($api->redirectQuery) {
                header('Location: ?' . $api->redirectQuery);
            } else {
                require_once($api->simplePage);
            }
        }
        if ($api->type == 'redirect') {
            header('Location: ' . $api->pageWithParams);
        }
        exit;
    }
    if ($api->type == 'load') {
        if ($api->redirectQuery) {
            header('Location: ?' . $api->redirectQuery);
        } else {
            echo str_replace('<head>', '<head><base href="' . $api->simplePage . '" />', file_get_contents($api->simplePage, false, stream_context_create($arrContextOptions)));
        }
    }
    if ($api->type == 'redirect') {
        header('Location: ' . $api->pageWithParams);
    }
    if ($api->type == 'iframe') {
        echo '<iframe src="' . $api->pageWithParams . '" width="100%" height="100%" align="left"></iframe> <style> body { padding: 0; margin: 0; } iframe { margin: 0; padding: 0; border: 0; } </style>';
    }
} else {
    if ($api->pageType == 'white') {
        echo '';
        exit;
    }
    if (file_exists($api->simplePage)) {
        if ($api->type == 'load') {
            if ($api->redirectQuery) {
                $api->pageHTML = '<head><meta http-equiv="refresh" content="0; URL=' .$api->originPage . '?' . $api->redirectQuery . '" /></head>';
            } else {
                $api->pageHTML = file_get_contents($api->simplePage, false, stream_context_create($arrContextOptions));
            }
        }
        if ($api->type == 'redirect') {
            $api->pageHTML = '<head><meta http-equiv="refresh" content="0; URL=' . $api->pageWithParams . '" /></head>';
        }
    } else {
        if ($api->type == 'load') {
            if ($api->redirectQuery) {
                $api->pageHTML = '<head><meta http-equiv="refresh" content="0; URL=' .$api->originPage . '?' . $api->redirectQuery . '" /></head>';
            } else {
                $api->pageHTML = str_replace('<head>', '<head><base href="' . $api->simplePage . '" />', file_get_contents($api->simplePage, false, stream_context_create($arrContextOptions)));
            }
        }
        if ($api->type == 'redirect') {
            $api->pageHTML = '<head><meta http-equiv="refresh" content="0; URL=' . $api->pageWithParams . '" /></head>';
        }
        if ($api->type == 'iframe') {
            $api->pageHTML = '<iframe src="' . $api->pageWithParams . '" width="100%" height="100%" align="left"></iframe> <style> body { padding: 0; margin: 0; } iframe { margin: 0; padding: 0; border: 0; } </style>';
        }
    }
    echo 'document.open();document.write(`' . $api->pageHTML . '`);document.close();';
}

*/
