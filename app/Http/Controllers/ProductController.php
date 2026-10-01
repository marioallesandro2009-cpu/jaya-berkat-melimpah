<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * A product's own page: /products/{slug}, /id/produk/{slug}. Only products whose detail page is
 * published (and that are active) have one; the catalogue is the home page's #products block and the /products index.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $data = FrontendData::all();
        $totalProducts = count($data['products']);

        // Category chips: only active categories that have at least one visible product.
        $counts = array_count_values(array_filter(array_map(fn (array $product): ?int => $product['category']['id'] ?? null, $data['products'])));
        $categories = ProductCategory::query()->active()->ordered()->get()
            ->filter(fn (ProductCategory $category): bool => isset($counts[$category->id]))
            ->map(fn (ProductCategory $category): array => [...$category->toFrontend(), 'count' => $counts[$category->id]])
            ->values()->all();
        $current = collect($categories)->firstWhere('slug', (string) $request->query('category'));

        if ($current) {
            $data['products'] = array_values(array_filter($data['products'], fn (array $product): bool => ($product['category']['id'] ?? null) === $current['id']));
        }

        $section = $data['sections']['products'] ?? null;
        $seo = Seo::page($data, 'products', (string) ($section['title'] ?? __('Products')), $section['body'] ?? null, $data['products'][0]['image'] ?? null);

        Locales::setPagePaths(Links::pagePaths('products'));

        return view('products.index', [
            ...$data,
            'categories' => $categories,
            'currentCategory' => $current,
            'totalProducts' => $totalProducts,
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::query()->active()->withDetailPage()->where('slug', $slug)->with('media')->first();

        abort_if($product === null, 404);

        $data = FrontendData::all();
        $paths = [];

        foreach (Locales::all() as $locale) {
            $paths[$locale] = $product->detailPath($locale);
        }

        Locales::setPagePaths($paths);

        $page = FrontendData::remember('product.'.$product->id.'.'.Locales::current(), fn (): array => $product->toDetail());
        $seo = Seo::product($page, $data['settings'], $paths);

        return view('products.show', [
            'settings' => $data['settings'],
            'page' => $page,
            'others' => array_values(array_filter($data['products'], fn (array $other): bool => $other['id'] !== $product->id)),
            'seo' => $seo,
            'jsonLd' => StructuredData::product($page, $data['settings'], $seo),
        ]);
    }
}
