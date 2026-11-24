<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class StaffProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.staff-profile', [
            'user' => $request->user(),
            'noIndex' => true,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validateWithBag('staffProfile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'required_with:current_password', 'string', 'min:12', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.profile.edit')
            ->with('staff_profile_saved', 'Your profile has been updated.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $request->validateWithBag('staffProfile', [
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $oldPhotoPath = $user->profile_photo_path;
        $path = $request->file('profile_photo')->store('staff-profile-photos', 'local');

        if ($path === false) {
            throw new RuntimeException('Failed to store the profile photo.');
        }

        $user->profile_photo_path = $path;
        $user->save();

        if (is_string($oldPhotoPath) && str_starts_with($oldPhotoPath, 'staff-profile-photos/')) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return redirect()
            ->route('admin.profile.edit')
            ->with('staff_profile_saved', 'Your profile photo has been updated.');
    }

    public function photo(Request $request): Response
    {
        $path = $request->user()->profile_photo_path;

        abort_unless(
            is_string($path)
                && str_starts_with($path, 'staff-profile-photos/')
                && Storage::disk('local')->exists($path),
            404
        );

        return Storage::disk('local')->response($path, headers: ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
