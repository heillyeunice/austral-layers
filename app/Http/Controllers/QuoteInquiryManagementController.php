<?php

namespace App\Http\Controllers;

use App\Models\QuoteInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteInquiryManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $query = QuoteInquiry::query()->latest();

        if (in_array($status, ['new', 'contacted', 'closed'], true)) {
            $query->where('status', $status);
        } else {
            $status = null;
        }

        return view('admin.inquiries.index', [
            'inquiries' => $query->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(QuoteInquiry $inquiry): View
    {
        return view('admin.inquiries.show', ['inquiry' => $inquiry]);
    }

    public function update(Request $request, QuoteInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validateWithBag('inquiryStatus', [
            'status' => ['required', Rule::in(['new', 'contacted', 'closed'])],
        ]);

        $inquiry->update($validated);

        return redirect()
            ->route('admin.inquiries.show', $inquiry)
            ->with('inquiry_updated', 'Inquiry status updated.');
    }

    public function attachment(QuoteInquiry $inquiry, int $fileIndex): StreamedResponse
    {
        return Storage::disk('local')->download($this->inspirationPath($inquiry, $fileIndex));
    }

    public function attachmentPreview(QuoteInquiry $inquiry, int $fileIndex): View
    {
        $path = $this->inspirationPath($inquiry, $fileIndex);

        return view('admin.attachments.preview', [
            'fileName' => basename($path),
            'mimeType' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
            'contentUrl' => route('admin.inquiries.attachments.preview-content', [$inquiry, $fileIndex]),
            'downloadUrl' => route('admin.inquiries.attachments.show', [$inquiry, $fileIndex]),
            'returnUrl' => route('admin.inquiries.show', $inquiry),
            'noIndex' => true,
        ]);
    }

    public function attachmentPreviewContent(QuoteInquiry $inquiry, int $fileIndex): StreamedResponse
    {
        $path = $this->inspirationPath($inquiry, $fileIndex);

        return Storage::disk('local')->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
        ], 'inline');
    }

    private function inspirationPath(QuoteInquiry $inquiry, int $fileIndex): string
    {
        $path = $inquiry->inspiration_files[$fileIndex] ?? null;

        abort_unless(
            is_string($path)
                && str_starts_with($path, 'quote-inspiration/')
                && Storage::disk('local')->exists($path),
            404
        );

        return $path;
    }
}
