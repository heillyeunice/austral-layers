<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

class ProductManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('productContent', $this->rules());
        $product = new Product;
        $this->fillProduct($product, $validated, $request);
        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('product_saved', 'Product created.');
    }

    public function edit(string $slug): View
    {
        return view('admin.products.form', [
            'product' => Product::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $validated = $request->validateWithBag('productContent', $this->rules($product));
        $oldImages = $product->images ?? [];
        $this->fillProduct($product, $validated, $request);
        $product->save();

        if ($request->hasFile('images') || $request->boolean('remove_images')) {
            PublicImageStorage::delete($oldImages, 'product-photos');
        }

        return redirect()
            ->route('admin.products.index')
            ->with('product_saved', 'Product updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $images = $product->images ?? [];
        $product->delete();

        PublicImageStorage::delete($images, 'product-photos');

        return redirect()
            ->route('admin.products.index')
            ->with('product_saved', 'Product deleted.');
    }

    /**
     * @return array<string, array<int, string|Unique>>
     */
    private function rules(?Product $product = null): array
    {
        return [
            'slug' => ['required', 'alpha_dash', 'max:180', Rule::unique('products', 'slug')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'lazada_url' => ['nullable', 'url:http,https', 'max:2048'],
            'shopee_url' => ['nullable', 'url:http,https', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['required', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif', 'max:20480'],
            'remove_images' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillProduct(Product $product, array $validated, Request $request): void
    {
        $images = $product->images ?? [];

        if ($request->hasFile('images')) {
            $images = PublicImageStorage::store($request->file('images'), 'product-photos');
        } elseif ($request->boolean('remove_images')) {
            $images = [];
        }

        $orderLinks = array_filter([
            'Facebook' => $validated['facebook_url'] ?? null,
            'Lazada' => $validated['lazada_url'] ?? null,
            'Shopee' => $validated['shopee_url'] ?? null,
        ], static fn (?string $url): bool => filled($url));

        $product->fill([
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'images' => array_values($images),
            'order_links' => $orderLinks,
        ]);
    }
}
