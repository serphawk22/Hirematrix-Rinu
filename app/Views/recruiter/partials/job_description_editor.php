<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
    document.querySelectorAll('textarea.js-rich-job-description').forEach(function (textarea) {
        if (textarea.dataset.editorReady === '1' || typeof window.Quill === 'undefined') return;

        const editor = document.createElement('div');
        editor.className = 'job-description-rich-editor';
        textarea.insertAdjacentElement('afterend', editor);

        const quill = new Quill(editor, {
            theme: 'snow',
            placeholder: textarea.getAttribute('placeholder') || 'Describe the role, responsibilities and requirements...',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['clean']
                ]
            },
            formats: ['header', 'bold', 'italic', 'list']
        });

        if (textarea.value.trim() !== '') {
            quill.clipboard.dangerouslyPasteHTML(textarea.value);
        }

        textarea.dataset.editorReady = '1';
        textarea.removeAttribute('required');
        textarea.classList.add('job-description-source');

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function (event) {
                const hasContent = quill.getText().trim() !== '';
                textarea.value = hasContent ? quill.root.innerHTML : '';
                if (!hasContent) {
                    event.preventDefault();
                    editor.classList.add('is-invalid');
                    quill.focus();
                    alert('Job description is required.');
                } else {
                    editor.classList.remove('is-invalid');
                }
            });
        }
    });
})();
</script>
