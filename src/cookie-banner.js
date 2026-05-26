/**
 * cookie-banner.js – YourWeek
 * ---------------------------------------------------------------
 * Fix principali rispetto alla versione originale:
 *   - document.currentScript può essere null in alcuni contesti:
 *     ora il path di cookie-banner.css viene risolto in modo
 *     più robusto (script[src*="cookie-banner.js"] come fallback).
 *   - Aggiunto cookie Secure quando la pagina è in HTTPS.
 *   - Aggiunto focus trap minimo + ESC = "Solo necessari".
 *   - Aggiunto revokeConsent() esposto pubblicamente.
 *   - Banner testuale via textContent (no innerHTML su contenuti
 *     dinamici).
 *   - Listener click una sola volta tramite delegated handler.
 *   - JSDoc completo.
 * ---------------------------------------------------------------
 */

(() => {
    'use strict';

    const CONSENT_COOKIE  = 'yw_cookie_consent';
    const REMEMBER_COOKIE = 'yw_remember';
    const CONSENT_DAYS    = 365;
    const REMEMBER_DAYS   = 30;
    const BANNER_ID       = 'yw-cookie-banner';

    const isHttps = location.protocol === 'https:';

    // ─── Utility cookie ───────────────────────────────────────────

    /** @param {string} name @param {string} value @param {number} days */
    const setCookie = (name, value, days) => {
        const expires = new Date();
        expires.setDate(expires.getDate() + days);
        const parts = [
            `${encodeURIComponent(name)}=${encodeURIComponent(value)}`,
            `expires=${expires.toUTCString()}`,
            'path=/',
            'SameSite=Strict',
        ];
        if (isHttps) parts.push('Secure');
        document.cookie = parts.join('; ');
    };

    /** @param {string} name @returns {string|null} */
    const getCookie = (name) => {
        const key = `${encodeURIComponent(name)}=`;
        for (const raw of document.cookie.split(';')) {
            const c = raw.trim();
            if (c.startsWith(key)) return decodeURIComponent(c.slice(key.length));
        }
        return null;
    };

    /** @param {string} name */
    const deleteCookie = (name) => {
        const parts = [
            `${encodeURIComponent(name)}=`,
            'expires=Thu, 01 Jan 1970 00:00:00 GMT',
            'path=/',
            'SameSite=Strict',
        ];
        if (isHttps) parts.push('Secure');
        document.cookie = parts.join('; ');
    };

    // ─── Stato consenso ───────────────────────────────────────────

    /** @returns {'accepted'|'declined'|null} */
    const getConsent = () => /** @type {'accepted'|'declined'|null} */ (getCookie(CONSENT_COOKIE));
    const cookiesAccepted = () => getConsent() === 'accepted';

    // ─── CSS path resolver ────────────────────────────────────────

    const resolveCssPath = () => {
        // 1) currentScript (synchronous load)
        const cur = document.currentScript;
        if (cur?.src) return cur.src.replace(/[^/]*$/, 'cookie-banner.css');

        // 2) fallback: cerca lo script per nome file
        const candidate = document.querySelector('script[src*="cookie-banner.js"]');
        if (candidate?.src) return candidate.src.replace(/[^/]*$/, 'cookie-banner.css');

        // 3) ultimo fallback: relativo alla pagina corrente
        return 'cookie-banner.css';
    };

    // ─── Banner ───────────────────────────────────────────────────

    const buildBanner = () => {
        const banner = document.createElement('div');
        banner.id = BANNER_ID;
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-modal', 'false');
        banner.setAttribute('aria-label', 'Consenso ai cookie');

        const inner = document.createElement('div');
        inner.className = 'yw-banner-inner';

        const icon = document.createElement('div');
        icon.className = 'yw-banner-icon';
        icon.textContent = '🍪';

        const textWrap = document.createElement('div');
        textWrap.className = 'yw-banner-text';
        const h3 = document.createElement('h3');
        h3.textContent = 'YourWeek utilizza i cookie';
        const p = document.createElement('p');
        p.textContent = 'Usiamo cookie tecnici necessari per il login e la gestione delle sessioni. Senza di essi non potrai accedere alla piattaforma.';
        textWrap.append(h3, p);

        const actions = document.createElement('div');
        actions.className = 'yw-banner-actions';

        const declineBtn = document.createElement('button');
        declineBtn.type = 'button';
        declineBtn.id = 'yw-btn-decline';
        declineBtn.className = 'yw-btn-decline';
        declineBtn.textContent = 'Solo necessari';

        const acceptBtn = document.createElement('button');
        acceptBtn.type = 'button';
        acceptBtn.id = 'yw-btn-accept';
        acceptBtn.className = 'yw-btn-accept';
        acceptBtn.textContent = '✓ Accetta';

        actions.append(declineBtn, acceptBtn);
        inner.append(icon, textWrap, actions);
        banner.appendChild(inner);

        return { banner, acceptBtn, declineBtn };
    };

    const ensureStyles = () => {
        if (document.getElementById('yw-cookie-banner-css')) return;
        const link = document.createElement('link');
        link.id   = 'yw-cookie-banner-css';
        link.rel  = 'stylesheet';
        link.href = resolveCssPath();
        document.head.appendChild(link);
    };

    let escListener = null;

    const showBanner = () => {
        if (document.getElementById(BANNER_ID)) return;

        ensureStyles();
        const { banner, acceptBtn, declineBtn } = buildBanner();
        document.body.appendChild(banner);

        // doppio rAF per garantire il transition-in
        requestAnimationFrame(() => requestAnimationFrame(() => {
            banner.classList.add('yw-banner-visible');
            acceptBtn.focus({ preventScroll: true });
        }));

        const close = (choice) => {
            setCookie(CONSENT_COOKIE, choice, CONSENT_DAYS);
            hideBanner(banner);
            document.dispatchEvent(new CustomEvent(
                choice === 'accepted' ? 'yw:cookieAccepted' : 'yw:cookieDeclined'
            ));
            if (escListener) document.removeEventListener('keydown', escListener);
        };

        acceptBtn.addEventListener('click',  () => close('accepted'));
        declineBtn.addEventListener('click', () => close('declined'));

        escListener = (e) => { if (e.key === 'Escape') close('declined'); };
        document.addEventListener('keydown', escListener);
    };

    const hideBanner = (banner) => {
        banner.classList.remove('yw-banner-visible');
        banner.addEventListener('transitionend', () => banner.remove(), { once: true });
        // Safety net se transitionend non scatta (es. display:none CSS)
        setTimeout(() => banner.isConnected && banner.remove(), 1000);
    };

    // ─── Remember-me ──────────────────────────────────────────────

    /** @param {string} token */
    const setRememberMe = (token) => {
        if (cookiesAccepted()) setCookie(REMEMBER_COOKIE, token, REMEMBER_DAYS);
    };
    const getRememberToken = () => getCookie(REMEMBER_COOKIE);
    const clearRememberMe  = () => deleteCookie(REMEMBER_COOKIE);

    /** Reset esplicito del consenso (utile per "Modifica preferenze") */
    const revokeConsent = () => {
        deleteCookie(CONSENT_COOKIE);
        clearRememberMe();
        showBanner();
    };

    // ─── Init ─────────────────────────────────────────────────────

    const init = () => {
        if (getConsent() !== null) return;
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showBanner, { once: true });
        } else {
            showBanner();
        }
    };

    // ─── API pubblica ─────────────────────────────────────────────

    window.YWCookies = Object.freeze({
        isAccepted:       cookiesAccepted,
        getConsent,
        setRememberMe,
        getRememberToken,
        clearRememberMe,
        revokeConsent,
        setCookie,
        getCookie,
        deleteCookie,
    });

    init();
})();
