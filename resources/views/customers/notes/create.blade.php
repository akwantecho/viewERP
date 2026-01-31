@extends('layouts.app')

@section('title', __('Create Note for :name', ['name' => $customer->name]))

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
                        <h1 class="text-xl font-bold text-gray-900">Create New Note</h1>
                        <p class="text-sm text-gray-500">for {{ $customer->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" id="autosave-indicator" class="text-xs text-gray-400 hidden">
                        <svg class="inline h-4 w-4 animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </button>
                    <button type="submit" form="note-form"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500 hover:shadow-emerald-600/50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Note
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

        <form id="note-form" method="POST" action="{{ route('customers.notes.store', $customer) }}" class="space-y-6">
            @csrf

            <!-- Title -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <input
                    type="text"
                    name="title"
                    id="note-title"
                    value="{{ old('title') }}"
                    placeholder="Note title (optional)"
                    class="w-full border-0 bg-transparent text-3xl font-bold text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-0"
                    autocomplete="off"
                >
            </div>

            <!-- Rich Text Editor (Tiptap) -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Content <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="content" id="note-content-input" value="{{ old('content') }}">
                    <div id="tiptap-toolbar"></div>
                    <div id="tiptap-editor" class="tiptap-editor-container min-h-[500px] border border-gray-200 rounded-xl p-4 bg-white focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500"></div>
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
                                   {{ old('is_pinned') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-gray-700">Pin this note</span>
                        </label>

                        <div>
                            <label class="block text-xs text-gray-600 mb-2">Visibility</label>
                            <select name="visibility"
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="private" {{ old('visibility') === 'private' ? 'selected' : '' }}>🔒 Private</option>
                                <option value="team" {{ old('visibility', 'team') === 'team' ? 'selected' : '' }}>👥 Team</option>
                                <option value="organization" {{ old('visibility') === 'organization' ? 'selected' : '' }}>🏢 Organization</option>
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
                               value="{{ old('color', '#f8fafc') }}"
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


@push('styles')
<style>
    /* Tiptap Editor Styles */
    .tiptap-editor-container {
        min-height: 500px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .tiptap-editor {
        min-height: 100%;
        outline: none;
    }

    .tiptap-editor p {
        margin: 0.5em 0;
    }

    .tiptap-editor h1 {
        font-size: 2em;
        font-weight: 700;
        margin: 0.67em 0;
    }

    .tiptap-editor h2 {
        font-size: 1.5em;
        font-weight: 600;
        margin: 0.83em 0;
    }

    .tiptap-editor h3 {
        font-size: 1.17em;
        font-weight: 600;
        margin: 1em 0;
    }

    .tiptap-editor ul,
    .tiptap-editor ol {
        padding-left: 1.5em;
        margin: 0.5em 0;
    }

    .tiptap-editor ul {
        list-style-type: disc;
    }

    .tiptap-editor ol {
        list-style-type: decimal;
    }

    .tiptap-editor blockquote {
        border-left: 4px solid #10b981;
        padding-left: 1em;
        margin: 1em 0;
        color: #6b7280;
        font-style: italic;
    }

    .tiptap-editor pre {
        background: #1f2937;
        color: #f9fafb;
        padding: 1em;
        border-radius: 0.5rem;
        overflow-x: auto;
        margin: 1em 0;
        font-family: monospace;
    }

    .tiptap-editor code {
        background: #f3f4f6;
        padding: 0.2em 0.4em;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.9em;
    }

    .tiptap-editor pre code {
        background: none;
        padding: 0;
    }

    .tiptap-editor a {
        color: #10b981;
        text-decoration: underline;
    }

    .tiptap-editor img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        margin: 1em 0;
    }

    /* Table Styles */
    .tiptap-editor table {
        border-collapse: collapse;
        margin: 1em 0;
        width: 100%;
        table-layout: fixed;
        overflow: hidden;
    }

    .tiptap-editor th,
    .tiptap-editor td {
        border: 2px solid #d1d5db;
        padding: 0.5rem 0.75rem;
        vertical-align: top;
        box-sizing: border-box;
        position: relative;
        min-width: 1em;
    }

    .tiptap-editor th {
        background: #f3f4f6;
        font-weight: 600;
        text-align: left;
    }

    .tiptap-editor td {
        background: #fff;
    }

    .tiptap-editor .selectedCell:after {
        z-index: 2;
        position: absolute;
        content: "";
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: rgba(16, 185, 129, 0.2);
        pointer-events: none;
    }

    .tiptap-editor .column-resize-handle {
        position: absolute;
        right: -2px;
        top: 0;
        bottom: -2px;
        width: 4px;
        background-color: #10b981;
        pointer-events: none;
    }

    .tiptap-editor.resize-cursor {
        cursor: ew-resize;
        cursor: col-resize;
    }

    /* Placeholder - styled by Tiptap extension */
    .tiptap-editor .is-editor-empty:first-child::before {
        color: #9ca3af;
        float: left;
        height: 0;
        pointer-events: none;
    }

    /* Toolbar */
    .tiptap-toolbar-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .tiptap-toolbar-btn svg {
        width: 18px;
        height: 18px;
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
    const contentInput = document.getElementById('note-content-input');
    const storageKey = 'customer-note-draft-{{ $customer->id }}-new';

    let tags = [];
    let editor = null;

    // Initialize Tiptap Editor
    let initAttempts = 0;
    const maxAttempts = 50; // 5 seconds max wait

    function initTiptapEditor() {
        initAttempts++;

        if (!window.TiptapEditor) {
            if (initAttempts < maxAttempts) {
                setTimeout(initTiptapEditor, 100);
            } else {
                console.error('TiptapEditor failed to load after 5 seconds');
            }
            return;
        }

        const editorElement = document.getElementById('tiptap-editor');
        const toolbarElement = document.getElementById('tiptap-toolbar');

        if (!editorElement || !toolbarElement) {
            console.error('Tiptap elements not found');
            return;
        }

        const initialContent = contentInput?.value || '';

        try {
            editor = window.TiptapEditor.createTiptapEditor({
                element: editorElement,
                content: initialContent,
                placeholder: 'Start writing your note...',
                onUpdate: (html) => {
                    if (contentInput) contentInput.value = html;
                    saveDraft();
                }
            });

            window.TiptapEditor.createToolbar(editor, toolbarElement);

            // Load draft content if exists
            loadDraftContent();
        } catch (e) {
            console.error('Failed to initialize Tiptap editor:', e);
        }
    }

    // Listen for tiptap-ready event as well
    document.addEventListener('tiptap-ready', function() {
        if (!editor) initTiptapEditor();
    });

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
            const payload = {
                title: document.getElementById('note-title')?.value || '',
                content: editor ? editor.getHTML() : (contentInput?.value || ''),
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

    // Load draft content
    function loadDraftContent() {
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
                if (data.content && editor) {
                    editor.commands.setContent(data.content);
                    if (contentInput) contentInput.value = data.content;
                }
            }
        } catch (e) {
            console.error('Draft load failed:', e);
        }
    }

    // Auto-save interval
    setInterval(saveDraft, 5000);

    // Clear draft on submit
    form?.addEventListener('submit', () => {
        // Ensure content is captured before submit
        if (editor && contentInput) {
            contentInput.value = editor.getHTML();
        }
        localStorage.removeItem(storageKey);
    });

    renderTags();
    initTiptapEditor();
})();
</script>
@endpush
@endsection
