let revealObserver;

function initRevealAnimations() {
    if (revealObserver) {
        revealObserver.disconnect();
    }

    revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.18 });

    document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));
}

function initMagnetButtons() {
    document.querySelectorAll('.magnet').forEach((button) => {
        if (button.dataset.magnetReady === 'true') {
            return;
        }

        button.dataset.magnetReady = 'true';
        button.addEventListener('mousemove', (event) => {
            const rect = button.getBoundingClientRect();
            const x = event.clientX - rect.left - rect.width / 2;
            const y = event.clientY - rect.top - rect.height / 2;
            button.style.transform = `translate(${x * 0.08}px, ${y * 0.18}px)`;
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = '';
        });
    });
}

function initAppointmentMinDate() {
    const appointmentInput = document.querySelector('input[type="datetime-local"]');
    if (appointmentInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset() + 30);
        appointmentInput.min = now.toISOString().slice(0, 16);
    }
}

function initApp() {
    initRevealAnimations();
    initMagnetButtons();
    initAppointmentMinDate();
}

function replacePage(html, url, pushState = true) {
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    if (!nextDocument.body || !nextDocument.querySelector('main')) {
        window.location.href = url;
        return;
    }

    document.title = nextDocument.title;
    document.body.innerHTML = nextDocument.body.innerHTML;

    if (pushState && url && url !== window.location.href) {
        history.pushState({ ajax: true }, '', url);
    }

    initApp();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function fetchAndSwap(url, options = {}) {
    document.body.classList.add('ajax-loading');

    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });
        const html = await response.text();
        replacePage(html, response.url || url, options.pushState !== false);
    } catch (error) {
        console.error('Errore AJAX:', error);
        window.location.href = url;
    } finally {
        document.body.classList.remove('ajax-loading');
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target.closest('form');
    if (!form || event.defaultPrevented || form.dataset.ajax === 'false') {
        return;
    }

    event.preventDefault();

    const submitter = event.submitter;
    if (submitter) {
        submitter.disabled = true;
        submitter.dataset.originalText = submitter.textContent;
        submitter.textContent = 'Salvataggio...';
    }

    const method = (form.method || 'GET').toUpperCase();
    const action = form.action || window.location.href;
    const body = method === 'GET' ? null : new FormData(form);
    const url = method === 'GET' ? `${action}?${new URLSearchParams(new FormData(form)).toString()}` : action;

    fetchAndSwap(url, { method, body }).finally(() => {
        if (submitter) {
            submitter.disabled = false;
            submitter.textContent = submitter.dataset.originalText || submitter.textContent;
        }
    });
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-ajax-link]');
    if (!link || event.defaultPrevented || link.target) {
        return;
    }

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) {
        return;
    }

    event.preventDefault();
    fetchAndSwap(url.href, { method: 'GET' });
});

window.addEventListener('popstate', () => {
    fetchAndSwap(window.location.href, { method: 'GET', pushState: false });
});

initApp();
