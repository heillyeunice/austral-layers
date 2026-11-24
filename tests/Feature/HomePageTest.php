<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use App\Models\QuoteInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\NewQuoteInquiry;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Not everything');
        $response->assertSee('Personalized Plate Keychains');
        $response->assertSee('Custom 3D Modeling');
        $response->assertSee('/images/personalized-keychains.png');
        $response->assertSee('/images/automotive-key-holders.png');
        $response->assertSee('/images/custom-made-products.png');
        $response->assertSee('/images/facebook-qr.png');
        $response->assertSee('/images/instagram-qr.png');
        $response->assertSee('/images/tiktok-qr.png');
        $response->assertSee('Down to layer up?');
        $response->assertSee('https://www.facebook.com/profile.php?id=61595552973378');
        $response->assertSee('https://www.instagram.com/austral_layers');
        $response->assertSee('https://www.tiktok.com/@austral_layers');
        $response->assertSee('australlayers@gmail.com');
        $response->assertSee('mailto:australlayers@gmail.com', false);
        $response->assertSee('name="social_media_link"', false);
        $response->assertSee('inputmode="url"', false);
        $response->assertSee(route('admin.login'), false);
        $response->assertSee(route('superadmin.login'), false);
    }

    public function test_quote_submission_is_validated(): void
    {
        $this->post('/quote', [])
            ->assertSessionHasErrors(['name', 'email', 'service', 'quantity', 'message', 'social_media_link']);
    }

    public function test_quote_submission_sends_success_response(): void
    {
        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'company' => 'Example',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'I have a model ready to print.',
        ])->assertRedirect('/')
            ->assertSessionHas('quote_success', true);

        $this->assertDatabaseHas('quote_inquiries', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
            'service' => '3D Printing',
            'status' => 'new',
        ]);

        $inquiry = QuoteInquiry::query()->firstOrFail();
        $admin = (new User)->forceFill([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Social media contact')
            ->assertSee('https://www.facebook.com/alex.profile', false);
    }

    public function test_quote_requires_a_valid_social_media_link(): void
    {
        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
            'social_media_link' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('social_media_link');

        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
        ])->assertRedirect('/')
            ->assertSessionHas('quote_success', true);

        $this->assertDatabaseHas('quote_inquiries', [
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
        ]);
    }

    public function test_quote_submission_accepts_social_links_without_a_scheme(): void
    {
        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'www.facebook.com/heillyeunice',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
        ])->assertRedirect('/')
            ->assertSessionHas('quote_success', true);

        $this->assertDatabaseHas('quote_inquiries', [
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/heillyeunice',
        ]);
    }

    public function test_quote_submission_stores_optional_pdf_inspiration(): void
    {
        Storage::fake('local');
        $inspiration = UploadedFile::fake()->createWithContent(
            'reference.pdf',
            "%PDF-1.4\nReference image notes\n%%EOF"
        );

        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
            'service' => 'Custom 3D Modeling',
            'quantity' => '1–5 pieces',
            'message' => 'I have an idea for a custom part.',
            'inspiration' => [$inspiration],
        ])->assertRedirect('/')
            ->assertSessionHas('quote_success', true);

        $this->assertCount(1, Storage::disk('local')->allFiles('quote-inspiration'));
        $this->assertDatabaseHas('quote_inquiries', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
        ]);
    }

    public function test_new_quote_notifies_admin_and_superadmin_and_can_be_opened_from_dashboard(): void
    {
        $admin = (new User)->forceFill([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'safe-test-password',
            'role' => User::ROLE_ADMIN,
        ]);
        $admin->save();

        $superadmin = (new User)->forceFill([
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'password' => 'safe-test-password',
            'role' => User::ROLE_SUPERADMIN,
        ]);
        $superadmin->save();

        $ordinaryUser = (new User)->forceFill([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => 'safe-test-password',
        ]);
        $ordinaryUser->save();

        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.instagram.com/alex.profile',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
        ])->assertRedirect('/');

        $this->assertDatabaseCount('notifications', 2);

        $inquiry = QuoteInquiry::query()->firstOrFail();
        $notification = $admin->unreadNotifications()->firstOrFail();
        $this->assertSame(NewQuoteInquiry::class, $notification->type);
        $this->assertSame('Alex', $notification->data['customer_name']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('New inquiry from Alex')
            ->assertSee('aria-label="Notifications, 1 unread"', false)
            ->assertSee('class="staff-avatar-trigger"', false);

        $this->get(route('admin.notifications.show', $notification->id))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertNotNull(
            $admin->fresh()->notifications()->findOrFail($notification->id)->read_at
        );

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('New inquiry from Alex');

        $this->assertSame(1, $superadmin->unreadNotifications()->count());
        $this->assertSame(0, $ordinaryUser->notifications()->count());
    }

    public function test_staff_can_update_their_profile_and_view_their_private_photo(): void
    {
        Storage::fake('local');
        $admin = (new User)->forceFill([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'safe-test-password',
            'role' => User::ROLE_ADMIN,
        ]);
        $admin->save();
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+x8ioAAAAASUVORK5CYII=');

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Account type')
            ->assertSee('Profile details')
            ->assertSee('Change profile photo')
            ->assertSee('id="profile-photo-input"', false);

        $this->put(route('admin.profile.update'), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->post(route('admin.profile.photo.update'), [
            'profile_photo' => UploadedFile::fake()->createWithContent('avatar.png', $image),
        ])->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('staff_profile_saved', 'Your profile photo has been updated.');

        $updatedAdmin = $admin->fresh();
        $this->assertSame('Updated Admin', $updatedAdmin->name);
        $this->assertSame(User::ROLE_ADMIN, $updatedAdmin->role);
        $this->assertNotNull($updatedAdmin->profile_photo_path);
        Storage::disk('local')->assertExists($updatedAdmin->profile_photo_path);

        $this->get(route('admin.profile.photo'))->assertOk();

        $this->put(route('admin.profile.update'), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
            'current_password' => 'safe-test-password',
            'password' => 'updated-safe-password',
            'password_confirmation' => 'updated-safe-password',
        ])->assertRedirect(route('admin.profile.edit'));

        $this->assertTrue(Hash::check('updated-safe-password', $admin->fresh()->password));
    }

    public function test_quote_submission_rejects_unsupported_inspiration_file(): void
    {
        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.facebook.com/alex.profile',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
            'inspiration' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
        ])->assertSessionHasErrors(['inspiration.0']);
    }

    public function test_portfolio_and_products_pages_use_the_shared_site_chrome(): void
    {
        $this->get('/portfolio')
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('3D modeling')
            ->assertSee('3D printed')
            ->assertSee('/products', false)
            ->assertSee('id="social-qrs-title"', false);

        $this->get('/products')
            ->assertOk()
            ->assertSee('Custom Plate Number Keychain')
            ->assertSee('Wall Mounted Automotive Key Holder')
            ->assertSee('Custom Christmas Ornaments')
            ->assertSeeInOrder([
                'Custom Plate Number Keychain',
                'Wall Mounted Automotive Key Holder',
                'Custom Christmas Ornaments',
            ])
            ->assertSee('id="social-qrs-title"', false);
    }

    public function test_portfolio_lists_projects_that_link_to_their_own_pages(): void
    {
        $response = $this->get('/portfolio');

        $response->assertOk()
            ->assertSee('12 projects')
            ->assertSee('Mustard Tank')
            ->assertSee('Threaded canister')
            ->assertSee('Mazdaspeed wheel center caps')
            ->assertSee(route('portfolio.show', 'mustard-tank'), false)
            ->assertSee(route('portfolio.show', 'threaded-canister'), false)
            ->assertSee(route('portfolio.show', 'mazdaspeed-wheel-center-caps'), false);
    }

    public function test_portfolio_project_pages_show_gallery_work_and_keywords(): void
    {
        $this->get('/portfolio/mustard-tank')
            ->assertOk()
            ->assertSee('In-house')
            ->assertSee('What we did')
            ->assertSee('0.2 mm nozzle')
            ->assertSee('images/portfolio/tank-front.jpg')
            ->assertSee('images/portfolio/tank-rear.jpg')
            ->assertSee('Rear and turret view');

        $this->get('/portfolio/jeepney-with-zeal-livery')
            ->assertOk()
            ->assertSee('Commissioned')
            ->assertSee('3D modelled')
            ->assertSee('USA–Philippines')
            ->assertSee('images/portfolio/jeepney-render.jpg')
            ->assertSee('images/portfolio/jeepney-plate.jpg');

        $this->get('/portfolio/threaded-canister')
            ->assertOk()
            ->assertSee('CAD design')
            ->assertSee('images/portfolio/canister-sketch.jpg')
            ->assertSee('images/portfolio/canister-section.jpg')
            ->assertSee('images/portfolio/canister-assembly.jpg');
    }

    public function test_every_portfolio_project_has_a_detail_page(): void
    {
        foreach ([
            'mustard-tank',
            'jeepney-with-zeal-livery',
            'mazdaspeed-wheel-center-caps',
            'name-ornaments',
            'grey-mold',
            'threaded-canister',
            'lens-hood',
            'cat-and-bone-keychains',
            'faceted-christmas-tree',
            'two-colour-slogan-tags',
            'textured-and-stepped-rings',
            'small-batch-clips',
        ] as $slug) {
            $this->get(route('portfolio.show', $slug))->assertOk();
        }
    }

    public function test_unknown_portfolio_project_returns_not_found(): void
    {
        $this->get('/portfolio/not-a-project')->assertNotFound();
    }

    public function test_staff_login_pages_are_separate_and_use_the_shared_site_chrome(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin sign in')
            ->assertSee(route('admin.login.store'), false)
            ->assertSee('noindex, nofollow');

        $this->get('/superadmin/login')
            ->assertOk()
            ->assertSee('SuperAdmin sign in')
            ->assertSee(route('superadmin.login.store'), false)
            ->assertSee(route('admin.login'), false);
    }

    public function test_switching_staff_roles_requires_confirmation_and_signs_out_only_on_confirmation(): void
    {
        $admin = (new User)->forceFill([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('superadmin.login'))
            ->assertOk()
            ->assertSee('Sign out first?')
            ->assertSee('You’re signed in as Admin.')
            ->assertSee(route('staff.logout-and-switch', 'superadmin'), false)
            ->assertAuthenticatedAs($admin);

        $this->post(route('staff.logout-and-switch', 'superadmin'))
            ->assertRedirect(route('superadmin.login'))
            ->assertGuest();
    }

    public function test_staff_login_attempts_are_scoped_to_the_requested_role(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        Auth::partialMock()
            ->shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'admin@example.com',
                'password' => 'correct-horse-battery',
                'role' => 'admin',
            ])
            ->andReturnFalse();

        $this->from('/admin/login')->post('/admin/login', [
            'login_email' => 'admin@example.com',
            'login_password' => 'correct-horse-battery',
        ])->assertRedirect('/admin/login')
            ->assertSessionHasErrorsIn('staffLogin', 'login_email');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign-in failed. Check your email and password, then try again.')
            ->assertDontSee('class="form-errors"', false)
            ->assertSee('name="email" value=""', false);
    }

    public function test_staff_dashboards_require_the_matching_role(): void
    {
        $admin = (new User)->forceFill([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('aria-label="Staff management"', false)
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee('View website')
            ->assertDontSee(route('superadmin.site-settings.edit'), false);

        $this->get('/superadmin/dashboard')->assertForbidden();
        $this->post('/superadmin/admins', [])->assertForbidden();

        auth()->logout();

        $this->get('/superadmin/dashboard')
            ->assertRedirect(route('superadmin.login'));

        $superadmin = (new User)->forceFill([
            'id' => 2,
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $this->actingAs($superadmin)
            ->get('/superadmin/dashboard')
            ->assertOk()
            ->assertSee('SuperAdmin dashboard')
            ->assertSee('Nominate an Admin')
            ->assertSee(route('superadmin.site-settings.edit'), false)
            ->assertSee('Admin position / job title')
            ->assertSee(route('superadmin.admins.store'), false);

        $this->get('/admin/dashboard')->assertForbidden();

        $this->from('/superadmin/dashboard')
            ->post('/superadmin/admins', [])
            ->assertRedirect('/superadmin/dashboard')
            ->assertSessionHasErrorsIn('adminNomination', ['admin_name', 'admin_email', 'admin_position', 'admin_password']);
    }

    public function test_admin_accounts_cannot_be_bootstrapped_from_the_cli(): void
    {
        $this->artisan('staff:create', ['role' => 'admin'])
            ->expectsOutput('Admin accounts can only be nominated by an authenticated SuperAdmin.')
            ->assertExitCode(1);
    }

    public function test_superadmin_must_assign_a_position_when_nominating_an_admin(): void
    {
        $superadmin = (new User)->forceFill([
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'password' => 'safe-test-password',
            'role' => User::ROLE_SUPERADMIN,
        ]);
        $superadmin->save();

        $this->actingAs($superadmin)
            ->post(route('superadmin.admins.store'), [
                'admin_name' => 'New Admin',
                'admin_email' => 'new-admin@example.com',
                'admin_position' => 'Print Technician',
                'admin_password' => 'new-safe-password',
                'admin_password_confirmation' => 'new-safe-password',
            ])
            ->assertRedirect(route('superadmin.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'role' => User::ROLE_ADMIN,
            'position' => 'Print Technician',
        ]);

        $newAdmin = User::query()->where('email', 'new-admin@example.com')->firstOrFail();
        $this->actingAs($newAdmin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Print Technician');
    }

    public function test_staff_logout_ends_the_authenticated_session(): void
    {
        $admin = (new User)->forceFill([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_product_detail_pages_show_available_order_links(): void
    {
        $this->get('/products/custom-plate-number-keychain')
            ->assertOk()
            ->assertSee('Order on Facebook')
            ->assertSee('Order on Lazada')
            ->assertSee('Order on Shopee')
            ->assertSee('/products/i15685975834.html', false)
            ->assertSee('https://shopee.ph/product/1938462770/58121894792/', false);

        $this->get('/products/wall-mounted-automotive-key-holder')
            ->assertOk()
            ->assertSee('Order on Facebook')
            ->assertDontSee('Order on Lazada')
            ->assertDontSee('Order on Shopee');

        $this->get('/products/custom-christmas-ornaments')
            ->assertOk()
            ->assertSee('Order on Lazada')
            ->assertSee('Order on Shopee')
            ->assertSee('/products/i15724759226.html', false)
            ->assertSee('https://shopee.ph/product/1938462770/44036886317/', false);

        $this->get('/products/not-a-product')->assertNotFound();
    }

    public function test_admin_can_manage_content_and_inquiries_but_not_site_settings(): void
    {
        $admin = (new User)->forceFill([
            'id' => 3,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.portfolio.index'))
            ->assertOk()
            ->assertSee('Mustard Tank');
        $this->get(route('admin.portfolio.create'))->assertOk()->assertSee('Add a project');
        $this->get(route('admin.portfolio.edit', 'mustard-tank'))->assertOk()->assertSee('Edit project');

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Custom Plate Number Keychain');
        $this->get(route('admin.products.create'))->assertOk()->assertSee('Add a product');
        $this->get(route('admin.products.edit', 'custom-plate-number-keychain'))->assertOk()->assertSee('Edit product');

        $this->get(route('admin.inquiries.index'))->assertOk();
        $this->get(route('superadmin.site-settings.edit'))->assertForbidden();

        $this->from(route('admin.portfolio.create'))
            ->post(route('admin.portfolio.store'), [])
            ->assertRedirect(route('admin.portfolio.create'))
            ->assertSessionHasErrorsIn('portfolioContent', ['slug', 'title', 'status', 'description', 'work', 'images']);

        $this->get(route('admin.portfolio.create'))
            ->assertOk()
            ->assertDontSee('class="form-errors"', false);

        $this->put(route('admin.portfolio.update', 'mustard-tank'), [
            'slug' => 'mustard-tank',
            'title' => 'Mustard Tank Updated',
            'status' => 'Commissioned',
            'keywords' => 'Printed, Fine detail',
            'description' => 'Updated project description.',
            'work' => 'Updated work summary.',
        ])->assertRedirect(route('admin.portfolio.index'));

        $this->post(route('admin.products.store'), [
            'slug' => 'custom-test-product',
            'name' => 'Test Product',
            'category' => 'Test accessories',
            'description' => 'Product description.',
            'facebook_url' => 'https://facebook.com/example',
            'lazada_url' => '',
            'shopee_url' => '',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('portfolio_projects', [
            'slug' => 'mustard-tank',
            'title' => 'Mustard Tank Updated',
            'status' => 'Commissioned',
        ]);
        $this->assertDatabaseHas('products', [
            'slug' => 'custom-test-product',
            'name' => 'Test Product',
        ]);
    }

    public function test_superadmin_can_update_homepage_settings(): void
    {
        $superadmin = (new User)->forceFill([
            'id' => 4,
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'role' => User::ROLE_SUPERADMIN,
        ]);
        $settings = SiteSetting::defaults();
        unset($settings['logo_path']);
        $settings['hero_title_first'] = 'Make it personal';
        $settings['brand_primary'] = '#102030';
        $settings['brand_secondary'] = '#203040';
        $settings['use_second_main_color'] = '1';
        $settings['brand_accent'] = '#C94D20';
        $settings['show_portfolio'] = '0';
        $settings['show_products'] = '0';

        $this->actingAs($superadmin)
            ->get(route('superadmin.site-settings.edit'))
            ->assertOk()
            ->assertSee('Homepage &amp; branding', false)
            ->assertSee('Public sections')
            ->assertSee('name="show_portfolio"', false)
            ->assertSee('name="show_products"', false);

        $this->put(route('superadmin.site-settings.update'), $settings)
            ->assertRedirect(route('superadmin.site-settings.edit'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Make it personal')
            ->assertSee('--paper: #102030; --surface: #203040; --orange: #C94D20', false)
            ->assertDontSee(route('portfolio'), false)
            ->assertDontSee(route('products.index'), false)
            ->assertDontSee('Personalized Plate Keychains');
        $this->get(route('portfolio'))->assertNotFound();
        $this->get(route('portfolio.show', 'mustard-tank'))->assertNotFound();
        $this->get(route('products.index'))->assertNotFound();
        $this->get(route('products.show', 'custom-plate-number-keychain'))->assertNotFound();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand_accent',
            'value' => '#C94D20',
        ]);

        $singleColorSettings = SiteSetting::defaults();
        unset($singleColorSettings['logo_path'], $singleColorSettings['brand_secondary']);
        $singleColorSettings['brand_primary'] = '#334455';

        $this->put(route('superadmin.site-settings.update'), $singleColorSettings)
            ->assertRedirect(route('superadmin.site-settings.edit'));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'brand_secondary',
            'value' => '',
        ]);
        $this->get('/')
            ->assertOk()
            ->assertSee('--paper: #334455; --surface: #334455;', false);
    }

    public function test_superadmin_can_replace_the_logo_with_a_host_relative_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brand/old-logo.png', 'old logo');
        SiteSetting::query()->updateOrCreate(
            ['key' => 'logo_path'],
            ['value' => 'http://localhost/storage/brand/old-logo.png']
        );

        $superadmin = (new User)->forceFill([
            'id' => 4,
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'role' => User::ROLE_SUPERADMIN,
        ]);
        $settings = SiteSetting::defaults();
        unset($settings['logo_path']);
        $settings['use_second_main_color'] = '1';
        $settings['logo'] = UploadedFile::fake()->createWithContent(
            'replacement.png',
            file_get_contents(public_path('images/austral-layers-logo.png'))
        );

        $this->actingAs($superadmin)
            ->put(route('superadmin.site-settings.update'), $settings)
            ->assertRedirect(route('superadmin.site-settings.edit'))
            ->assertSessionHas('site_settings_saved');

        $logoPath = SiteSetting::query()->where('key', 'logo_path')->value('value');
        $this->assertMatchesRegularExpression('#^/storage/brand/[^/]+\.png$#', $logoPath);
        Storage::disk('public')->assertExists(substr($logoPath, strlen('/storage/')));
        Storage::disk('public')->assertMissing('brand/old-logo.png');
    }

    public function test_quote_inspiration_download_requires_staff_access(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent(
            'reference.pdf',
            "%PDF-1.4\nReference image notes\n%%EOF"
        );

        $this->post('/quote', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'social_media_link' => 'https://www.instagram.com/alex.profile',
            'service' => '3D Printing',
            'quantity' => '1–5 pieces',
            'message' => 'Please print this model.',
            'inspiration' => [$file],
        ])->assertRedirect('/');

        $inquiry = QuoteInquiry::query()->firstOrFail();
        $downloadUrl = route('admin.inquiries.attachments.show', [$inquiry, 0]);

        $this->get($downloadUrl)->assertRedirect(route('admin.login'));

        $admin = (new User)->forceFill([
            'id' => 5,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Project details')
            ->assertSee('https://www.instagram.com/alex.profile', false);

        $this->get(route('admin.inquiries.attachments.preview', [$inquiry, 0]))
            ->assertOk()
            ->assertSee('aria-label="Close attachment preview"', false)
            ->assertSee('href="'.route('admin.inquiries.show', $inquiry).'"', false)
            ->assertSee('Download')
            ->assertSee('<iframe', false);

        $this->patch(route('admin.inquiries.update', $inquiry), ['status' => 'contacted'])
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->get($downloadUrl)->assertOk();
        $this->assertDatabaseHas('quote_inquiries', [
            'id' => $inquiry->id,
            'status' => 'contacted',
        ]);
    }

    public function test_superadmin_sign_in_reaches_the_management_dashboard(): void
    {
        $superadmin = (new User)->forceFill([
            'name' => 'SuperAdmin User',
            'email' => 'superadmin@example.com',
            'password' => 'safe-test-password',
            'role' => User::ROLE_SUPERADMIN,
        ]);
        $superadmin->save();

        $this->post(route('superadmin.login.store'), [
            'login_email' => 'superadmin@example.com',
            'login_password' => 'safe-test-password',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Homepage &amp; branding', false)
            ->assertSee('Nominate an Admin');
    }

    public function test_admin_can_upload_photos_for_a_portfolio_project(): void
    {
        Storage::fake('public');
        $admin = (new User)->forceFill([
            'id' => 6,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.portfolio.store'), [
                'slug' => 'test-photo-project',
                'title' => 'Test Photo Project',
                'status' => 'Commissioned',
                'keywords' => 'Printed, Custom',
                'description' => 'A project used to check photo uploads.',
                'work' => 'Uploaded an example gallery photo.',
                'images' => [UploadedFile::fake()->create('project.jpg', 100, 'image/jpeg')],
            ])
            ->assertRedirect(route('admin.portfolio.index'));

        $project = PortfolioProject::query()
            ->where('slug', 'test-photo-project')
            ->firstOrFail();

        $this->assertCount(1, $project->images);
        $this->assertCount(1, Storage::disk('public')->allFiles('portfolio-projects'));
    }
}
