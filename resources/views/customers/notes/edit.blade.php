@extends('layouts.app')

@section('title', __('Edit Note for :name', ['name' => $customer->name]))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white/80 backdrop-blur-sm sticky top-0 z-10 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('customers.profile', $customer) }}#notes"
                       class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Customer
                    </a>
                    <div class="h-6 w-px bg-gray-300"></div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Edit Note</h1>
                        <p class="text-sm text-gray-500">for {{ $customer->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" form="note-form"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500 hover:shadow-emerald-600/50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Note
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-6 py-4">
                <div class="flex items-start gap-3">
                    <svg class="h-6 w-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-red-800">Please correct the following errors:</h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form id="note-form" method="POST" action="{{ route('customers.notes.update', $note) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <input
                    type="text"
                    name="title"
                    id="note-title"
                    value="{{ old('title', $note->title) }}"
                    placeholder="Note title (optional)"
                    class="w-full border-0 bg-transparent text-3xl font-bold text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-0"
                    autocomplete="off"
                >
            </div>

            <!-- Rich Text Editor -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6">
                    <label for="note-content" class="block text-sm font-medium text-gray-700 mb-3">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <x-trix-input
                        id="note-content"
                        name="content"
                        value="{{ old('content', $note->content ?? $note->html) }}"
                        placeholder="Start writing your note..."
                        class="trix-content" style="min-height: 500px;"
                    />
                </div>
            </div>

            <!-- Metadata Panel -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Tags -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Tags
                    </label>
                    <div id="note-tags" class="flex min-h-[44px] flex-wrap gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 focus-within:border-emerald-500 focus-within:bg-white">
                        <input type="text"
                               id="note-tag-input"
                               class="flex-1 border-none bg-transparent text-sm outline-none"
                               placeholder="Add tags (press Enter)">
                    </div>
                    <input type="hidden" id="note-tags-hidden" name="tags" value="">
                    <p class="mt-2 text-xs text-gray-500">Press Enter to add tags. Max 20 tags.</p>
                </div>

                <!-- Settings -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Settings
                    </label>
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="is_pinned"
                                   value="1"
                                   {{ old('is_pinned', $note->is_pinned) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">Pin this note</span>
                        </label>

                        <div>
                            <label class="block text-xs text-gray-600 mb-2">Visibility</label>
                            <select name="visibility"
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="private" {{ old('visibility', $note->visibility) === 'private' ? 'selected' : '' }}>🔒 Private</option>
                                <option value="team" {{ old('visibility', $note->visibility) === 'team' ? 'selected' : '' }}>👥 Team</option>
                                <option value="organization" {{ old('visibility', $note->visibility) === 'organization' ? 'selected' : '' }}>🏢 Organization</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Color -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        Note Color
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="color"
                               name="color"
                               value="{{ old('color', $note->color ?? '#f8fafc') }}"
                               id="note-color"
                               class="h-12 w-20 cursor-pointer rounded-lg border-2 border-gray-200">
                        <div class="flex-1">
                            <p class="text-xs text-gray-500">Choose a color to help categorize this note</p>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-slate-100" data-color="#f8fafc"></button>
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-red-100" data-color="#fee2e2"></button>
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-blue-100" data-color="#dbeafe"></button>
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-green-100" data-color="#d1fae5"></button>
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-yellow-100" data-color="#fef3c7"></button>
                        <button type="button" class="color-preset h-8 w-8 rounded-full border-2 border-gray-200 bg-purple-100" data-color="#ede9fe"></button>
                    </div>
                </div>
            </div>

            <!-- Auto-save hint -->
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-800">
                <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Your work is automatically saved as a draft every 5 seconds.
            </div>
        </form>
    </div>
</div>

<x-rich-text::styles theme="richtextlaravel" />
@push('styles')
<style>
    /* Make Trix editor more spacious and comfortable */
    trix-editor {
        min-height: 500px !important;
        max-height: 70vh;
        overflow-y: auto;
        padding: 1.5rem !important;
        font-size: 1rem;
        line-height: 1.75;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        background: #ffffff;
    }

    trix-editor:focus {
        outline: none;
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    /* Toolbar styling */
    trix-toolbar {
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        background: #f9fafb;
        padding: 0.75rem !important;
        margin-bottom: 1rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    trix-toolbar .trix-button-group {
        border: none !important;
        margin-bottom: 0;
    }

    trix-toolbar .trix-button {
        border: 1px solid #d1d5db !important;
        background: #ffffff !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem !important;
        margin: 0 0.25rem;
        transition: all 0.2s;
    }

    trix-toolbar .trix-button:hover {
        background: #f3f4f6 !important;
        border-color: #10b981 !important;
    }

    trix-toolbar .trix-button.trix-active {
        background: #10b981 !important;
        color: white !important;
        border-color: #10b981 !important;
    }

    /* Content styling */
    trix-editor h1 {
        font-size: 2em;
        font-weight: 700;
        margin: 1em 0 0.5em;
    }

    trix-editor h2 {
        font-size: 1.5em;
        font-weight: 600;
        margin: 0.83em 0;
    }

    trix-editor ul, trix-editor ol {
        padding-left: 2em;
        margin: 1em 0;
    }

    trix-editor blockquote {
        border-left: 4px solid #10b981;
        padding-left: 1em;
        margin: 1em 0;
        color: #6b7280;
        font-style: italic;
    }

    trix-editor pre {
        background: #f3f4f6;
        padding: 1em;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 1em 0;
    }

    trix-editor a {
        color: #10b981;
        text-decoration: underline;
    }

    trix-editor img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        margin: 1em 0;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const form = document.getElementById('note-form');
    const tagsHidden = document.getElementById('note-tags-hidden');
    const tagsContainer = document.getElementById('note-tags');
    const tagInput = document.getElementById('note-tag-input');
    const colorPresets = document.querySelectorAll('.color-preset');
    const colorInput = document.getElementById('note-color');
    const storageKey = 'customer-note-draft-{{ $customer->id }}-note-{{ $note->id }}';

    let tags = @json($note->tags ?? []);

    // Tag management
    function renderTags() {
        if (!tagsContainer) return;
        tagsContainer.querySelectorAll('[data-tag-item]').forEach(el => el.remove());
        tags.forEach((tag, idx) => {
            const pill = document.createElement('span');
            pill.dataset.tagItem = idx;
            pill.className = 'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 border border-emerald-200';
            pill.innerHTML = `<span>#${tag}</span><button type="button" data-remove-tag="${idx}" class="text-emerald-600 hover:text-emerald-800 ml-1">×</button>`;
            tagsContainer.insertBefore(pill, tagInput);
        });
        if (tagsHidden) {
            tagsHidden.value = JSON.stringify(tags);
        }
    }

    function addTag(tag) {
        const trimmed = tag.trim();
        if (!trimmed || tags.includes(trimmed) || tags.length >= 20) return;
        tags.push(trimmed);
        renderTags();
        saveDraft();
    }

    tagInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addTag(tagInput.value);
            tagInput.value = '';
        }
        if (event.key === 'Backspace' && !tagInput.value && tags.length) {
            event.preventDefault();
            tags.pop();
            renderTags();
            saveDraft();
        }
    });

    tagsContainer?.addEventListener('click', function (event) {
        if (event.target.dataset.removeTag !== undefined) {
            tags.splice(parseInt(event.target.dataset.removeTag, 10), 1);
            renderTags();
            saveDraft();
        }
    });

    // Color presets
    colorPresets.forEach(preset => {
        preset.addEventListener('click', () => {
            const color = preset.dataset.color;
            if (colorInput) colorInput.value = color;
            saveDraft();
        });
    });

    // Auto-save draft
    function saveDraft() {
        try {
            const trixEditor = document.querySelector('trix-editor');
            const payload = {
                title: document.getElementById('note-title')?.value || '',
                content: trixEditor ? trixEditor.value : '',
                visibility: form.querySelector('select[name="visibility"]')?.value || 'team',
                color: colorInput?.value || '#f8fafc',
                is_pinned: form.querySelector('input[name="is_pinned"]')?.checked || false,
                tags: tags,
            };
            localStorage.setItem(storageKey, JSON.stringify(payload));
        } catch (e) {
            console.error('Draft save failed:', e);
        }
    }

    // Load draft
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            const data = JSON.parse(saved);
            if (data.title) document.getElementById('note-title').value = data.title;
            if (data.visibility) form.querySelector('select[name="visibility"]').value = data.visibility;
            if (data.color) colorInput.value = data.color;
            if (typeof data.is_pinned === 'boolean') form.querySelector('input[name="is_pinned"]').checked = data.is_pinned;
            if (Array.isArray(data.tags)) {
                tags = data.tags;
                renderTags();
            }
            if (data.content) {
                const trixEditor = document.querySelector('trix-editor');
                if (trixEditor) trixEditor.value = data.content;
            }
        }
    } catch (e) {
        console.error('Draft load failed:', e);
    }

    // Auto-save interval
    setInterval(saveDraft, 5000);

    // Clear draft on submit
    form?.addEventListener('submit', () => {
        localStorage.removeItem(storageKey);
    });

    // Trix upload handling
    document.addEventListener('trix-attachment-add', function(event) {
        const attachment = event.attachment;
        if (attachment.file) {
            uploadFile(attachment);
        }
    });

    function uploadFile(attachment) {
        const file = attachment.file;
        const formData = new FormData();
        formData.append('file', file);

        fetch('{{ route('customers.notes.upload', $customer) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.location || data.url) {
                attachment.setAttributes({
                    url: data.location || data.url,
                    href: data.location || data.url
                });
            } else if (data.error) {
                alert('Upload failed: ' + data.error);
                attachment.remove();
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Upload failed. Please try again.');
            attachment.remove();
        });
    }

    renderTags();
})();
</script>
@endpush
@endsection
