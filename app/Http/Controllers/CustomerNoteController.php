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
    public function store(Request $request, Customer $customer, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $data = $this->validateNote($request);

        $cleanHtml = $sanitizer->sanitize($data['html']);
        $plainText = trim(Str::of(strip_tags($cleanHtml))->squish()->value());

        $note = CustomerNote::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'title' => $data['title'] ?? null,
            'html' => $cleanHtml,
            'text' => $plainText,
            'tags' => $data['tags'],
            'color' => $data['color'] ?? null,
            'is_pinned' => $data['is_pinned'],
            'visibility' => $data['visibility'],
            'pinned_at' => $data['is_pinned'] ? now() : null,
        ]);

        $this->createRevision($note, $cleanHtml, $plainText, $data);

        return redirect()
            ->to(route('customers.profile', $customer) . '#notes')
            ->with('success', 'Note saved successfully.');
    }

    public function update(Request $request, Customer $customer, CustomerNote $note, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $this->ensureOwnership($customer, $note);

        $data = $this->validateNote($request, true);
        $cleanHtml = $sanitizer->sanitize($data['html']);
        $plainText = trim(Str::of(strip_tags($cleanHtml))->squish()->value());

        $note->fill([
            'title' => $data['title'] ?? null,
            'html' => $cleanHtml,
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

        $this->createRevision($note, $cleanHtml, $plainText, $data);

        return redirect()
            ->to(route('customers.profile', $customer) . '#notes')
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Customer $customer, CustomerNote $note): RedirectResponse
    {
        $this->ensureOwnership($customer, $note);

        $note->delete();

        return redirect()
            ->to(route('customers.profile', $customer) . '#notes')
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
            'html' => ['required', 'string'],
            'visibility' => ['required', 'in:private,team,organization'],
            'tags' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:32'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        $tags = [];
        if (!empty($validated['tags'])) {
            $decoded = json_decode($validated['tags'], true);
            if (is_array($decoded)) {
                $tags = array_values(array_unique(array_filter(array_map(static fn ($tag) => Str::limit(trim((string) $tag), 40, ''), $decoded))));
            }
        }

        return [
            'title' => $validated['title'] ?? null,
            'html' => $validated['html'],
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
