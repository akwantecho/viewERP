import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { Image } from '@tiptap/extension-image';
import { Link } from '@tiptap/extension-link';
import { Placeholder } from '@tiptap/extension-placeholder';

/**
 * Initialize a Tiptap editor instance
 * @param {Object} options
 * @param {HTMLElement} options.element - The element to attach the editor to
 * @param {string} options.content - Initial HTML content
 * @param {string} options.placeholder - Placeholder text
 * @param {Function} options.onUpdate - Callback when content changes
 * @returns {Editor}
 */
export function createTiptapEditor(options = {}) {
    const {
        element,
        content = '',
        placeholder = 'Start writing...',
        onUpdate = () => {},
    } = options;

    const editor = new Editor({
        element,
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
            }),
            Table.configure({
                resizable: true,
                HTMLAttributes: {
                    class: 'tiptap-table',
                },
            }),
            TableRow,
            TableCell,
            TableHeader,
            Image.configure({
                HTMLAttributes: {
                    class: 'tiptap-image',
                },
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    class: 'tiptap-link',
                },
            }),
            Placeholder.configure({
                placeholder,
            }),
        ],
        content,
        onUpdate: ({ editor }) => {
            onUpdate(editor.getHTML());
        },
        editorProps: {
            attributes: {
                class: 'tiptap-editor prose prose-sm max-w-none focus:outline-none',
            },
        },
    });

    return editor;
}

/**
 * Create toolbar buttons for the editor
 * @param {Editor} editor
 * @param {HTMLElement} toolbarElement
 */
