<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function defaults(): array
    {
        return [
            'brand_primary' => '#14201F',
            'brand_secondary' => '#1B2B29',
            'brand_accent' => '#FF5A1F',
            'brand_tagline' => 'Down to layer up?',
            'logo_path' => '/images/austral-layers-logo.png',
            'show_portfolio' => '1',
            'show_products' => '1',
            'hero_eyebrow' => 'Custom 3D printing & modeling studio',
            'hero_title_first' => 'Not everything',
            'hero_title_second' => 'comes off the shelf.',
            'hero_tagline' => 'Some things have to be made.',
            'hero_description' => 'At Austral Layers, we design, model, and 3D print objects that are a little more personal. From our own collection of products to one-of-a-kind custom projects, we make things worth creating.',
            'services_heading_first' => 'Some things',
            'services_heading_second' => 'need a personal touch.',
            'service_1_title' => 'Custom 3D Modeling',
            'service_1_description' => 'Can’t find the design you’re looking for? We create 3D models from references, sketches, and ideas, tailored to what you need.',
            'service_2_title' => '3D Printing',
            'service_2_description' => 'Already have a model? Send it our way. We turn digital designs into physical objects, one layer at a time.',
            'service_3_title' => 'Products & Personalization',
            'service_3_description' => 'Explore our own designs, or make them yours. From accessories and gifts to custom pieces, we create objects with a personal touch.',
            'studio_heading_first' => 'A little different.',
            'studio_heading_second' => 'By design.',
            'studio_paragraph_one' => 'We’re Austral Layers, a small 3D printing studio with a simple interest: making things that are worth making.',
            'studio_paragraph_two' => 'Some days we’re working on our next product. Other days we’re figuring out how to turn someone’s unusual idea into something real.',
            'studio_signoff' => 'We enjoy both.',
            'inquiry_heading_first' => 'Tell us what',
            'inquiry_heading_second' => 'you’re imagining.',
            'inquiry_description' => 'It doesn’t have to be a finished design. A sketch, a reference photo, or even a rough idea is enough to start a conversation.',
        ];
    }
}
