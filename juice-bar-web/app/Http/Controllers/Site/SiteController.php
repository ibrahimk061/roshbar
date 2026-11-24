<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\Site\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

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

    public function contact(Request $request): Response
    {
        return $this->page('contact', [
            'title' => t('contact_title'),
            'errors' => $request->session()->get('contact_errors', []),
            'sent' => (bool) $request->session()->get('contact_sent'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $back = redirect()->to(url('contact').'#form');

        // Honeypot: real people never fill the hidden "website" field.
        if (filled($request->input('website'))) {
            return $back;
        }

        $in = [
            'name' => trim((string) $request->input('name', '')),
            'phone' => preg_replace('/[^0-9+]/', '', (string) $request->input('phone', '')),
            'topic' => (string) $request->input('topic', 'general'),
            'message' => trim((string) $request->input('message', '')),
        ];

        $errors = [];
        if (mb_strlen($in['name']) < 2 || mb_strlen($in['name']) > 120) {
            $errors['name'] = t('err_name');
        }
        if (! preg_match('/^(\+?88)?01[3-9]\d{8}$/', $in['phone'])) {
            $errors['phone'] = t('err_phone');
        }
        if (! in_array($in['topic'], Message::TOPICS, true)) {
            $in['topic'] = 'general';
        }
        if (mb_strlen($in['message']) < 10 || mb_strlen($in['message']) > 2000) {
            $errors['message'] = t('err_message');
        }
        // One message per minute per visitor (session) and per IP.
        $key = 'contact:'.$request->ip();
        if (! $errors && RateLimiter::tooManyAttempts($key, 1)) {
            $errors['message'] = t('err_wait');
        }

        if ($errors) {
            return $back->withInput($in)->with('contact_errors', $errors);
        }

        Message::query()->create($in + ['ip' => $request->ip()]);
        RateLimiter::hit($key, 60);

        return $back->with('contact_sent', true);
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
