<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\FrontendData;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

/**
 * A product's own page: /products/{slug}, /id/produk/{slug}. Only products whose detail page is
 * published (and that are active) have one; the catalogue itself is the home page's #products block.
 */
class ProductController extends Controller
{
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
