/**
 * Grace Apps — app.js
 * Handles: OTP input, Chatbot AJAX, Mailbox compose/preview
 */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ───────────────────────────────────────────
   OTP INPUT — auto-advance & backspace
─────────────────────────────────────────── */
function initOtpInputs() {
    const inputs = document.querySelectorAll('.otp-box input.otp');
    if (!inputs.length) return;

    inputs.forEach((input, i) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);
            if (input.value) {
                input.classList.add('filled');
                if (i < inputs.length - 1) inputs[i + 1].focus();
            } else {
                input.classList.remove('filled');
            }
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && i > 0) {
                inputs[i - 1].focus();
                inputs[i - 1].value = '';
                inputs[i - 1].classList.remove('filled');
            }
        });
        input.addEventListener('paste', e => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            paste.split('').slice(0, inputs.length).forEach((ch, j) => {
                inputs[j].value = ch;
                inputs[j].classList.add('filled');
            });
            const next = Math.min(paste.length, inputs.length - 1);
            inputs[next].focus();
        });
    });
}

function getOtpValue() {
    return Array.from(document.querySelectorAll('.otp-box input.otp'))
        .map(i => i.value).join('');
}

/* ───────────────────────────────────────────
   CHATBOT
─────────────────────────────────────────── */
function initChatbot() {
    const win  = document.getElementById('chatWindow');
    const inp  = document.getElementById('chatInput');
    const btn  = document.getElementById('sendBtn');
    if (!win || !inp || !btn) return;

    window.handleChatKey = e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); } };

    window.sendChat = async () => {
        const msg = inp.value.trim();
        if (!msg) return;

        addMessage('user', msg);
        inp.value = '';
        btn.disabled = true;

        // Show typing indicator
        const typingId = addTyping();

        try {
            const res = await fetch('/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: msg }),
            });
            const data = await res.json();
            removeTyping(typingId);
            addMessage('bot', data.reply ?? 'Sorry, I could not process that.');
        } catch {
            removeTyping(typingId);
            addMessage('bot', 'Something went wrong. Please try again.');
        } finally {
            btn.disabled = false;
            inp.focus();
        }
    };

    function addMessage(role, text) {
        const div = document.createElement('div');
        div.className = `chat-message ${role}`;
        div.innerHTML = `
            <div class="avatar">${role === 'bot' ? '🤖' : '👤'}</div>
            <div class="bubble">${escHtml(text)}</div>
        `;
        win.appendChild(div);
        requestAnimationFrame(() => div.classList.add('show'));
        win.scrollTop = win.scrollHeight;
        return div;
    }

    function addTyping() {
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'chat-message bot show';
        div.innerHTML = `
            <div class="avatar">🤖</div>
            <div class="bubble typing-indicator">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        `;
        win.appendChild(div);
        win.scrollTop = win.scrollHeight;
        return id;
    }

    function removeTyping(id) {
        document.getElementById(id)?.remove();
    }
}

/* ───────────────────────────────────────────
   MAILBOX — compose modal
─────────────────────────────────────────── */
window.openCompose  = () => document.getElementById('composeModal')?.classList.add('open');
window.closeCompose = () => document.getElementById('composeModal')?.classList.remove('open');

// Close modal on backdrop click
document.addEventListener('click', e => {
    const modal = document.getElementById('composeModal');
    if (modal && e.target === modal) closeCompose();
});

window.sendEmail = async () => {
    const to      = document.getElementById('composeTo')?.value.trim();
    const subject = document.getElementById('composeSubject')?.value.trim();
    const body    = document.getElementById('composeBody')?.value.trim();
    const btn     = document.querySelector('.modal .btn.primary');

    if (!to || !subject || !body) {
        showAlert('Please fill in all fields.', 'error');
        return;
    }

    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

    try {
        const res = await fetch('/mailbox/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ to, subject, body }),
        });
        const data = await res.json();
        if (res.ok) {
            closeCompose();
            showAlert(data.message ?? 'Message sent!', 'success');
            document.getElementById('composeTo').value = '';
            document.getElementById('composeSubject').value = '';
            document.getElementById('composeBody').value = '';
        } else {
            showAlert(data.message ?? 'Failed to send.', 'error');
        }
    } catch {
        showAlert('Network error. Please try again.', 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Send Email'; }
    }
};

window.filterMail = () => {
    const q = document.getElementById('searchMail')?.value.toLowerCase() ?? '';
    document.querySelectorAll('.mail-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });
};

window.previewMail = (title, meta, body, el) => {
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewMeta').textContent  = meta;
    document.getElementById('previewBody').textContent  = body;
    document.querySelectorAll('.mail-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    el.classList.remove('unread');
};

/* ───────────────────────────────────────────
   UTILITY
─────────────────────────────────────────── */
function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showAlert(msg, type = 'info') {
    const existing = document.getElementById('grace-alert');
    if (existing) existing.remove();
    const div = document.createElement('div');
    div.id = 'grace-alert';
    div.className = `alert ${type} fade-in`;
    div.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:999;max-width:320px;';
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 4000);
}

/* ───────────────────────────────────────────
   INIT
─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    initOtpInputs();
    initChatbot();
});
