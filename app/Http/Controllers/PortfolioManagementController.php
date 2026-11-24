<?php

namespace App\Http\Controllers;

use App\Models\PortfolioProject;
use App\Support\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortfolioManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.portfolio.index', [
            'projects' => PortfolioProject::query()->orderBy('title')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.portfolio.form', ['project' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('portfolioContent', [
            'slug' => ['required', 'alpha_dash', 'max:180', 'unique:portfolio_projects,slug'],
            'title' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(['Commissioned', 'In-house'])],
            'keywords' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'work' => ['required', 'string', 'max:2000'],
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => ['required', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif', 'max:20480'],
        ]);

        $project = new PortfolioProject;
        $this->fillProject($project, $validated, $request->file('images'));
        $project->save();

        return redirect()
            ->route('admin.portfolio.index')
            ->with('portfolio_saved', 'Portfolio project created.');
    }

    public function edit(string $slug): View
    {
        return view('admin.portfolio.form', [
            'project' => PortfolioProject::query()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $project = PortfolioProject::query()->where('slug', $slug)->firstOrFail();
        $validated = $request->validateWithBag('portfolioContent', [
            'slug' => ['required', 'alpha_dash', 'max:180', Rule::unique('portfolio_projects', 'slug')->ignore($project->id)],
            'title' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(['Commissioned', 'In-house'])],
            'keywords' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'work' => ['required', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['required', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif', 'max:20480'],
        ]);

        $oldImages = $project->images ?? [];
        $this->fillProject($project, $validated, $request->file('images', []));
        $project->save();

        if ($request->hasFile('images')) {
            PublicImageStorage::delete(
                array_column($oldImages, 'src'),
                'portfolio-projects'
            );
        }

        return redirect()
            ->route('admin.portfolio.index')
            ->with('portfolio_saved', 'Portfolio project updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $project = PortfolioProject::query()->where('slug', $slug)->firstOrFail();
        $images = $project->images ?? [];
        $project->delete();

        PublicImageStorage::delete(array_column($images, 'src'), 'portfolio-projects');

        return redirect()
            ->route('admin.portfolio.index')
            ->with('portfolio_saved', 'Portfolio project deleted.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>|null  $uploads
     */
    private function fillProject(PortfolioProject $project, array $validated, ?array $uploads): void
    {
        $images = $project->images ?? [];

        if ($uploads !== null && $uploads !== []) {
            $urls = PublicImageStorage::store($uploads, 'portfolio-projects');
            $images = [];

            foreach ($urls as $index => $url) {
                $images[] = [
                    'src' => $url,
                    'alt' => $validated['title'],
                    'caption' => 'Photo '.($index + 1),
                ];
            }
        }

        $keywords = collect(explode(',', $validated['keywords'] ?? ''))
            ->map(static fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $project->fill([
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'status' => $validated['status'],
            'keywords' => $keywords,
            'description' => $validated['description'],
            'work' => $validated['work'],
            'images' => $images,
        ]);
    }
}
