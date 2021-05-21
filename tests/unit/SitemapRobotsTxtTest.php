<?php

/**
 * SitemapRobotsTxtTest.php
 *
 * @package           WordPressURLDetector
 * @author            Leon Stafford <me@ljs.dev>
 * @license           The Unlicense
 * @link              https://unlicense.org
 */

declare(strict_types=1);

namespace WordPressURLDetector;

/**
 * Class SitemapRobotsTxtTest
 *
 * @package WordPressURLDetector
 */
class SitemapRobotsTxtTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider generateDataForTest
     * @param string $url URL
     * @param string $body URL body content
     * @param array $result Test result to match
     */
    public function testRobotsTxt( $url, $body, $result )
    {
        $parser = new SitemapParser('SitemapParser');
        $this->assertInstanceOf('WordPressURLDetector\SitemapParser', $parser);
        $parser->parse($url, $body);
        $this->assertEquals($result, $parser->getSitemaps());
        $this->assertEquals([], $parser->getURLs());
    }

    /**
     * Generate test data
     *
     * @return array
     */
    public function generateDataForTest()
    {
        return [
            [
                'http://www.example.com/robots.txt',
                <<<'ROBOTSTXT'
User-agent: *
Disallow: /
#Sitemap:http://www.example.com/sitemap.xml.gz
  Sitemap:http://www.example.com/sitemap.xml#comment
ROBOTSTXT
                ,
                $result = [
                    'http://www.example.com/sitemap.xml' => [
                        'loc' => 'http://www.example.com/sitemap.xml',
                        'lastmod' => null,
                    ],
                ],
            ],
        ];
    }
}
