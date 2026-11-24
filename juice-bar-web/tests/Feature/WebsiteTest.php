<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Product;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
    }

    public function test_public_pages_render_without_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Roshbar')->assertSee('Himsagar Mango');
        $this->get('/menu')->assertOk()->assertSee('Green Daab');
        $this->get('/menu?c=detox')->assertOk()->assertSee('Green Reset')->assertDontSee('Himsagar Mango');
        $this->get('/menu/himsagar-mango')->assertOk()->assertSee('Himsagar Mango')->assertSee('৳320');
        $this->get('/about')->assertOk();
        $this->get('/contact')->assertOk()->assertSee('name="_token"', false);
    }

    public function test_bangla_toggle_is_remembered(): void
    {
        $this->get('/menu/himsagar-mango?lang=bn')->assertOk()->assertSee('হিমসাগর আম')->assertSee('৳৩২০');
        $this->get('/menu')->assertOk()->assertSee('কচি ডাব');
        $this->get('/menu?lang=en')->assertOk()->assertSee('Green Daab');
    }

    public function test_website_prices_follow_the_pos_menu(): void
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->first();
        $p->variants()->where('name', '500ml')->update(['price' => '350.00']);

        $this->get('/menu/himsagar-mango')->assertSee('৳350')->assertDontSee('৳320');
    }

    public function test_hidden_products_are_not_on_the_website(): void
    {
        Product::query()->where('slug', 'green-daab')->update(['show_on_website' => false]);
        $this->get('/menu')->assertDontSee('Green Daab');
        $this->get('/menu/green-daab')->assertNotFound();

        Product::query()->where('slug', 'bel-sharbat')->update(['is_active' => false]);
        $this->get('/menu/bel-sharbat')->assertNotFound();
    }

    public function test_unknown_page_shows_site_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('This page does not exist.');
    }

    public function test_contact_form_saves_message(): void
    {
        $this->post('/contact', ['name' => 'Rahim', 'phone' => '01712345678', 'topic' => 'catering', 'message' => 'Juice for 50 people on Friday?'])
            ->assertRedirect(url('contact').'#form');

        $m = Message::query()->sole();
        $this->assertSame('catering', $m->topic);
        $this->assertFalse($m->is_read);
        $this->get('/contact')->assertSee('alert-ok', false);
    }

    public function test_contact_form_validates_and_blocks_spam(): void
    {
        $this->post('/contact', ['name' => 'R', 'phone' => '12345', 'message' => 'short'])
            ->assertRedirect()->assertSessionHas('contact_errors', fn ($e) => isset($e['name'], $e['phone'], $e['message']));

        // honeypot
        $this->post('/contact', ['name' => 'Bot', 'phone' => '01712345678', 'message' => 'Buy cheap things now!!', 'website' => 'x']);
        $this->assertSame(0, Message::query()->count());

        // second message within a minute
        $ok = ['name' => 'Rahim', 'phone' => '01712345678', 'message' => 'First message here.'];
        $this->post('/contact', $ok);
        $this->post('/contact', $ok)->assertSessionHas('contact_errors');
        $this->assertSame(1, Message::query()->count());
    }

    public function test_contact_form_escapes_old_input(): void
    {
        $this->post('/contact', ['name' => '<script>x</script>', 'phone' => 'bad', 'message' => 'short']);
        $this->get('/contact')->assertDontSee('<script>x</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
