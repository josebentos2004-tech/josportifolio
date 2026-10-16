document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sideToggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    const formatSize = function (bytes) {
        return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
    };

    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const wrapper = input.closest('.media-upload, .slot');
            const existing = wrapper ? wrapper.querySelector('.selected-file') : null;
            if (existing) existing.remove();
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            const maxBytes = Number(input.dataset.maxBytes || 0);
            if (maxBytes > 0 && file.size > maxBytes) {
                window.alert('A imagem escolhida tem ' + formatSize(file.size) + '. O limite deste campo é ' + formatSize(maxBytes) + '. Escolha um ficheiro menor.');
                input.value = '';
                return;
            }

            const note = document.createElement('small');
            note.className = 'selected-file';
            note.textContent = 'Selecionado: ' + file.name + ' (' + formatSize(file.size) + ')';
            if (wrapper) wrapper.appendChild(note);

            // A dimensão também é validada no servidor. Esta verificação dá feedback imediato no navegador.
            const maxDimension = Number(input.dataset.maxDimension || 0);
            const maxPixels = Number(input.dataset.maxPixels || 0);
            if ((maxDimension > 0 || maxPixels > 0) && file.type !== 'image/svg+xml' && file.type.startsWith('image/')) {
                const objectUrl = URL.createObjectURL(file);
                const image = new Image();
                image.onload = function () {
                    URL.revokeObjectURL(objectUrl);
                    const tooLargeByDimension = maxDimension > 0 && (image.naturalWidth > maxDimension || image.naturalHeight > maxDimension);
                    const tooLargeByPixels = maxPixels > 0 && (image.naturalWidth * image.naturalHeight) > maxPixels;
                    if (tooLargeByDimension || tooLargeByPixels) {
                        const reason = tooLargeByDimension
                            ? 'O limite deste campo é ' + maxDimension + ' × ' + maxDimension + ' px.'
                            : 'A imagem ultrapassa ' + (maxPixels / 1000000) + ' megapíxeis.';
                        window.alert('A imagem tem ' + image.naturalWidth + ' × ' + image.naturalHeight + ' px. ' + reason);
                        input.value = '';
                        if (note.parentNode) note.remove();
                    }
                };
                image.onerror = function () {
                    URL.revokeObjectURL(objectUrl);
                };
                image.src = objectUrl;
            }
        });
    });
});
