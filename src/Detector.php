<?php

/**
 * Detector.php
 *
 * @package           WordPressURLDetector
 * @author            Leon Stafford <me@ljs.dev>
 * @license           The Unlicense
 * @link              https://unlicense.org
 */

declare(strict_types=1);

namespace WordPressURLDetector;

/**
 * Detects URLs in a WordPress site
 */
class Detector
{
    protected function __construct()
    {
    }

    /**
     * Detect URLs within site
     *
     * @return array<string> of URLs
     */
    // phpcs:ignore NeutronStandard.Functions.LongFunction.LongFunction
    public function detectURLs(
        DetectorConfig $config,
        SiteInfo $siteInfo
    ): array {
        $wpdb = new WPDB();

        return array_unique(
            FilesHelper::cleanDetectedURLs(
                array_merge(
                    $config->detectCommon ? DetectCommon::detect() : [],
                    $config->detectPosts ? DetectPosts::detect() : [],
                    $config->detectPages ? DetectPages::detect() : [],
                    $config->detectCustomPostTypes ? DetectCustomPostTypes::detect() : [],
                    $config->detectUploads ? FilesHelper::getListOfLocalFilesByDir($siteInfo::getPath('uploads')) : [],
                    $config->detectSitemaps ? DetectSitemaps::detect($siteInfo::getURL('site')) : [],
                    // TODO: these may as well be generic path/URL directory iterators
                    $config->detectParentThemeAssets ? DetectThemeAssets::detect(
                        $siteInfo::getPath('site'),
                        $siteInfo::getPath('parent_theme'),
                    ) : [],
                    $config->detectChildThemeAssets ? DetectThemeAssets::detect(
                        $siteInfo::getPath('site'),
                        $siteInfo::getPath('child_theme'),
                    ) : [],
                    $config->detectPluginAssets ? DetectPluginAssets::detect() : [],
                    $config->detectWPIncludesAssets ? DetectWPIncludesAssets::detect(
                        $siteInfo::getPath('includes'),
                        $siteInfo::getURL('includes'),
                        $siteInfo::getURL('home'),
                    ) : [],
                    $config->detectThirdPartyAssets ?  DetectThirdPartyAssets::detect(
                        $siteInfo::getURL('site'),
                        $siteInfo::getPath('content'),
                        $siteInfo::getURL('content'),
                    ) : [],
                    $config->detectPostPagination ? DetectPostPagination::detect(
                        $siteInfo::getURL('site'),
                        $wpdb,
                        SiteInfo::getPaginationBase(),
                        // TODO: move into SiteInfo
                        get_option('posts_per_page'),
                    ) : [],
                    $config->detectArchives ? DetectArchives::detect() : [],
                    $config->detectCategories ? DetectCategories::detect() : [],
                    $config->detectCategoryPagination ? DetectCategoryPagination::detect() : [],
                    $config->detectAuthors ? DetectAuthors::detect() : [],
                    $config->detectAuthorPagination ? DetectAuthorPagination::detect($siteInfo::getURL('site')) : [],
                ),
                $siteInfo::getURL('home')
            )
        );
    }
}
