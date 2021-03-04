<?php

declare(strict_types=1);

namespace WordPressURLDetector;

use WordPressURLDetectorGuzzleHttp\Client;
use WordPressURLDetectorGuzzleHttp\Psr7\Request;

class DetectSitemaps
{

    /**
     * Detect Authors URLs
     *
     * @return array<string> list of URLs
     * @throws \WordPressURLDetector\WordPressURLDetectorException
     */
    public static function detect( string $wp_site_url ): array
    {
        $sitemaps_urls = [];
        $parser = new SitemapParser('WordPressURLDetector.com', [ 'strict' => false ]);

        $site_path = rtrim(SiteInfo::getURL('site'), '/');

        $port_override = apply_filters(
            'wp2static_curl_port',
            null
        );

        $base_uri = $site_path;

        if ($port_override) {
            $base_uri = "{$base_uri}:{$port_override}";
        }

        $client = new Client(
            [
                'base_uri' => $base_uri,
                'verify' => false,
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => 1,
                    // required to get effective_url
                    'track_redirects' => true,
                ],
                'connect_timeout' => 0,
                'timeout' => 600,
                'headers' => [
                    'User-Agent' => apply_filters(
                        'wp2static_curl_user_agent',
                        'WordPressURLDetector.com',
                    ),
                ],
            ]
        );

        $headers = [];

        $auth_user = CoreOptions::getValue('basicAuthUser');

        if ($auth_user) {
            $auth_password = CoreOptions::getValue('basicAuthPassword');

            if ($auth_password) {
                $headers['auth'] = [ $auth_user, $auth_password ];
            }
        }

        $request = new Request('GET', '/robots.txt', $headers);

        $response = $client->send($request);

        $robots_exists = $response->getStatusCode() === 200;

        try {
            $sitemaps = [];

            // if robots exists, parse for possible sitemaps
            if ($robots_exists) {
                $parser->parseRecursive($wp_site_url . 'robots.txt');
                $sitemaps = $parser->getSitemaps();
            }

            // if no sitemaps add known sitemaps
            if ($sitemaps === []) {
                $sitemaps = [
                    // we're assigning empty arrays to match sitemaps library
                    '/sitemap.xml' => [], // normal sitemap
                    '/sitemap_index.xml' => [], // yoast sitemap
                    '/wp_sitemap.xml' => [], // wp 5.5 sitemap
                ];
            }

            foreach (array_keys($sitemaps) as $sitemap) {
                if (! is_string($sitemap)) {
                    continue;
                }

                $request = new Request('GET', $sitemap, $headers);

                $response = $client->send($request);

                $status_code = $response->getStatusCode();

                if ($status_code !== 200) {
                    continue;
                }

                $parser->parse($wp_site_url . $sitemap);

                $sitemaps_urls[] = '/' . str_replace(
                    $wp_site_url,
                    '',
                    $sitemap
                );

                $extract_sitemaps = $parser->getSitemaps();

                foreach ($extract_sitemaps as $url => $tags) {
                    $sitemaps_urls[] = '/' . str_replace(
                        $wp_site_url,
                        '',
                        $url
                    );
                }
            }
        } catch (\WordPressURLDetector\WordPressURLDetectorException $e) {
            WsLog::l($e->getMessage());
            throw new \WordPressURLDetector\WordPressURLDetectorException($e->getMessage(), 0, $e);
        }

        return $sitemaps_urls;
    }
}