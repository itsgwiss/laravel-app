import './bootstrap';

window.previewMail = function (el) {

    // safer: find correct container even if DOM changes
    const mailItem = el.closest('.mail-item');

    if (!mailItem) {
        console.error('ERROR: .mail-item not found', el);
        return;
    }

    document.querySelectorAll('.mail-item').forEach(m => {
        m.classList.remove('active');
    });

    mailItem.classList.add('active');

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.innerText = value || '';
    };

    const hide = (id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    };

    const show = (id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'block';
    };

    hide('previewPlaceholder');
    show('previewContent');

    setText('previewTitle', el.dataset.subject);
    setText('previewMeta', el.dataset.meta);
    setText('previewBody', el.dataset.body);
};