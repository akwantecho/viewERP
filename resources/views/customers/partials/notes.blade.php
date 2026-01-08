@php
    $isEditing = isset($editingNote) && $editingNote;
    $formAction = $isEditing
        ? route('customers.notes.update', [$customer, $editingNote])
        : route('customers.notes.store', $customer);
    $formMethod = $isEditing ? 'PUT' : 'POST';
    $tagsValue = old('tags');
    if ($tagsValue) {
        $decodedTags = json_decode($tagsValue, true);
        $noteTags = is_array($decodedTags) ? $decodedTags : [];
    } else {
        $noteTags = $isEditing ? ($editingNote->tags ?? []) : [];
    }
    $noteTags = array_values(array_filter($noteTags, static fn ($tag) => $tag !== ''));
    $visibilityValue = old('visibility', $isEditing ? $editingNote->visibility : 'team');
    $colorValue = old('color', $isEditing ? ($editingNote->color ?? '#f8fafc') : '#f8fafc');
    $titleValue = old('title', $isEditing ? $editingNote->title : '');
    $htmlValue = old('html', $isEditing ? $editingNote->html : '');
    $isPinnedValue = old('is_pinned', $isEditing ? ($editingNote->is_pinned ? '1' : '0') : '0');
    $draftStorageKey = $isEditing
        ? 'customer-note-draft-' . $customer->id . '-note-' . $editingNote->id
        : 'customer-note-draft-' . $customer->id . '-new';
@endphp

@push('styles')
<style>
    .note-fullscreen-active {
        position: fixed;
        inset: 0;
        background: #f9fafb;
        z-index: 60;
        overflow-y: auto;
        padding: 2.5rem 1.5rem;
    }

    .note-fullscreen-active .note-fullscreen-card {
        margin: 0 auto;
        max-width: 960px;
    }

    .note-fullscreen-active .note-fullscreen-backdrop {
        display: none;
    }

    body.note-scroll-lock {
        overflow: hidden;
    }
</style>
@endpush

