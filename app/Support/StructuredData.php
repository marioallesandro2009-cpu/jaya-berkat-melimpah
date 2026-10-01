<?php

namespace App\Support;

/**
 * JSON-LD (schema.org) for the public pages.
 */
final class StructuredData
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function home(array $data, array $seo): array
    {
        $settings = $data['settings'];
        $url = (string) $seo['canonical'];

        $faq = $data['faqs'] === [] ? [] : [[
            '@type' => 'FAQPage',
            '@id' => $url.'#faq',
            'mainEntity' => array_map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ], $data['faqs']),
        ]];

        return ['@context' => 'https://schema.org', '@graph' => [
            ...$faq,
            self::organization($settings),
            [
                '@type' => 'WebSite',
                '@id' => Seo::baseUrl().'/#website',
                'url' => Seo::baseUrl().'/',
                'name' => $settings['companyName'],
                'inLanguage' => Locales::current(),
                'publisher' => ['@id' => Seo::baseUrl().'/#organization'],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => Locales::current(),
                'isPartOf' => ['@id' => Seo::baseUrl().'/#website'],
            ],
        ]];
    }

    /**
     * A plain page (company, news index) with a breadcrumb.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function page(array $data, array $seo): array
    {
        $settings = $data['settings'];
        $url = (string) $seo['canonical'];

        return ['@context' => 'https://schema.org', '@graph' => [
            self::organization($settings),
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => Locales::current(),
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => Locales::url(Locales::current())],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $seo['title'], 'item' => $url],
                ],
            ],
        ]];
    }

    /**
     * A product's own page.
     *
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function product(array $page, array $settings, array $seo): array
    {
        $url = (string) $seo['canonical'];
        $orgId = Seo::baseUrl().'/#organization';

        return ['@context' => 'https://schema.org', '@graph' => [
            self::organization($settings),
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => Locales::current(),
                'dateModified' => $page['updatedAt'],
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            ],
            array_filter([
                '@type' => 'Product',
                '@id' => $url.'#product',
                'name' => $page['name'],
                'description' => $seo['description'],
                'image' => $seo['image']['url'] ?? null,
                'brand' => ['@id' => $orgId],
                'manufacturer' => ['@id' => $orgId],
                'additionalProperty' => $page['specs'] === [] ? null : array_map(fn (array $row): array => ['@type' => 'PropertyValue', 'name' => $row['label'], 'value' => $row['value']], $page['specs']),
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => Locales::url(Locales::current())],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('Products'), 'item' => Locales::url(Locales::current()).'#products'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $page['name'], 'item' => $url],
                ],
            ],
        ]];
    }

    /**
     * A news article's own page.
     *
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function newsPost(array $page, array $settings, array $seo): array
    {
        $locale = Locales::current();
        $url = (string) $seo['canonical'];
        $orgId = Seo::baseUrl().'/#organization';

        return ['@context' => 'https://schema.org', '@graph' => [
            self::organization($settings),
            [
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'url' => $url,
                'name' => $seo['title'],
                'description' => $seo['description'],
                'inLanguage' => $locale,
                'dateModified' => $page['updatedAt'],
                'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            ],
            array_filter([
                '@type' => 'NewsArticle',
                '@id' => $url.'#article',
                'headline' => $page['title'],
                'description' => $seo['description'],
                'url' => $url,
                'image' => $seo['image']['url'] ?? null,
                'datePublished' => $page['publishedAt'],
                'dateModified' => $page['updatedAt'],
                'author' => ['@id' => $orgId],
                'publisher' => ['@id' => $orgId],
                'mainEntityOfPage' => ['@id' => $url.'#webpage'],
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('Home'), 'item' => Locales::url($locale)],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('News'), 'item' => Seo::baseUrl().Links::page('news')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $page['title'], 'item' => $url],
                ],
            ],
        ]];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function organization(array $settings): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => Seo::baseUrl().'/#organization',
            'name' => $settings['legalName'] ?? $settings['companyName'] ?? null,
            'url' => Seo::baseUrl().'/',
            'logo' => $settings['logo']['url'] ?? null,
            'email' => $settings['email'] ?? null,
            'telephone' => $settings['phone'] ?? null,
            'address' => ($settings['address'] ?? null) ? ['@type' => 'PostalAddress', 'streetAddress' => str_replace(["\r", "\n"], ', ', (string) $settings['address'])] : null,
            'sameAs' => $settings['sameAs'] ?? null,
        ]);
    }
}
