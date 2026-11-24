<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Public roshbar.com pages. Views are the ported plain-PHP templates in resources/views/site. */
class SiteController extends Controller
{
    public function __construct(private Catalog $catalog) {}

    public function home(): Response
    {
        $month = (int) now(config('pos.timezone'))->format('n');

        return $this->page('home', [
            'title' => t('home_title'),
            'featured' => $this->catalog->featured(8),
            'seasonal' => $this->catalog->inSeason($month),
            'categories' => $this->catalog->categoriesWithCount(),
            'month' => $month,
        ]);
    }

    public function menu(Request $request): Response
    {
        $categories = $this->catalog->categories();
        $cat = $request->query('c');
        if (! is_string($cat) || ! in_array($cat, array_column($categories, 'slug'), true)) {
            $cat = null;
        }

        return $this->page('menu', [
            'title' => t('menu_title'),
            'categories' => $categories,
            'active' => $cat,
            'products' => $this->catalog->all($cat),
        ]);
    }

    public function product(string $slug): Response
    {
        $product = $this->catalog->findBySlug($slug);
        if (! $product) {
            return $this->notFound();
        }

        return $this->page('product', [
            'title' => tr($product, 'name').' — '.config('site.name'),
            'theme' => $product['color'],
            'product' => $product,
            'sizes' => $product['sizes'],
            'related' => $this->catalog->related($product, 4),
        ]);
    }

    public function about(): Response
    {
        return $this->page('about', ['title' => t('about_title'), 'featured' => $this->catalog->featured(4)]);
    }

    public function contact(): Response
    {
        return $this->page('contact', ['title' => t('contact_title')]);
    }

    public function notFound(): Response
    {
        return $this->page('error', ['title' => t('error_404'), 'code' => 404, 'message' => t('error_404')], 404);
    }

    private function page(string $view, array $data, int $status = 200): Response
    {
        $content = view('site.pages.'.$view, $data)->render();

        return response(view('site.layouts.main', $data + ['content' => $content])->render(), $status);
    }
}
