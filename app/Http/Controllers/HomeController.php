<?php

namespace App\Http\Controllers;

use App\Support\FrontendData;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $data = FrontendData::all();
        $seo = Seo::home($data);

        return view('home', [
            ...$data,
            'seo' => $seo,
            'jsonLd' => StructuredData::home($data, $seo),
        ]);
    }
}
