<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\CustomerNoteRevision;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerNoteController extends Controller
{
    public function index(Customer $customer)
    {
        $notes = CustomerNote::where('customer_id', $customer->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('customers.notes.index', compact('customer', 'notes'));
    }

    public function create(Customer $customer)
    {
        return view('customers.notes.create', compact('customer'));
    }

    public function edit(Customer $customer, CustomerNote $note)
    {
        $this->ensureOwnership($customer, $note);
        return view('customers.notes.edit', compact('customer', 'note'));
    }

    public function store(Request $request, Customer $customer, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $data = $this->validateNote($request);

        // Support both rich text (content) and legacy HTML
        $useRichText = !empty($data['content']);

        if ($useRichText) {
            // Rich Text Laravel handles sanitization automatically
            $plainText = strip_tags($data['content']);
        } else {
            // Legacy HTML support
            $cleanHtml = $sanitizer->sanitize($data['html'] ?? '');
            $plainText = trim(Str::of(strip_tags($cleanHtml))->squish()->value());
        }

        $note = CustomerNote::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'title' => $data['title'] ?? null,
            'html' => $useRichText ? null : ($cleanHtml ?? ''), // Legacy field
            'text' => $plainText,
            'tags' => $data['tags'],
            'color' => $data['color'] ?? null,
            'is_pinned' => $data['is_pinned'],
            'visibility' => $data['visibility'],
            'pinned_at' => $data['is_pinned'] ? now() : null,
        ]);

        // Set rich text content after creation (HasRichText trait handles this)
        if ($useRichText) {
            $note->content = $data['content'];
            $note->save();
        } else {
            $this->createRevision($note, $cleanHtml, $plainText, $data);
        }

        return redirect()
            ->route('customers.notes.index', $customer)
            ->with('success', 'Note saved successfully.');
    }

    public function update(Request $request, Customer $customer, CustomerNote $note, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $this->ensureOwnership($customer, $note);

        $data = $this->validateNote($request, true);

        // Support both rich text (content) and legacy HTML
        $useRichText = !empty($data['content']);

        if ($useRichText) {
            $plainText = strip_tags($data['content']);
            $note->content = $data['content']; // Rich text handles sanitization
        } else {
            $cleanHtml = $sanitizer->sanitize($data['html'] ?? '');
            $plainText = trim(Str::of(strip_tags($cleanHtml))->squish()->value());
            $note->html = $cleanHtml;
        }

        $note->fill([
            'title' => $data['title'] ?? null,
            'text' => $plainText,
            'tags' => $data['tags'],
            'color' => $data['color'] ?? null,
            'is_pinned' => $data['is_pinned'],
            'visibility' => $data['visibility'],
        ]);

        $note->pinned_at = $data['is_pinned']
            ? ($note->pinned_at ?? now())
            : null;

        $note->save();

        if (!$useRichText) {
            $this->createRevision($note, $cleanHtml ?? '', $plainText, $data);
        }

        return redirect()
            ->route('customers.notes.index', $customer)
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Customer $customer, CustomerNote $note): RedirectResponse
    {
        $this->ensureOwnership($customer, $note);

        $note->delete();

        return redirect()
            ->route('customers.notes.index', $customer)
            ->with('success', 'Note deleted successfully.');
    }

    protected function ensureOwnership(Customer $customer, CustomerNote $note): void
    {
        if ($note->customer_id !== $customer->id) {
            abort(404);
        }
    }

    protected function validateNote(Request $request, bool $isUpdate = false): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'html' => ['nullable', 'string', 'max:1000000'], // Legacy HTML support
            'content' => ['nullable', 'string', 'max:1000000'], // Rich text content
            'visibility' => ['required', 'in:private,team,organization'],
            'tags' => ['nullable', 'string', 'max:5000'], // Limit tags JSON size
            'color' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9A-Fa-f]{6}$/'], // Valid hex color
            'is_pinned' => ['sometimes', 'boolean'],
        ], [
            'html.max' => 'Note content is too large. Please reduce the amount of content or images.',
            'content.max' => 'Note content is too large. Please reduce the amount of content or images.',
            'tags.max' => 'Too many tags. Please use fewer tags.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #ff0000).',
        ]);

        $tags = [];
        if (!empty($validated['tags'])) {
            $decoded = json_decode($validated['tags'], true);
            if (is_array($decoded)) {
                // Limit to 20 tags max, 40 chars each
                $tags = array_slice(
                    array_values(array_unique(array_filter(
                        array_map(static fn ($tag) => Str::limit(trim((string) $tag), 40, ''), $decoded)
                    ))),
                    0,
                    20
                );
            }
        }

        return [
            'title' => $validated['title'] ?? null,
            'html' => $validated['html'] ?? null,
            'content' => $validated['content'] ?? null,
            'visibility' => $validated['visibility'],
            'tags' => $tags,
            'color' => $validated['color'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
        ];
    }

    protected function createRevision(CustomerNote $note, string $cleanHtml, string $plainText, array $data): void
    {
        CustomerNoteRevision::create([
            'customer_note_id' => $note->id,
            'customer_id' => $note->customer_id,
            'user_id' => Auth::id(),
            'title' => $data['title'] ?? null,
            'html' => $cleanHtml,
            'text' => $plainText,
            'tags' => $data['tags'],
            'color' => $data['color'] ?? null,
            'visibility' => $data['visibility'],
        ]);
    }
}
