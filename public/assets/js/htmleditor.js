(function () {
    'use strict';

    function createToolbarButton(label, title, onClick) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'editor-btn';
        btn.innerHTML = label;
        btn.title = title;
        btn.addEventListener('click', onClick);
        return btn;
    }

    function initEditor(textarea) {
        textarea.style.display = 'none';

        const wrap = document.createElement('div');
        wrap.className = 'html-editor';

        const toolbar = document.createElement('div');
        toolbar.className = 'html-editor-toolbar';

        const surface = document.createElement('div');
        surface.className = 'html-editor-surface';
        surface.contentEditable = 'true';
        surface.innerHTML = textarea.value;

        let sourceMode = false;

        function syncSurfaceToTextarea() {
            textarea.value = surface.innerHTML;
        }

        function exec(command, value) {
            surface.focus();
            document.execCommand(command, false, value || null);
            syncSurfaceToTextarea();
        }

        toolbar.appendChild(createToolbarButton('<b>B</b>', 'Grassetto', () => exec('bold')));
        toolbar.appendChild(createToolbarButton('<i>I</i>', 'Corsivo', () => exec('italic')));
        toolbar.appendChild(createToolbarButton('<u>U</u>', 'Sottolineato', () => exec('underline')));
        toolbar.appendChild(createToolbarButton('&#128279;', 'Inserisci link', () => {
            const url = prompt('URL del link:', 'https://');
            if (url) exec('createLink', url);
        }));
        toolbar.appendChild(createToolbarButton('&#8226;', 'Elenco puntato', () => exec('insertUnorderedList')));

        const sourceToggle = createToolbarButton('&lt;/&gt;', 'Vedi/modifica HTML sorgente', () => {
            sourceMode = !sourceMode;
            if (sourceMode) {
                syncSurfaceToTextarea();
                textarea.style.display = '';
                surface.style.display = 'none';
                sourceToggle.classList.add('active');
            } else {
                surface.innerHTML = textarea.value;
                textarea.style.display = 'none';
                surface.style.display = '';
                sourceToggle.classList.remove('active');
            }
        });
        toolbar.appendChild(sourceToggle);

        surface.addEventListener('input', syncSurfaceToTextarea);
        surface.addEventListener('blur', syncSurfaceToTextarea);

        wrap.appendChild(toolbar);
        wrap.appendChild(surface);
        textarea.parentNode.insertBefore(wrap, textarea);

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', syncSurfaceToTextarea);
        }
    }

    document.querySelectorAll('textarea[data-html-editor]').forEach(initEditor);
})();
