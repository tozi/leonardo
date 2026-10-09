/**
 * Quill editor wrapper pre admin (nahrádza TinyMCE).
 *
 * Použitie: <textarea name="content" data-rw-editor></textarea>
 * Konfigurácia (pred načítaním tohto súboru):
 *   window.RW_EDITOR_CONFIG = { uploadUrl, csrf, csrfName, postId };
 *
 * - textarea zostáva skutočným poľom formulára (hodnota sa synchronizuje po každej zmene)
 * - kým používateľ obsah v editore nezmení, odošle sa PÔVODNÉ HTML bez zmeny
 *   (Quill nepozná napr. tabuľky/inline štýly, tak sa pri samotnom uložení nič nestratí)
 * - tlačidlo </> prepína na úpravu zdrojového HTML
 * - obrázky sa nahrávajú na server (upload.php), nie ako base64
 */
(function () {
    'use strict';

    var cfg = window.RW_EDITOR_CONFIG || {};
    var IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    var TOOLBAR = [
        [{ header: [1, 2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
        [{ align: [] }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean']
    ];

    var FORMATS = ['header', 'bold', 'italic', 'underline', 'strike', 'list', 'indent', 'align',
                   'blockquote', 'code-block', 'link', 'image'];

    function uploadImage(file) {
        if (IMAGE_TYPES.indexOf(file.type) === -1) {
            return Promise.reject(new Error('Povolené sú len JPG, PNG, GIF a WebP.'));
        }
        var fd = new FormData();
        fd.append('file', file, file.name || 'image');
        fd.append(cfg.csrfName || 'csrf_token', cfg.csrf || '');
        if (cfg.postId) fd.append('post_id', cfg.postId);
        return fetch(cfg.uploadUrl || 'upload.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                return r.json().catch(function () { throw new Error('Neplatná odpoveď servera'); })
                    .then(function (j) {
                        if (!r.ok || j.error) throw new Error(j.error || ('HTTP ' + r.status));
                        return j.location || j.url;
                    });
            });
    }

    function init(textarea) {
        if (textarea.dataset.rwInit) return;
        textarea.dataset.rwInit = '1';

        var original = textarea.value;
        var dirty = false;
        var sourceMode = false;

        var wrap = document.createElement('div');
        wrap.className = 'rw-editor';
        textarea.parentNode.insertBefore(wrap, textarea);
        var host = document.createElement('div');
        wrap.appendChild(host);
        wrap.appendChild(textarea);
        textarea.classList.add('rw-editor-source');
        textarea.style.display = 'none';

        var quill;

        function pickImage() {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = IMAGE_TYPES.join(',');
            input.onchange = function () {
                if (input.files && input.files[0]) insertFiles([input.files[0]]);
            };
            input.click();
        }

        function insertFiles(files, index) {
            var range = quill.getSelection(true);
            var pos = (typeof index === 'number') ? index : (range ? range.index : quill.getLength());
            wrap.classList.add('rw-uploading');
            var chain = Promise.resolve();
            Array.prototype.forEach.call(files, function (file) {
                chain = chain.then(function () {
                    return uploadImage(file).then(function (url) {
                        quill.insertEmbed(pos, 'image', url, 'user');
                        pos += 1;
                        quill.setSelection(pos, 0, 'silent');
                    });
                });
            });
            chain.catch(function (err) {
                alert('Nahrávanie obrázka zlyhalo: ' + err.message);
            }).then(function () {
                wrap.classList.remove('rw-uploading');
            });
        }

        quill = new Quill(host, {
            theme: 'snow',
            placeholder: textarea.getAttribute('placeholder') || '',
            formats: FORMATS,
            modules: {
                toolbar: { container: TOOLBAR, handlers: { image: pickImage } },
                // drag&drop / vloženie obrázka zo schránky -> upload na server (nie base64)
                uploader: {
                    mimetypes: IMAGE_TYPES,
                    handler: function (range, files) { insertFiles(files, range ? range.index : undefined); }
                }
            }
        });

        // Base64 obrázky pri vkladaní HTML zahodiť (zbytočne by nafúkli databázu)
        var Delta = Quill.import('delta');
        quill.clipboard.addMatcher('img', function (node, delta) {
            return /^data:/i.test(node.getAttribute('src') || '') ? new Delta() : delta;
        });

        // Počiatočný obsah (bez vyvolania zmeny)
        if (original && original.trim() !== '') {
            quill.setContents(quill.clipboard.convert({ html: original }), 'silent');
        }

        function sync() {
            textarea.value = quill.getLength() <= 1 ? '' : quill.getSemanticHTML();
        }

        quill.on('text-change', function (delta, oldDelta, source) {
            if (source !== 'user') return;
            dirty = true;
            sync();
        });

        // Tlačidlo na úpravu zdrojového HTML
        var toolbarEl = quill.getModule('toolbar').container;
        var grp = document.createElement('span');
        grp.className = 'ql-formats rw-source-group';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rw-source-btn';
        btn.title = 'Zdrojový HTML kód';
        btn.textContent = '</> HTML';
        grp.appendChild(btn);
        toolbarEl.appendChild(grp);

        btn.addEventListener('click', function () {
            if (!sourceMode) {
                sourceMode = true;
                wrap.classList.add('rw-source-on');
                btn.classList.add('active');
                textarea.style.display = 'block';
                textarea.focus();
            } else {
                sourceMode = false;
                wrap.classList.remove('rw-source-on');
                btn.classList.remove('active');
                textarea.style.display = 'none';
                // hodnota textarea ostáva tak, ako ju používateľ upravil; Quill ju len zobrazí
                quill.setContents(quill.clipboard.convert({ html: textarea.value }), 'silent');
            }
        });

        // Poistka: ak by sa formulár odoslal v režime zdroja, textarea už obsahuje správnu hodnotu.
        // Ak sa v editore nič nezmenilo, textarea zostáva s pôvodným HTML.
        var form = textarea.form;
        if (form) {
            form.addEventListener('submit', function () {
                if (!sourceMode && dirty) sync();
            });
        }
    }

    function initAll() {
        if (typeof Quill === 'undefined') {
            console.error('Quill sa nenačítal (CDN nedostupné?).');
            return;
        }
        document.querySelectorAll('textarea[data-rw-editor]').forEach(init);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
