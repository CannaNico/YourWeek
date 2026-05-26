/**
 * Toast Notification System – YourWeek
 * ---------------------------------------------------------------
 * Fix principali rispetto alla versione originale:
 *   - XSS GRAVE: message e title venivano interpolati in innerHTML
 *     senza escape → injection possibile da risposte API. Ora usiamo
 *     textContent e nodi DOM.
 *   - Rimossi onclick/onmouseover inline (CSP-friendly).
 *   - Accessibilità: role="status"/aria-live, pulsante con aria-label.
 *   - Limite massimo di toast contemporanee (max 5).
 *   - Math.random + substr(2,9) → crypto.randomUUID()/fallback.
 *   - Gestione timeout robusta con WeakMap (no dataset string).
 *   - Esposto window.Toast in modo idempotente.
 * ---------------------------------------------------------------
 */

(() => {
    'use strict';

    const MAX_VISIBLE = 5;
    const TIMEOUTS = new WeakMap();

    const ICONS = Object.freeze({
        success: 'fa-check-circle',
        error:   'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle',
        loading: 'fa-spinner fa-spin',
    });

    const COLORS = Object.freeze({
        success: '#10b981',
        error:   '#ef4444',
        warning: '#f59e0b',
        info:    '#3b82f6',
        loading: '#6366f1',
    });

    const TITLES = Object.freeze({
        success: 'Successo!',
        error:   'Errore',
        warning: 'Attenzione',
        info:    'Informazione',
        loading: 'Caricamento...',
    });

    let container = null;

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    const safeUUID = () =>
        (typeof crypto !== 'undefined' && crypto.randomUUID)
            ? crypto.randomUUID()
            : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 11)}`;

    const ensureContainer = () => {
        if (container && document.body.contains(container)) return container;

        container = document.createElement('div');
        container.id = 'toast-container';
        Object.assign(container.style, {
            position:      'fixed',
            top:           '20px',
            right:         '20px',
            zIndex:        '10000',
            display:       'flex',
            flexDirection: 'column',
            gap:           '12px',
            pointerEvents: 'none',
            maxWidth:      '400px',
        });
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'false');
        document.body.appendChild(container);
        injectStyles();
        return container;
    };

    const injectStyles = () => {
        if (document.getElementById('toast-styles')) return;
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes yw-toast-progress { from { width: 100%; } to { width: 0%; } }
            .yw-toast { transition: transform .4s cubic-bezier(.34,1.56,.64,1), opacity .4s; }
            .yw-toast--enter { transform: translateX(120%); opacity: 0; }
            .yw-toast--shown { transform: translateX(0);    opacity: 1; }
            .yw-toast--leave { transform: translateX(120%); opacity: 0; }
        `;
        document.head.appendChild(style);
    };

    const enforceLimit = () => {
        if (!container) return;
        while (container.children.length >= MAX_VISIBLE) {
            const oldest = container.firstElementChild;
            if (oldest) dismiss(oldest.id);
            else break;
        }
    };

    // ──────────────────────────────────────────────────────────────
    // Create / dismiss / update
    // ──────────────────────────────────────────────────────────────

    const createToast = (message, type = 'info', duration = 3000, options = {}) => {
        ensureContainer();
        enforceLimit();

        const id = `toast-${safeUUID()}`;
        const isLoading = type === 'loading';
        const finalDuration = isLoading ? 0 : duration;
        const color = COLORS[type] ?? COLORS.info;
        const title = options.title ?? TITLES[type] ?? 'Notifica';

        const toast = document.createElement('div');
        toast.id = id;
        toast.className = 'yw-toast yw-toast--enter';
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        Object.assign(toast.style, {
            background:    '#fff',
            borderRadius:  '16px',
            padding:       '16px 20px',
            boxShadow:     '0 10px 40px rgba(0,0,0,.15), 0 4px 12px rgba(0,0,0,.1)',
            display:       'flex',
            alignItems:    'flex-start',
            gap:           '12px',
            minWidth:      '300px',
            maxWidth:      '400px',
            pointerEvents: 'auto',
            borderLeft:    `4px solid ${color}`,
            position:      'relative',
            overflow:      'hidden',
        });

        // ── icona ───────────────────────────────────
        const iconWrap = document.createElement('div');
        Object.assign(iconWrap.style, { color, fontSize: '1.3rem', flexShrink: '0', marginTop: '2px' });
        iconWrap.innerHTML = `<i class="fas ${ICONS[type] ?? ICONS.info}" aria-hidden="true"></i>`;

        // ── corpo ───────────────────────────────────
        const body = document.createElement('div');
        Object.assign(body.style, { flex: '1', minWidth: '0' });

        const titleEl = document.createElement('div');
        Object.assign(titleEl.style, { fontWeight: '600', color: '#1f2937', fontSize: '.95rem', marginBottom: '4px' });
        titleEl.textContent = String(title); // textContent → no XSS

        const msgEl = document.createElement('div');
        Object.assign(msgEl.style, { color: '#6b7280', fontSize: '.9rem', lineHeight: '1.5' });
        msgEl.textContent = String(message); // textContent → no XSS

        body.append(titleEl, msgEl);

        // ── pulsante chiudi ─────────────────────────
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Chiudi notifica');
        Object.assign(closeBtn.style, {
            background: 'none', border: 'none', color: '#9ca3af',
            cursor: 'pointer', fontSize: '1.1rem', padding: '4px',
            margin: '-4px', borderRadius: '8px', flexShrink: '0',
            transition: 'all .2s',
        });
        closeBtn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        closeBtn.addEventListener('mouseenter', () => {
            closeBtn.style.color = '#4b5563';
            closeBtn.style.background = '#f3f4f6';
        });
        closeBtn.addEventListener('mouseleave', () => {
            closeBtn.style.color = '#9ca3af';
            closeBtn.style.background = 'transparent';
        });
        closeBtn.addEventListener('click', () => dismiss(id));

        toast.append(iconWrap, body, closeBtn);

        // ── progress bar ────────────────────────────
        if (finalDuration > 0) {
            const progress = document.createElement('div');
            progress.className = 'yw-toast-progress';
            Object.assign(progress.style, {
                position: 'absolute', bottom: '0', left: '0',
                height: '3px', background: color, width: '100%',
                animation: `yw-toast-progress ${finalDuration}ms linear forwards`,
            });
            toast.appendChild(progress);
        }

        container.appendChild(toast);
        // Forziamo un reflow per garantire la transizione
        requestAnimationFrame(() => {
            toast.classList.remove('yw-toast--enter');
            toast.classList.add('yw-toast--shown');
        });

        // ── auto-dismiss ────────────────────────────
        if (finalDuration > 0) {
            scheduleDismiss(toast, finalDuration);

            toast.addEventListener('mouseenter', () => {
                clearScheduled(toast);
                const p = toast.querySelector('.yw-toast-progress');
                if (p) p.style.animationPlayState = 'paused';
            });
            toast.addEventListener('mouseleave', () => {
                const p = toast.querySelector('.yw-toast-progress');
                if (p) p.style.animationPlayState = 'running';
                scheduleDismiss(toast, finalDuration / 2);
            });
        }

        return id;
    };

    const scheduleDismiss = (toast, delay) => {
        clearScheduled(toast);
        TIMEOUTS.set(toast, setTimeout(() => dismiss(toast.id), delay));
    };

    const clearScheduled = (toast) => {
        const t = TIMEOUTS.get(toast);
        if (t) {
            clearTimeout(t);
            TIMEOUTS.delete(toast);
        }
    };

    const dismiss = (id) => {
        const toast = document.getElementById(id);
        if (!toast) return;

        clearScheduled(toast);
        toast.classList.remove('yw-toast--shown');
        toast.classList.add('yw-toast--leave');

        toast.addEventListener('transitionend', function onEnd() {
            toast.removeEventListener('transitionend', onEnd);
            toast.remove();
            if (container && container.children.length === 0) {
                container.remove();
                container = null;
            }
        }, { once: true });
    };

    const dismissAll = () => {
        if (!container) return;
        [...container.children].forEach((el) => dismiss(el.id));
    };

    const update = (id, message, type = 'info', duration = 3000) => {
        const toast = document.getElementById(id);
        if (!toast) return null;

        clearScheduled(toast);

        const color = COLORS[type] ?? COLORS.info;
        const [iconWrap, body] = toast.children;
        const [titleEl, msgEl] = body.children;

        iconWrap.innerHTML = `<i class="fas ${ICONS[type] ?? ICONS.info}" aria-hidden="true"></i>`;
        iconWrap.style.color = color;
        toast.style.borderLeftColor = color;
        titleEl.textContent = TITLES[type] ?? 'Notifica';
        msgEl.textContent = String(message);

        const oldProgress = toast.querySelector('.yw-toast-progress');
        if (oldProgress) oldProgress.remove();

        if (duration > 0 && type !== 'loading') {
            const progress = document.createElement('div');
            progress.className = 'yw-toast-progress';
            Object.assign(progress.style, {
                position: 'absolute', bottom: '0', left: '0',
                height: '3px', background: color, width: '100%',
                animation: `yw-toast-progress ${duration}ms linear forwards`,
            });
            toast.appendChild(progress);
            scheduleDismiss(toast, duration);
        }

        return id;
    };

    const promise = async (promiseFn, {
        loading = 'Caricamento in corso...',
        success = 'Operazione completata!',
        error   = 'Si è verificato un errore',
        duration = 3000,
    } = {}) => {
        const id = createToast(loading, 'loading');
        try {
            const result = await promiseFn();
            update(id, success, 'success', duration);
            return result;
        } catch (err) {
            update(id, err?.message || error, 'error', duration);
            throw err;
        }
    };

    // ──────────────────────────────────────────────────────────────
    // Espone API (idempotente)
    // ──────────────────────────────────────────────────────────────
    if (!window.Toast) {
        window.Toast = Object.freeze({
            success: (m, d, o) => createToast(m, 'success', d, o),
            error:   (m, d, o) => createToast(m, 'error',   d, o),
            warning: (m, d, o) => createToast(m, 'warning', d, o),
            info:    (m, d, o) => createToast(m, 'info',    d, o),
            loading: (m, o)    => createToast(m, 'loading', 0,  o),
            dismiss,
            dismissAll,
            update,
            promise,
        });
    }

    // Sostituto opzionale di alert()
    if (!window.showAlert) {
        window.showAlert = (message, type = 'info') => window.Toast[type]?.(message);
    }
})();
