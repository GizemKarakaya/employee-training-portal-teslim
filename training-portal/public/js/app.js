document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const button = event.submitter;

        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();

            return;
        }

        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.classList.add('is-loading');

        if (button.dataset.loadingText) {
            button.textContent = button.dataset.loadingText;
        }
    });
});
