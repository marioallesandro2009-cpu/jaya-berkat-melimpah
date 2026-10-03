<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Species;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Catalogue pages: the index (/products, /id/produk) with filters, a species page
 * (/products/{category}/{species}) and a product's own page (/products/{slug}).
 * Only products whose detail page is published (and that are active) have their own page.
 */
class ProductController extends Controller
{
    /** Filter parameters of the index, in the order they are shown. */
    private const FILTERS = ['species', 'cut', 'storage', 'grade'];

    public function index(Request $request): View
    {
        $data = FrontendData::all();
        $allProducts = $data['products'];
        $totalProducts = count($allProducts);

        // Category chips: only active categories that have at least one visible product.
        $counts = array_count_values(array_filter(array_map(fn (array $product): ?int => $product['category']['id'] ?? null, $allProducts)));
        $categories = ProductCategory::query()->active()->ordered()->get()
            ->filter(fn (ProductCategory $category): bool => isset($counts[$category->id]))
            ->map(fn (ProductCategory $category): array => [...$category->toFrontend(), 'count' => $counts[$category->id]])
            ->values()->all();
        $wanted = $request->query('category');
        $current = is_string($wanted) ? collect($categories)->firstWhere('slug', $wanted) : null;

        // The other filters: the choices on offer are the ones that exist among the visible products.
        $options = $this->options($allProducts);
        $filters = [];

        foreach (self::FILTERS as $name) {
            $value = $request->query($name);
            $filters[$name] = is_string($value) && isset($options[$name][$value]) ? $value : null;
        }

        $products = array_values(array_filter($allProducts, fn (array $product): bool => $this->matches($product, $current['id'] ?? null, $filters)));
        $active = $current !== null || array_filter($filters) !== [];

        $section = $data['sections']['products'] ?? null;
        $seo = Seo::page($data, 'products', (string) ($section['title'] ?? __('Products')), $section['body'] ?? null, $allProducts[0]['image'] ?? null);

        Locales::setPagePaths(Links::pagePaths('products'));

        return view('products.index', [
            ...$data,
            'products' => $products,
            'featured' => $active ? [] : array_values(array_filter($allProducts, fn (array $product): bool => $product['featured'])),
            'groups' => $this->groups($products, $categories),
            'categories' => $categories,
            'currentCategory' => $current,
            'filters' => $filters,
            'options' => $options,
            'active' => $active,
            'totalProducts' => $totalProducts,
            'allProducts' => $allProducts,
            'speciesCount' => max(1, count(array_unique(array_filter(array_map(fn (array $product): ?int => $product['species']['id'] ?? null, $allProducts))))),
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }

    public function species(string $category, string $species): View
    {
        $row = Species::query()->active()->where('slug', $species)
            ->whereHas('category', fn ($query) => $query->active()->where('slug', $category))
            ->with(['category', 'media', 'cuts'])->first();

        abort_if($row === null, 404);

        $data = FrontendData::all();
        $products = array_values(array_filter($data['products'], fn (array $product): bool => ($product['species']['id'] ?? null) === $row->id));
        $paths = [];

        foreach (Locales::all() as $locale) {
            $paths[$locale] = (string) $row->detailPath($locale);
        }

        Locales::setPagePaths($paths);

        $page = $row->toFrontend();
        $seo = Seo::product([
            'name' => $page['name'],
            'image' => $page['image'],
            'seoTitle' => $row->translate('meta_title'),
            'seoDescription' => $row->translate('meta_description') ?? $page['description'],
            'intro' => $page['description'],
        ], $data['settings'], $paths);

        $cuts = [];

        foreach ($row->cuts as $cut) {
            $product = collect($products)->firstWhere('cutSlug', $cut->slug);
            $cuts[] = ['name' => (string) $cut->translate('name'), 'sold' => (bool) data_get($cut, 'pivot.available_as_product') && $product !== null, 'url' => $product['url'] ?? null];
        }

        return view('products.species', [
            ...$data,
            'species' => $page,
            'category' => $row->category->toFrontend(),
            'products' => $products,
            'cuts' => $cuts,
            'siblings' => Species::query()->active()->where('category_id', $row->category_id)->whereKeyNot($row->id)->ordered()->get()->map->toFrontend()->all(),
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::query()->active()->withDetailPage()->where('slug', $slug)->with(['media', 'species.category', 'cut'])->first();

        abort_if($product === null, 404);

        $data = FrontendData::all();
        $paths = [];

        foreach (Locales::all() as $locale) {
            $paths[$locale] = $product->detailPath($locale);
        }

        Locales::setPagePaths($paths);

        $page = FrontendData::remember('product.'.$product->id.'.'.Locales::current(), fn (): array => $product->toDetail());
        $seo = Seo::product($page, $data['settings'], $paths);
        $others = array_values(array_filter($data['products'], fn (array $other): bool => $other['id'] !== $product->id));
        // Same species first: a buyer looking at a loin is probably also interested in the saku.
        usort($others, fn (array $a, array $b): int => (($b['species']['id'] ?? 0) === ($page['species']['id'] ?? -1)) <=> (($a['species']['id'] ?? 0) === ($page['species']['id'] ?? -1)));

        return view('products.show', [
            'settings' => $data['settings'],
            'page' => $page,
            'others' => array_slice($others, 0, 8),
            'seo' => $seo,
            'jsonLd' => StructuredData::product($page, $data['settings'], $seo),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array<string, array<string, array{label: string, count: int}>>
     */
    private function options(array $products): array
    {
        $options = ['species' => [], 'cut' => [], 'storage' => [], 'grade' => []];

        foreach ($products as $product) {
            foreach ([
                'species' => [$product['species']['slug'] ?? null, $product['species']['name'] ?? null],
                'cut' => [$product['cutSlug'], $product['cut']],
                'storage' => [$product['freezing'], $product['storage'] ? explode(',', (string) $product['storage'])[0] : null],
                'grade' => [$product['grade'], $product['grade']],
            ] as $name => [$key, $label]) {
                if (blank($key) || blank($label)) {
                    continue;
                }

                $options[$name][$key] = ['label' => (string) $label, 'count' => ($options[$name][$key]['count'] ?? 0) + 1];
            }
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, string|null>  $filters
     */
    private function matches(array $product, ?int $categoryId, array $filters): bool
    {
        return ($categoryId === null || ($product['category']['id'] ?? null) === $categoryId)
            && ($filters['species'] === null || ($product['species']['slug'] ?? null) === $filters['species'])
            && ($filters['cut'] === null || $product['cutSlug'] === $filters['cut'])
            && ($filters['storage'] === null || $product['freezing'] === $filters['storage'])
            && ($filters['grade'] === null || $product['grade'] === $filters['grade']);
    }

    /**
     * Products grouped by category in the categories' order (uncategorised last).
     *
     * @param  list<array<string, mixed>>  $products
     * @param  array<int, array<string, mixed>>  $categories
     * @return list<array{category: array<string, mixed>|null, products: list<array<string, mixed>>}>
     */
    private function groups(array $products, array $categories): array
    {
        $groups = [];

        foreach ($categories as $category) {
            $items = array_values(array_filter($products, fn (array $product): bool => ($product['category']['id'] ?? null) === $category['id']));

            if ($items !== []) {
                $groups[] = ['category' => $category, 'products' => $items];
            }
        }

        $rest = array_values(array_filter($products, fn (array $product): bool => ! in_array($product['category']['id'] ?? null, array_column($categories, 'id'), true)));

        if ($rest !== []) {
            $groups[] = ['category' => null, 'products' => $rest];
        }

        return $groups;
    }
}
