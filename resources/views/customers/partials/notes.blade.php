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

<section id="notes" data-tab-panel="notes">
    <div class="rounded-2xl border border-gray-100 bg-white shadow-sm" data-note-card>
        <div class="border-b border-gray-100 px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.notes.title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('customers.profile.notes.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('customers.notes.create', $customer) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('New Note') }}</span>
                </a>
            </div>
        </div>

        <div class="px-6 py-6">
            @if($notes->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <h3 class="mt-4 text-base font-medium text-gray-900">Create notes with full-page editor</h3>
                    <p class="mt-2 text-sm text-gray-500">Click "New Note" to create a note with our powerful rich text editor</p>
                    <div class="mt-6">
                        <a href="{{ route('customers.notes.create', $customer) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Your First Note
                        </a>
                    </div>
                </div>
            @else

            {{-- Legacy inline form hidden by default --}}
            <div id="legacy-note-form" class="hidden">
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
                            @if($note->content)
                                {!! $note->content !!}
                            @elseif($note->html)
                                {!! $note->html !!}
                            @else
                                <p class="text-gray-400 italic">No content</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-sm">
                        <a href="{{ route('notes.edit', $note) }}"
                           class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ __('customers.profile.notes.edit') }}
                        </a>
                        <form method="POST" action="{{ route('notes.destroy', $note) }}"
                              data-confirm
                              data-confirm-title="{{ __('customers.profile.notes.delete_title') }}"
                              data-confirm-message="{{ __('customers.profile.notes.delete_message') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 font-medium">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                {{ __('customers.profile.notes.delete') }}
                            </button>
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
            @endif
        </div>
    </div>
</section>

{{-- Delete confirmation is handled by the global script in layouts/app.blade.php --}}
