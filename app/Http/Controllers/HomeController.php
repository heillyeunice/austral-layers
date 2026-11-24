<?php

namespace App\Http\Controllers;

use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\QuoteInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\NewQuoteInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }

    public function portfolio(): View
    {
        abort_unless($this->publicSectionEnabled('show_portfolio'), 404);

        $projects = PortfolioProject::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (PortfolioProject $project): array => $project->toArray())
            ->all();

        return view('portfolio.index', [
            'projects' => $projects,
            'stats' => [
                'projects' => count($projects),
                'commissioned' => count(array_filter($projects, fn (array $project): bool => $project['status'] === 'Commissioned')),
                'fine_detail' => count(array_filter($projects, fn (array $project): bool => in_array('0.2 mm nozzle', $project['keywords'], true))),
                'cad_models' => count(array_filter($projects, fn (array $project): bool => in_array('3D modelled', $project['keywords'], true))),
            ],
        ]);
    }

    public function portfolioProject(string $slug): View
    {
        abort_unless($this->publicSectionEnabled('show_portfolio'), 404);

        $project = PortfolioProject::query()->where('slug', $slug)->first();

        abort_if($project === null, 404);

        return view('portfolio.show', ['project' => $project->toArray()]);
    }

    public function products(): View
    {
        abort_unless($this->publicSectionEnabled('show_products'), 404);

        $products = Product::query()
            ->orderBy('id')
            ->get()
            ->map(static function (Product $product): array {
                $data = $product->toArray();
                $data['images'] = $data['images'] ?? [];
                $data['image'] = $data['images'][0] ?? null;

                return $data;
            })
            ->all();

        return view('products.index', ['products' => $products]);
    }

    public function product(string $slug): View
    {
        abort_unless($this->publicSectionEnabled('show_products'), 404);

        $product = Product::query()->where('slug', $slug)->first();

        abort_if($product === null, 404);

        $data = $product->toArray();
        $data['images'] = $data['images'] ?? [];
        $data['image'] = $data['images'][0] ?? null;

        return view('products.show', ['product' => $data]);
    }

    public function quote(Request $request): RedirectResponse
    {
        $socialMediaLink = $request->input('social_media_link');
        $socialMediaLink = is_string($socialMediaLink) ? trim($socialMediaLink) : '';

        if ($socialMediaLink !== '' && ! preg_match('/\Ahttps?:\/\//i', $socialMediaLink)) {
            $socialMediaLink = 'https://'.$socialMediaLink;
        }

        $request->merge(['social_media_link' => $socialMediaLink]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:100'],
            'social_media_link' => ['required', 'string', 'max:2048', 'url:http,https'],
            'service' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
            'inspiration' => ['nullable', 'array', 'max:5'],
            'inspiration.*' => ['file', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif,pdf', 'max:20480'],
        ]);

        $inspirationFiles = [];

        foreach ($request->file('inspiration', []) as $file) {
            $path = $file->store('quote-inspiration', 'local');

            if ($path === false) {
                throw new \RuntimeException('Failed to store a quote inspiration file.');
            }

            $inspirationFiles[] = $path;
        }

        $inquiry = QuoteInquiry::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'social_media_link' => $validated['social_media_link'],
            'service' => $validated['service'],
            'quantity' => $validated['quantity'],
            'message' => $validated['message'],
            'inspiration_files' => $inspirationFiles,
        ]);

        User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get()
            ->each(static fn (User $staffMember) => $staffMember->notify(new NewQuoteInquiry($inquiry)));

        return redirect()->route('home')
            ->with('quote_success', true)
            ->with('quote_name', $validated['name']);
    }

    public static function defaultCatalog(): array
    {
        $facebook = 'https://www.facebook.com/profile.php?id=61595552973378';

        return [
            'custom-plate-number-keychain' => [
                'slug' => 'custom-plate-number-keychain',
                'name' => 'Custom Plate Number Keychain',
                'category' => 'Personalized accessories',
                'image' => '/images/personalized-keychains.png',
                'description' => 'An everyday keychain made personal with your own plate number.',
                'order_links' => [
                    'Facebook' => $facebook,
                    'Lazada' => 'https://www.lazada.com.ph/products/i15685975834.html?spm=a1zawj.24863640.table_online_product.7.7c441e13uEDxo0',
                    'Shopee' => 'https://shopee.ph/product/1938462770/58121894792/',
                ],
            ],
            'wall-mounted-automotive-key-holder' => [
                'slug' => 'wall-mounted-automotive-key-holder',
                'name' => 'Wall Mounted Automotive Key Holder',
                'category' => 'Home accessories',
                'image' => '/images/automotive-key-holders.png',
                'description' => 'A wall-mounted display and key holder inspired by your favorite car.',
                'order_links' => [
                    'Facebook' => $facebook,
                ],
            ],
            'custom-christmas-ornaments' => [
                'slug' => 'custom-christmas-ornaments',
                'name' => 'Custom Christmas Ornaments',
                'category' => 'Seasonal gifts',
                'image' => null,
                'description' => 'Personalized Christmas ornaments, made to add a little Austral Layers touch to your celebrations.',
                'order_links' => [
                    'Facebook' => $facebook,
                    'Lazada' => 'https://www.lazada.com.ph/products/i15724759226.html?spm=a1zawj.24863640.table_online_product.4.7c441e13uEDxo0',
                    'Shopee' => 'https://shopee.ph/product/1938462770/44036886317/',
                ],
            ],
        ];
    }

    private function publicSectionEnabled(string $key): bool
    {
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return ($value ?? SiteSetting::defaults()[$key]) === '1';
    }

    public static function defaultPortfolioProjects(): array
    {
        return [
            'mustard-tank' => [
                'slug' => 'mustard-tank',
                'title' => 'Mustard Tank',
                'status' => 'In-house',
                'keywords' => ['Printed', '0.2 mm nozzle', 'Fine detail'],
                'description' => 'A scale tank miniature in mustard-yellow filament. Track links, hatches, engine-deck grilles and tow hooks hold their shape at this size, which is what the 0.2 mm nozzle is for.',
                'work' => 'Printed the miniature in mustard-yellow filament with a 0.2 mm nozzle, preserving the fine track links, hatches, grilles and tow hooks.',
                'images' => [
                    ['src' => 'images/portfolio/tank-front.jpg', 'alt' => 'Mustard-yellow printed tank miniature on a concrete ledge, front three-quarter view', 'caption' => 'Front three-quarter view'],
                    ['src' => 'images/portfolio/tank-rear.jpg', 'alt' => 'Mustard-yellow printed tank miniature seen from the rear and above', 'caption' => 'Rear and turret view'],
                ],
            ],
            'jeepney-with-zeal-livery' => [
                'slug' => 'jeepney-with-zeal-livery',
                'title' => 'Jeepney with ZEAL livery',
                'status' => 'Commissioned',
                'keywords' => ['3D modelled', 'Printed', 'Multi-colour'],
                'description' => 'A jeepney modelled in CAD and printed in navy, cyan, grey and white, with ZEAL and USA–Philippines lettering and a sun motif on the roof. Shown as the render and the colour-sorted print plate.',
                'work' => 'Modelled the jeepney in CAD, prepared its colour-sorted print plate, and printed it in navy, cyan, grey and white.',
                'images' => [
                    ['src' => 'images/portfolio/jeepney-render.jpg', 'alt' => 'CAD render of a blue jeepney with ZEAL and USA-Philippines lettering', 'caption' => 'CAD render'],
                    ['src' => 'images/portfolio/jeepney-plate.jpg', 'alt' => 'Slicer view with the jeepney body, chassis parts and colour blocks laid out on the print plate', 'caption' => 'Print plate in the slicer'],
                ],
            ],
            'mazdaspeed-wheel-center-caps' => [
                'slug' => 'mazdaspeed-wheel-center-caps',
                'title' => 'Mazdaspeed wheel center caps',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Epoxy resin finish'],
                'description' => 'Center caps for wheels, printed in black and white and finished with a clear epoxy resin dome for a glossy top. A plate-style keychain got the same coating in the same batch.',
                'work' => 'Printed the center caps in black and white, then applied a clear epoxy resin dome finish. A matching plate-style keychain received the same coating in the batch.',
                'images' => [
                    ['src' => 'images/portfolio/caps-set.jpg', 'alt' => 'Four black and white printed wheel center caps with a glossy epoxy resin coating, next to a resin-coated plate-style keychain', 'caption' => 'Coated caps on the print bed'],
                    ['src' => 'images/portfolio/caps-close.jpg', 'alt' => 'Close-up of two glossy resin-coated center caps with a white line logo on black', 'caption' => 'Close-up of the resin finish'],
                ],
            ],
            'name-ornaments' => [
                'slug' => 'name-ornaments',
                'title' => 'Name ornaments',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Personalised', 'Seasonal'],
                'description' => 'White name ornaments in script lettering inside a ring of snowflakes, each with a silver cord to hang it. Packed in a box with a handwritten thank-you card.',
                'work' => 'Printed white script names inside snowflake rings, added silver hanging cords, and packed the ornaments with a handwritten thank-you card.',
                'images' => [
                    ['src' => 'images/portfolio/ornaments.jpg', 'alt' => 'Open box of white 3D-printed snowflake ornaments with script names, packed in black and white paper shred with a holiday card', 'caption' => 'Packed and ready to ship'],
                ],
            ],
            'grey-mold' => [
                'slug' => 'grey-mold',
                'title' => 'Grey Mold',
                'status' => 'In-house',
                'keywords' => ['Printed', '0.2 mm nozzle', 'Fine detail'],
                'description' => 'A mold part with four standing pins, a recessed cavity and a cross-shaped stem. Printed with the 0.2 mm nozzle so the pins and the small stem stay sharp.',
                'work' => 'Printed the mold with a 0.2 mm nozzle, keeping its four pins, recessed cavity and cross-shaped stem crisp.',
                'images' => [
                    ['src' => 'images/portfolio/mold.jpg', 'alt' => 'Grey printed mold part with four upright pins and a cross-shaped stem in the cavity, on a cutting mat', 'caption' => 'Pins and cavity'],
                ],
            ],
            'threaded-canister' => [
                'slug' => 'threaded-canister',
                'title' => 'Threaded canister',
                'status' => 'Commissioned',
                'keywords' => ['3D modelled', 'CAD design'],
                'description' => 'A cylindrical housing modelled in CAD: two square bays split by a central web, side windows, clip bands on the outside and a threaded neck. Shown as the sketch, a cross-section and the assembled view.',
                'work' => 'Developed the CAD model from section sketch through cross-section to assembly, including two square bays, a central web, side windows, clip bands and a threaded neck.',
                'images' => [
                    ['src' => 'images/portfolio/canister-sketch.jpg', 'alt' => 'CAD view of a green cylinder with an I-shaped section sketch being cut', 'caption' => 'Section sketch'],
                    ['src' => 'images/portfolio/canister-section.jpg', 'alt' => 'Cross-section of the green cylinder showing two square internal bays', 'caption' => 'Cross-section'],
                    ['src' => 'images/portfolio/canister-assembly.jpg', 'alt' => 'Green cylinder with white clip bands and a black threaded neck', 'caption' => 'Assembled view'],
                ],
            ],
            'lens-hood' => [
                'slug' => 'lens-hood',
                'title' => 'Lens hood',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Functional part'],
                'description' => 'A black lens hood with an internal thread for mounting. Shown from the side and looking into the threaded end.',
                'work' => 'Printed a black lens hood with an internal mounting thread, and checked both the exterior and threaded end.',
                'images' => [
                    ['src' => 'images/portfolio/hood-side.jpg', 'alt' => 'Black printed lens hood held in a hand over a cutting mat, side view', 'caption' => 'Side view'],
                    ['src' => 'images/portfolio/hood-thread.jpg', 'alt' => 'Inside of the black lens hood showing the printed internal thread', 'caption' => 'Threaded end'],
                ],
            ],
            'cat-and-bone-keychains' => [
                'slug' => 'cat-and-bone-keychains',
                'title' => 'Cat and bone keychains',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Keychains'],
                'description' => 'Round keychains finished in blue, with a cat-face recess, a bone-shaped window and a swivel clasp.',
                'work' => 'Printed a pair of blue keychains with a cat-face recess and bone-shaped window, then fitted each with a swivel clasp.',
                'images' => [
                    ['src' => 'images/portfolio/keychains.jpg', 'alt' => 'Two round blue keychains with a cat-face recess and a bone-shaped window, each with a metal clasp', 'caption' => 'Finished pair'],
                ],
            ],
            'faceted-christmas-tree' => [
                'slug' => 'faceted-christmas-tree',
                'title' => 'Faceted Christmas tree',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Seasonal'],
                'description' => 'A green Christmas tree built from stacked, star-shaped tiers, photographed partway through the print.',
                'work' => 'Printed a green faceted tree from stacked, star-shaped tiers.',
                'images' => [
                    ['src' => 'images/portfolio/tree.jpg', 'alt' => 'Green faceted Christmas tree half printed on a 3D printer bed', 'caption' => 'Mid-print'],
                ],
            ],
            'two-colour-slogan-tags' => [
                'slug' => 'two-colour-slogan-tags',
                'title' => 'Two-colour slogan tags',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Two-colour', 'Batch'],
                'description' => 'A batch of nine two-colour tags with raised white lettering on a black base, printed in one run.',
                'work' => 'Printed nine two-colour tags in one run, with raised white lettering on black bases.',
                'images' => [
                    ['src' => 'images/portfolio/tags.jpg', 'alt' => 'Nine black and white printed slogan tags laid out in two rows on a cutting mat', 'caption' => 'Batch of nine'],
                ],
            ],
            'textured-and-stepped-rings' => [
                'slug' => 'textured-and-stepped-rings',
                'title' => 'Textured and stepped rings',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Texture', 'Finish'],
                'description' => 'Rings in two finishes: a pebbled, textured face, and a smooth profile with a stepped lip.',
                'work' => 'Printed and compared two ring finishes: a pebbled texture and a smooth profile with a stepped lip.',
                'images' => [
                    ['src' => 'images/portfolio/rings-textured.jpg', 'alt' => 'Two dark printed rings with a pebbled textured surface', 'caption' => 'Textured finish'],
                    ['src' => 'images/portfolio/rings-stepped.jpg', 'alt' => 'Two dark printed rings, one flat with a stepped lip and one dished', 'caption' => 'Stepped lip'],
                ],
            ],
            'small-batch-clips' => [
                'slug' => 'small-batch-clips',
                'title' => 'Small-batch clips',
                'status' => 'Commissioned',
                'keywords' => ['Printed', 'Batch production'],
                'description' => 'A full tray of small black clip parts from one batch, laid out for finishing and checking.',
                'work' => 'Printed a full tray of small black clips in one batch, then laid them out for finishing and inspection.',
                'images' => [
                    ['src' => 'images/portfolio/clips.jpg', 'alt' => 'Rows of small black printed clip parts on a cutting mat', 'caption' => 'One batch'],
                ],
            ],
        ];
    }
}