export function createToolbar(editor, toolbarElement) {
    const buttons = [
        // Text formatting
        { action: () => editor.chain().focus().toggleBold().run(), icon: 'bold', title: 'Bold', isActive: () => editor.isActive('bold') },
        { action: () => editor.chain().focus().toggleItalic().run(), icon: 'italic', title: 'Italic', isActive: () => editor.isActive('italic') },
        { action: () => editor.chain().focus().toggleStrike().run(), icon: 'strikethrough', title: 'Strikethrough', isActive: () => editor.isActive('strike') },
        { type: 'divider' },
        // Headings
        { action: () => editor.chain().focus().toggleHeading({ level: 1 }).run(), icon: 'h1', title: 'Heading 1', isActive: () => editor.isActive('heading', { level: 1 }) },
        { action: () => editor.chain().focus().toggleHeading({ level: 2 }).run(), icon: 'h2', title: 'Heading 2', isActive: () => editor.isActive('heading', { level: 2 }) },
        { action: () => editor.chain().focus().toggleHeading({ level: 3 }).run(), icon: 'h3', title: 'Heading 3', isActive: () => editor.isActive('heading', { level: 3 }) },
        { type: 'divider' },
        // Lists
        { action: () => editor.chain().focus().toggleBulletList().run(), icon: 'list-ul', title: 'Bullet List', isActive: () => editor.isActive('bulletList') },
        { action: () => editor.chain().focus().toggleOrderedList().run(), icon: 'list-ol', title: 'Numbered List', isActive: () => editor.isActive('orderedList') },
        { type: 'divider' },
        // Block
        { action: () => editor.chain().focus().toggleBlockquote().run(), icon: 'quote', title: 'Quote', isActive: () => editor.isActive('blockquote') },
        { action: () => editor.chain().focus().toggleCodeBlock().run(), icon: 'code', title: 'Code Block', isActive: () => editor.isActive('codeBlock') },
        { type: 'divider' },
        // Table
        { action: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(), icon: 'table', title: 'Insert Table' },
        { action: () => editor.chain().focus().addColumnAfter().run(), icon: 'add-col', title: 'Add Column', disabled: () => !editor.can().addColumnAfter() },
        { action: () => editor.chain().focus().addRowAfter().run(), icon: 'add-row', title: 'Add Row', disabled: () => !editor.can().addRowAfter() },
        { action: () => editor.chain().focus().deleteColumn().run(), icon: 'del-col', title: 'Delete Column', disabled: () => !editor.can().deleteColumn() },
        { action: () => editor.chain().focus().deleteRow().run(), icon: 'del-row', title: 'Delete Row', disabled: () => !editor.can().deleteRow() },
        { action: () => editor.chain().focus().deleteTable().run(), icon: 'del-table', title: 'Delete Table', disabled: () => !editor.can().deleteTable() },
        { type: 'divider' },
        // Link
        { action: () => {
            const url = prompt('Enter URL:');
            if (url) {
                editor.chain().focus().setLink({ href: url }).run();
            }
        }, icon: 'link', title: 'Add Link', isActive: () => editor.isActive('link') },
        { action: () => editor.chain().focus().unsetLink().run(), icon: 'unlink', title: 'Remove Link', disabled: () => !editor.isActive('link') },
        { type: 'divider' },
        // Undo/Redo
        { action: () => editor.chain().focus().undo().run(), icon: 'undo', title: 'Undo', disabled: () => !editor.can().undo() },
        { action: () => editor.chain().focus().redo().run(), icon: 'redo', title: 'Redo', disabled: () => !editor.can().redo() },
    ];

    const iconSVGs = {
        'bold': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>',
        'italic': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>',
        'strikethrough': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4H9a3 3 0 0 0-3 3v0a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v0a3 3 0 0 1-3 3H7"/><line x1="4" y1="12" x2="20" y2="12"/></svg>',
        'h1': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h8M4 6v12M12 6v12M17 12l3-2v8"/></svg>',
        'h2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h8M4 6v12M12 6v12"/><path d="M21 18h-4c0-4 4-3 4-6 0-1.5-2-2.5-4-1"/></svg>',
        'h3': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h8M4 6v12M12 6v12"/><path d="M17 10.5c2-1 4 .5 3 2-1 1.5-3 0-3 2s2 3 4 2"/></svg>',
        'list-ul': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="5" cy="6" r="1" fill="currentColor"/><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="5" cy="18" r="1" fill="currentColor"/></svg>',
        'list-ol': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="4" y="8" font-size="6" fill="currentColor">1</text><text x="4" y="14" font-size="6" fill="currentColor">2</text><text x="4" y="20" font-size="6" fill="currentColor">3</text></svg>',
        'quote': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v4z"/></svg>',
        'code': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>',
        'table': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>',
        'add-col': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="12" height="18" rx="2"/><path d="M9 3v18"/><path d="M19 8v8M15 12h8"/></svg>',
        'add-row': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="12" rx="2"/><path d="M3 9h18"/><path d="M8 19h8M12 15v8"/></svg>',
        'del-col': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="12" height="18" rx="2"/><path d="M9 3v18"/><path d="M16 9l6 6M22 9l-6 6"/></svg>',
        'del-row': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="12" rx="2"/><path d="M3 9h18"/><path d="M9 16l6 6M15 16l-6 6"/></svg>',
        'del-table': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/><path d="M15 15l6 6M21 15l-6 6"/></svg>',
        'link': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        'unlink': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.84 12.25l1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M5.16 11.75l-1.72 1.71a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>',
        'undo': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>',
        'redo': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>',
    };

    toolbarElement.innerHTML = '';
    toolbarElement.className = 'tiptap-toolbar flex flex-wrap items-center gap-1 p-2 border border-gray-200 rounded-xl bg-gray-50 mb-2';

    buttons.forEach(btn => {
        if (btn.type === 'divider') {
            const divider = document.createElement('div');
            divider.className = 'w-px h-6 bg-gray-300 mx-1';
            toolbarElement.appendChild(divider);
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tiptap-toolbar-btn p-1.5 rounded hover:bg-gray-200 transition-colors';
        button.title = btn.title;
        button.innerHTML = `<span class="w-5 h-5 block">${iconSVGs[btn.icon] || ''}</span>`;

        button.addEventListener('click', (e) => {
            e.preventDefault();
            btn.action();
        });

        toolbarElement.appendChild(button);
    });

    // Update button states on selection change
    editor.on('selectionUpdate', () => updateButtonStates());
    editor.on('update', () => updateButtonStates());

    function updateButtonStates() {
        const toolbarButtons = toolbarElement.querySelectorAll('.tiptap-toolbar-btn');
        let btnIndex = 0;

        buttons.forEach((btn) => {
            if (btn.type === 'divider') return;

            const buttonEl = toolbarButtons[btnIndex];
            if (buttonEl) {
                // Active state
                if (btn.isActive && btn.isActive()) {
                    buttonEl.classList.add('bg-emerald-100', 'text-emerald-700');
                } else {
                    buttonEl.classList.remove('bg-emerald-100', 'text-emerald-700');
                }

                // Disabled state
                if (btn.disabled && btn.disabled()) {
                    buttonEl.disabled = true;
                    buttonEl.classList.add('opacity-40', 'cursor-not-allowed');
                } else {
                    buttonEl.disabled = false;
                    buttonEl.classList.remove('opacity-40', 'cursor-not-allowed');
                }
            }
            btnIndex++;
        });
    }

    // Initial state update
    updateButtonStates();
}

// Export for global use
window.TiptapEditor = { createTiptapEditor, createToolbar };

// Dispatch event to signal that TiptapEditor is ready
document.dispatchEvent(new CustomEvent('tiptap-ready'));
