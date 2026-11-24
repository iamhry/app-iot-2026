document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) {
        return;
    }
    const value = button.getAttribute('data-copy') || '';
    const previous = button.textContent;
    try {
        await navigator.clipboard.writeText(value);
        button.textContent = 'Tersalin';
    } catch (error) {
        button.textContent = 'Salin manual';
    }
    window.setTimeout(() => {
        button.textContent = previous;
    }, 1600);
});
