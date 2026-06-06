<?php

declare(strict_types=1);

namespace App\Core;

final class HttpClient
{
    public function request(string $method, string $url, array $headers = [], ?array $json = null, ?array $form = null): array
    {
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = is_int($k) ? $v : "{$k}: {$v}";
        }

        $uaPool = Env::split('USER_AGENT_POOL');
        $ua = $uaPool !== [] ? $uaPool[array_rand($uaPool)] : 'Mozilla/5.0 SocialHarvesterBot/1.0';
        $headerLines[] = 'User-Agent: ' . $ua;

        $proxyPool = Env::csv('PROXY_POOL');
        $proxy = $proxyPool !== [] ? $proxyPool[array_rand($proxyPool)] : null;

        $opts = [
            'http' => [
                'method' => strtoupper($method),
                'ignore_errors' => true,
                'timeout' => 30,
                'header' => implode("\r\n", $headerLines),
            ],
        ];

        if ($json !== null) {
            $opts['http']['header'] .= "\r\nContent-Type: application/json";
            $opts['http']['content'] = json_encode($json, JSON_UNESCAPED_UNICODE);
        }

        if ($form !== null) {
            $opts['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $opts['http']['content'] = http_build_query($form);
        }

        if ($proxy) {
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
        }

        usleep(random_int(30000, 180000));
        $context = stream_context_create($opts);
        $raw = @file_get_contents($url, false, $context);

        $status = 0;
        global $http_response_header;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }

        $data = json_decode((string) $raw, true);
        return [
            'status' => $status,
            'data' => is_array($data) ? $data : [],
            'raw' => (string) $raw,
        ];
    }
}
