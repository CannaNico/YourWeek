/**
 * cookie-banner.js – YourWeek
 * Gestione banner consenso cookie + cookie di sessione/login
 */

(function () {
    'use strict';

    // ─── Costanti ──────────────────────────────────────────────────
    const CONSENT_COOKIE   = 'yw_cookie_consent';   // accettazione banner
    const REMEMBER_COOKIE  = 'yw_remember';          // login persistente
    const CONSENT_DAYS     = 365;
    const REMEMBER_DAYS    = 30;

    // ─── Utility cookie ────────────────────────────────────────────

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setDate(expires.getDate() + days);
        document.cookie =
            encodeURIComponent(name) + '=' + encodeURIComponent(value) +
            '; expires=' + expires.toUTCString() +
            '; path=/; SameSite=Strict';
    }

    function getCookie(name) {
        const key = encodeURIComponent(name) + '=';
        const cookies = document.cookie.split(';');
        for (let c of cookies) {
            c = c.trim();
            if (c.indexOf(key) === 0) {
                return decodeURIComponent(c.substring(key.length));
            }
        }
        return null;
    }

    function deleteCookie(name) {
        document.cookie =
            encodeURIComponent(name) +
            '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Strict';
    }

    // ─── Stato consenso ────────────────────────────────────────────

    /** @returns {'accepted'|'declined'|null} */
    function getConsent() {
        return getCookie(CONSENT_COOKIE);
    }

    function cookiesAccepted() {
        return getConsent() === 'accepted';
    }

    // ─── Banner HTML ───────────────────────────────────────────────

    function createBanner() {
        const banner = document.createElement('div');
        banner.id = 'yw-cookie-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Consenso ai cookie');
        banner.innerHTML = `
            <div class="yw-banner-inner">
                <div class="yw-banner-icon">🍪</div>
                <div class="yw-banner-text">
                    <h3>YourWeek utilizza i cookie</h3>
                    <p>
                        Usiamo cookie tecnici necessari per il <strong>login</strong> e la 
                        <strong>gestione delle sessioni</strong>. Senza di essi non potrai 
                        accedere alla piattaforma.
                    </p>
                </div>
                <div class="yw-banner-actions">
                    <button class="yw-btn-decline" id="yw-btn-decline">Solo necessari</button>
                    <button class="yw-btn-accept" id="yw-btn-accept">✓ Accetta</button>
                </div>
            </div>`;
        return banner;
    }

    function showBanner() {
        // Inietta CSS se non già presente
        if (!document.getElementById('yw-cookie-banner-css')) {
            const link = document.createElement('link');
            link.id   = 'yw-cookie-banner-css';
            link.rel  = 'stylesheet';
            // Calcola il path relativo a cookie-banner.js
            const base = document.currentScript
                ? document.currentScript.src.replace('cookie-banner.js', '')
                : '';
            link.href = base + 'cookie-banner.css';
            document.head.appendChild(link);
        }

        const banner = createBanner();
        document.body.appendChild(banner);

        // Animazione entrata (piccolo delay per permettere il paint iniziale)
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                banner.classList.add('yw-banner-visible');
            });
        });

        // ── Accetta ────────────────────────────────────────────────
        document.getElementById('yw-btn-accept').addEventListener('click', function () {
            setCookie(CONSENT_COOKIE, 'accepted', CONSENT_DAYS);
            hideBanner(banner);
            // Emette evento custom per altri script che potrebbero ascoltare
            document.dispatchEvent(new CustomEvent('yw:cookieAccepted'));
        });

        // ── Rifiuta (solo cookie tecnici minimi) ───────────────────
        document.getElementById('yw-btn-decline').addEventListener('click', function () {
            setCookie(CONSENT_COOKIE, 'declined', CONSENT_DAYS);
            hideBanner(banner);
            document.dispatchEvent(new CustomEvent('yw:cookieDeclined'));
        });
    }

    function hideBanner(banner) {
        banner.classList.remove('yw-banner-visible');
        banner.addEventListener('transitionend', function () {
            banner.remove();
        }, { once: true });
    }

    // ─── Cookie "Ricordami" (login persistente) ────────────────────

    /**
     * Imposta il cookie "ricordami" dopo il login.
     * Chiama questa funzione dal form di login se il checkbox è spuntato.
     *
     * @param {string} token  Token sicuro restituito dal server dopo il login
     */
    function setRememberMe(token) {
        if (cookiesAccepted()) {
            setCookie(REMEMBER_COOKIE, token, REMEMBER_DAYS);
        }
    }

    /**
     * Legge il token "ricordami" salvato, se esiste.
     * @returns {string|null}
     */
    function getRememberToken() {
        return getCookie(REMEMBER_COOKIE);
    }

    /**
     * Elimina il cookie "ricordami" (usato al logout).
     */
    function clearRememberMe() {
        deleteCookie(REMEMBER_COOKIE);
    }

    // ─── Init ──────────────────────────────────────────────────────

    function init() {
        // Mostra il banner solo se l'utente non ha ancora risposto
        if (getConsent() === null) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showBanner);
            } else {
                showBanner();
            }
        }
    }

    // ─── Esposizione pubblica ──────────────────────────────────────

    window.YWCookies = {
        isAccepted:       cookiesAccepted,
        getConsent:       getConsent,
        setRememberMe:    setRememberMe,
        getRememberToken: getRememberToken,
        clearRememberMe:  clearRememberMe,
        setCookie:        setCookie,
        getCookie:        getCookie,
        deleteCookie:     deleteCookie
    };

    init();
})();
