<?php

namespace Database\Seeders;

use App\Http\Controllers\HomeController;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HomeController::defaultPortfolioProjects() as $project) {
            PortfolioProject::query()->firstOrCreate(
                ['slug' => $project['slug']],
                $project
            );
        }

        foreach (HomeController::defaultCatalog() as $product) {
            Product::query()->firstOrCreate(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'category' => $product['category'],
                    'description' => $product['description'],
                    'images' => $product['image'] === null ? [] : [$product['image']],
                    'order_links' => $product['order_links'],
                ]
            );
        }

        foreach (SiteSetting::defaults() as $key => $value) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
