<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => array_replace(
                SiteSetting::defaults(),
                SiteSetting::query()->pluck('value', 'key')->all()
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = array_fill_keys(
            array_keys(SiteSetting::defaults()),
            ['required', 'string', 'max:2000']
        );
        unset($rules['logo_path']);
        $rules['brand_primary'] = ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'];
        $rules['brand_secondary'] = $request->boolean('use_second_main_color')
            ? ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/']
            : ['nullable', 'regex:/\A#[0-9A-Fa-f]{6}\z/'];
        $rules['brand_accent'] = ['required', 'regex:/\A#[0-9A-Fa-f]{6}\z/'];
        $rules['show_portfolio'] = ['required', 'boolean'];
        $rules['show_products'] = ['required', 'boolean'];
        $rules['logo'] = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'];

        $validated = $request->validateWithBag('siteSettings', $rules);
        $oldLogo = SiteSetting::query()->where('key', 'logo_path')->value('value');
        $values = collect($validated)->except('logo')->all();
        $values['brand_secondary'] = $request->boolean('use_second_main_color')
            ? $validated['brand_secondary']
            : '';

        if ($request->hasFile('logo')) {
            $values['logo_path'] = PublicImageStorage::store([$request->file('logo')], 'brand')[0];
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                SiteSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        });

        if (isset($values['logo_path']) && $oldLogo !== null) {
            PublicImageStorage::delete([$oldLogo], 'brand');
        }

        return redirect()
            ->route('superadmin.site-settings.edit')
            ->with('site_settings_saved', 'Homepage and branding settings saved.');
    }
}
