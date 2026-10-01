<?php

namespace App\Http\Controllers;

use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

class CompanyController extends Controller
{
    public function __invoke(): View
    {
        $data = FrontendData::all();
        $header = $data['sections']['company_header'] ?? null;
        $seo = Seo::page($data, 'company', (string) ($header['eyebrow'] ?? __('Company Profile')), $header['body'] ?? null, $header['image'] ?? null);

        Locales::setPagePaths(Links::pagePaths('company'));

        return view('company', [
            ...$data,
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }
}
