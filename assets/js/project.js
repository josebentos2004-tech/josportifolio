/* Interacção das estrelas. O envio é processado pela própria página project.php
 * através de POST/Redirect/GET, sem chamadas AJAX para endpoints separados. */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ratingForm');
    if (!form) return;

    const buttons = Array.from(form.querySelectorAll('[data-rating]'));
    const value = document.getElementById('ratingValue');

    function paint(selected) {
        buttons.forEach(function (button) {
            const score = Number(button.dataset.rating);
            button.classList.toggle('selected', score <= selected);
            button.setAttribute('aria-pressed', String(score === selected));
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            value.value = String(Number(button.dataset.rating));
            paint(Number(value.value));
        });
    });

    paint(Number(value ? value.value : 0));
});