<section id="notes" data-tab-panel="notes">
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm note-fullscreen-card" data-note-card>
        <div class="border-b border-gray-100 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.notes.title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('customers.profile.notes.subtitle') }}</p>
            </div>
            <button type="button" data-note-fullscreen-toggle class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M5 3a2 2 0 00-2 2v2a1 1 0 102 0V6h1a1 1 0 000-2H5zm10 0h-2a1 1 0 100 2h1v1a1 1 0 102 0V5a2 2 0 00-2-2zM5 15h1a1 1 0 110 2H5a2 2 0 01-2-2v-2a1 1 0 112 0v1zm10-3a1 1 0 00-1 1v1h-1a1 1 0 100 2h2a2 2 0 002-2v-2a1 1 0 10-2 0v1z" />
                </svg>
                <span
                    data-note-fullscreen-label
                    data-expand-label="{{ __('customers.profile.notes.expand') }}"
                    data-collapse-label="{{ __('customers.profile.notes.collapse') }}"
                >{{ __('customers.profile.notes.expand') }}</span>
            </button>
        </div>

        <div class="px-6 py-6">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <strong class="font-semibold">{{ __('customers.profile.notes.errors_title') }}</strong>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="customer-note-form" method="POST" action="{{ $formAction }}" class="space-y-5" data-storage-key="{{ $draftStorageKey }}">
                @csrf
                @if ($formMethod === 'PUT')
                    @method('PUT')
                @endif

                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                    <div class="space-y-4">
                        <div>
                            <label for="note-title" class="block text-sm font-medium text-gray-700">{{ __('customers.profile.notes.form.title') }}</label>
                            <input id="note-title" name="title" type="text" value="{{ $titleValue }}" placeholder="{{ __('customers.profile.notes.form.title_placeholder') }}"
                                   class="mt-1 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring focus:ring-emerald-100">
                        </div>

                        <div>
                            <label for="note-html" class="block text-sm font-medium text-gray-700">
                                {{ __('customers.profile.notes.form.content') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea id="note-html" name="html" class="mt-1 min-h-[220px] w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring focus:ring-emerald-100">{!! $htmlValue !!}</textarea>
                            <div id="note-editor-loading" class="mt-2 text-xs text-gray-400">{{ __('customers.profile.notes.form.loading') }}</div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="is_pinned" value="1" @checked($isPinnedValue === '1') class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                {{ __('customers.profile.notes.form.pin') }}
                            </label>

                            <label class="flex items-center gap-3 text-sm text-gray-600">
                                <span>{{ __('customers.profile.notes.form.color') }}</span>
                                <input type="color" name="color" value="{{ $colorValue ?? '#f8fafc' }}" class="h-8 w-12 cursor-pointer rounded border border-gray-300 p-1">
                            </label>

                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <span>{{ __('customers.profile.notes.form.visibility') }}</span>
                                <select name="visibility" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-emerald-500 focus:outline-none">
                                    <option value="private" @selected($visibilityValue === 'private')>{{ __('customers.profile.notes.form.visibility_options.private') }}</option>
                                    <option value="team" @selected($visibilityValue === 'team')>{{ __('customers.profile.notes.form.visibility_options.team') }}</option>
                                    <option value="organization" @selected($visibilityValue === 'organization')>{{ __('customers.profile.notes.form.visibility_options.organization') }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('customers.profile.notes.form.tags') }}</label>
                            <div id="note-tags" class="mt-1 flex min-h-[44px] flex-wrap gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm focus-within:border-emerald-500">
                                <input type="text" id="note-tag-input" class="flex-1 border-none bg-transparent text-sm outline-none" placeholder="{{ __('customers.profile.notes.form.tags_placeholder') }}">
                            </div>
                            <input type="hidden" id="note-tags-hidden" name="tags" value='@json($noteTags)'>
                        </div>

                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 text-xs text-emerald-800">
                            {!! __('customers.profile.notes.form.autosave_hint_html') !!}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                        {{ $isEditing ? __('customers.profile.notes.form.update') : __('customers.profile.notes.form.save') }}
                    </button>
                    @if ($isEditing)
                        <a href="{{ route('customers.profile', $customer) . '#notes' }}" class="text-sm text-gray-500 hover:text-gray-700" data-clear-draft="true">{{ __('customers.profile.notes.form.cancel_edit') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="mt-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ __('customers.profile.notes.list_title') }}</h3>
            <span class="rounded-full bg-gray-900/5 px-3 py-1 text-xs font-semibold text-gray-600">{{ __('customers.profile.notes.total', ['count' => $notes->total()]) }}</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($notes as $note)
                <article class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-base font-semibold text-gray-900">{{ $note->title ?: __('customers.profile.notes.untitled') }}</h4>
                                @if($note->is_pinned)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold uppercase text-amber-700">{{ __('customers.profile.notes.pinned') }}</span>
                                @endif
                            </div>
                            @php
                                $noteAuthor = $note->user?->name ?: __('customers.profile.notes.unknown_author');
                                $noteUpdated = $note->updated_at
                                    ? __('customers.profile.notes.updated', ['time' => $note->updated_at->diffForHumans()])
                                    : __('customers.profile.notes.updated', ['time' => '—']);
                                $noteVisibilityKey = in_array($note->visibility, ['team', 'private', 'organization'], true)
                                    ? $note->visibility
                                    : 'team';
                                $noteVisibilityLabel = __('customers.profile.notes.form.visibility_options.' . $noteVisibilityKey);
                            @endphp
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('customers.profile.notes.meta', [
                                    'author' => $noteAuthor,
                                    'updated' => $noteUpdated,
                                    'visibility' => $noteVisibilityLabel,
                                ]) }}
                            </p>
                        </div>
                        @if($note->color)
                            <span class="h-8 w-8 rounded-full border" style="background: {{ $note->color }}"></span>
                        @endif
                    </div>
                    <div class="px-5 py-4">
                        @if(!empty($note->tags))
                            <div class="mb-3 flex flex-wrap gap-2">
                                @foreach($note->tags as $tag)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">#{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="prose prose-sm max-w-none text-gray-700" style="max-height: 180px; overflow: hidden;">
                            {!! $note->html !!}
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-sm">
                        <a href="{{ route('customers.profile', $customer) . '?note=' . $note->id . '#notes' }}" class="text-emerald-600 hover:text-emerald-700">{{ __('customers.profile.notes.edit') }}</a>
                        <form method="POST" action="{{ route('customers.notes.destroy', [$customer, $note]) }}" data-confirm="delete" data-confirm-title="{{ __('customers.profile.notes.delete_title') }}" data-confirm-message="{{ __('customers.profile.notes.delete_message') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">{{ __('customers.profile.notes.delete') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center text-sm text-gray-500">
                    {{ __('customers.profile.notes.empty') }}
                </div>
            @endforelse
        </div>

        <div class="pt-2">
            {{ $notes->withQueryString()->links() }}
        </div>
    </div>
</section>

@php
    $noteStrings = [
        'expand' => __('customers.profile.notes.expand'),
        'collapse' => __('customers.profile.notes.collapse'),
        'editorError' => __('customers.profile.notes.form.editor_error'),
        'uploadNetworkError' => __('customers.profile.notes.upload.network_error'),
        'uploadFailed' => __('customers.profile.notes.upload.failed'),
        'uploadInvalidResponse' => __('customers.profile.notes.upload.invalid_response'),
    ];
@endphp

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
(function() {
    const tabPanel = document.querySelector('[data-tab-panel="notes"]');
    if (!tabPanel) return;

    const strings = @json($noteStrings);

    const storageKey = tabPanel.querySelector('#customer-note-form')?.dataset.storageKey;
    const tagsHidden = document.getElementById('note-tags-hidden');
    const tagsContainer = document.getElementById('note-tags');
    const tagInput = document.getElementById('note-tag-input');
    let tags = [];
    let pendingHtml = null;

    function renderTags() {
        if (!tagsContainer) return;
        tagsContainer.querySelectorAll('[data-tag-item]').forEach(el => el.remove());
        tags.forEach((tag, idx) => {
            const pill = document.createElement('span');
            pill.dataset.tagItem = idx;
            pill.className = 'inline-flex items-center gap-1 rounded-full bg-gray-900/5 px-2.5 py-1 text-xs font-medium text-gray-600';
            pill.innerHTML = `<span>#${tag}</span><button type="button" data-remove-tag="${idx}" class="text-gray-400 hover:text-gray-600">×</button>`;
            tagsContainer.insertBefore(pill, tagInput);
        });
        if (tagsHidden) {
            tagsHidden.value = JSON.stringify(tags);
        }
    }

    function addTag(tag) {
        const trimmed = tag.trim();
        if (!trimmed || tags.includes(trimmed)) return;
        tags.push(trimmed);
        renderTags();
    }

    function removeTag(index) {
        tags.splice(index, 1);
        renderTags();
    }

    if (tagsHidden && tagsHidden.value) {
        try {
            const parsed = JSON.parse(tagsHidden.value);
            if (Array.isArray(parsed)) {
                tags = parsed;
            }
        } catch (_) {
            tags = [];
        }
    }
    renderTags();

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
        }
    });

    tagsContainer?.addEventListener('click', function (event) {
        const target = event.target;
        if (target instanceof HTMLElement && target.dataset.removeTag) {
            removeTag(parseInt(target.dataset.removeTag, 10));
        }
    });

    const editorSelector = '#note-html';
    const textareaEl = document.querySelector(editorSelector);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const uploadUrl = '{{ route('customers.notes.upload', $customer) }}';
    const form = document.getElementById('customer-note-form');
    const loadingIndicator = document.getElementById('note-editor-loading');
    let editorInstance = null;

    class CustomerNoteUploadAdapter {
        constructor(loader) {
            this.loader = loader;
            this.xhr = null;
        }

        upload() {
            return this.loader.file.then(file => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl, true);
                xhr.responseType = 'json';
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.onerror = () => reject(strings.uploadNetworkError);
                xhr.onabort = () => reject();
                xhr.onload = () => {
                    const response = xhr.response;
                    if (!response || xhr.status >= 400) {
                        return reject(strings.uploadFailed);
                    }
                    const location = response.location || response.url;
                    if (location) {
                        resolve({ default: location });
                    } else {
                        reject(strings.uploadInvalidResponse);
                    }
                };

                const data = new FormData();
                data.append('file', file);
                xhr.send(data);
                this.xhr = xhr;
            }));
        }

        abort() {
            if (this.xhr) {
                this.xhr.abort();
            }
        }
    }

    function CustomerNoteUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = loader => new CustomerNoteUploadAdapter(loader);
    }

    function resolveEditorConstructor() {
        if (window.ClassicEditor) {
            return window.ClassicEditor;
        }
        if (window.CKEDITOR && window.CKEDITOR.ClassicEditor) {
            return window.CKEDITOR.ClassicEditor;
        }
        return null;
    }

    function initializeCKEditor() {
        const EditorConstructor = resolveEditorConstructor();
        if (!EditorConstructor || !textareaEl) {
            if (loadingIndicator) {
                loadingIndicator.textContent = strings.editorError;
            }
            return;
        }

        EditorConstructor.create(textareaEl, {
            extraPlugins: [CustomerNoteUploadAdapterPlugin],
            toolbar: {
                items: [
                    'undo', 'redo', '|',
                    'heading', '|',
                    'bold', 'italic', '|',
                    'bulletedList', 'numberedList', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', 'imageUpload'
                ]
            },
            image: {
                toolbar: ['imageTextAlternative']
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            },
            licenseKey: ''
        }).then(editor => {
            editorInstance = editor;
            if (pendingHtml) {
                editor.setData(pendingHtml);
                pendingHtml = null;
            }
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
        }).catch(error => {
            console.error('CKEditor initialization error', error);
            if (loadingIndicator) {
                loadingIndicator.textContent = strings.editorError;
            }
        });
    }

    initializeCKEditor();

    const fullscreenToggle = tabPanel.querySelector('[data-note-fullscreen-toggle]');
    const fullscreenLabel = tabPanel.querySelector('[data-note-fullscreen-label]');

    function setFullscreen(active) {
        if (active) {
            tabPanel.classList.add('note-fullscreen-active');
        } else {
            tabPanel.classList.remove('note-fullscreen-active');
        }
        if (active) {
            document.body.classList.add('note-scroll-lock');
            fullscreenLabel.textContent = fullscreenLabel?.dataset.collapseLabel || strings.collapse;
            fullscreenToggle?.classList.add('bg-gray-900', 'text-white', 'border-transparent');
        } else {
            document.body.classList.remove('note-scroll-lock');
            fullscreenLabel.textContent = fullscreenLabel?.dataset.expandLabel || strings.expand;
            fullscreenToggle?.classList.remove('bg-gray-900', 'text-white', 'border-transparent');
        }
    }

    fullscreenToggle?.addEventListener('click', () => {
        const isActive = !tabPanel.classList.contains('note-fullscreen-active');
        setFullscreen(isActive);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && tabPanel.classList.contains('note-fullscreen-active')) {
            setFullscreen(false);
        }
    });

    const draftIntervalMs = 5000;
    if (storageKey && form) {
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                const data = JSON.parse(saved);
                if (data.title !== undefined) {
                    const titleInput = document.getElementById('note-title');
                    if (titleInput && !titleInput.value) {
                        titleInput.value = data.title;
                    }
                }
                if (data.visibility) {
                    const visibilitySelect = form.querySelector('select[name="visibility"]');
                    if (visibilitySelect) visibilitySelect.value = data.visibility;
                }
                if (data.color) {
                    const colorInput = form.querySelector('input[name="color"]');
                    if (colorInput) colorInput.value = data.color;
                }
                if (typeof data.is_pinned === 'boolean') {
                    const pinCheckbox = form.querySelector('input[name="is_pinned"]');
                    if (pinCheckbox) pinCheckbox.checked = data.is_pinned;
                }
                if (Array.isArray(data.tags) && !tags.length) {
                    tags = data.tags;
                    renderTags();
                }
                if (data.html) {
                    pendingHtml = data.html;
                    if (editorInstance) {
                        editorInstance.setData(data.html);
                        pendingHtml = null;
                    }
                }
            }
        } catch (_) {
            /* ignore */
        }

        const saveDraft = function() {
            const payload = {
                title: document.getElementById('note-title')?.value || '',
                visibility: form.querySelector('select[name="visibility"]')?.value || 'team',
                color: form.querySelector('input[name="color"]')?.value || '#f8fafc',
                is_pinned: form.querySelector('input[name="is_pinned"]')?.checked || false,
                tags,
                html: editorInstance ? editorInstance.getData() : (textareaEl?.value || ''),
            };
            try {
                localStorage.setItem(storageKey, JSON.stringify(payload));
            } catch (_) {
                /* ignore quota errors */
            }
        };

        const intervalId = window.setInterval(saveDraft, draftIntervalMs);
        form.addEventListener('submit', function () {
            if (editorInstance) {
                textareaEl.value = editorInstance.getData();
            }
            window.clearInterval(intervalId);
            if (storageKey) {
                localStorage.removeItem(storageKey);
            }
        });

        window.addEventListener('beforeunload', saveDraft);

        const clearLinks = form.querySelectorAll('[data-clear-draft="true"]');
        clearLinks.forEach((link) => {
            link.addEventListener('click', function () {
                if (storageKey) {
                    localStorage.removeItem(storageKey);
                }
            });
        });
    }
})();
</script>
@endpush
