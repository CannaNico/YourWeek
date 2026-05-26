/**
 * Shared Utilities – YourWeek
 * ---------------------------------------------------------------
 * Fix principali:
 *   - Modernizzazione ES2020+ (arrow, optional chaining, ?? ).
 *   - generateId(): crypto.randomUUID() + fallback (substr deprecato).
 *   - formatRelativeTime: Math.floor invece di Math.ceil (più
 *     intuitivo: "1 ora fa", non "2 ore fa" per 1h 5min).
 *   - showFullScreenLoader: testo del messaggio inserito con
 *     textContent (no XSS).
 *   - saveToStorage: gestione QuotaExceededError dedicata.
 *   - detectColorScheme: niente listener duplicati (cleanup).
 *   - isMobile: priorità matchMedia, UA solo come fallback.
 *   - Namespace YW.utils, oltre a window.Utils per compatibilità.
 *   - Funzioni pure dichiarate fuori dall'oggetto (tree-shake-friendly).
 * ---------------------------------------------------------------
 */

(() => {
    'use strict';

    // ─── Helpers funzionali ───────────────────────────────────────

    const debounce = (fn, wait) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    };

    const throttle = (fn, limit) => {
        let lastCall = 0;
        let trailing;
        return function throttled(...args) {
            const now = Date.now();
            const remaining = limit - (now - lastCall);
            if (remaining <= 0) {
                lastCall = now;
                fn.apply(this, args);
            } else {
                clearTimeout(trailing);
                trailing = setTimeout(() => {
                    lastCall = Date.now();
                    fn.apply(this, args);
                }, remaining);
            }
        };
    };

    // ─── Date helpers ─────────────────────────────────────────────

    const formatDate = (date, options = {}) =>
        new Date(date).toLocaleDateString('it-IT', {
            day: '2-digit', month: 'short', year: 'numeric', ...options,
        });

    const formatTime = (date) =>
        new Date(date).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });

    const formatRelativeTime = (date) => {
        const now = new Date();
        const d = new Date(date);
        const diffMs = Math.abs(now - d);
        const diffMin  = Math.floor(diffMs / 60_000);
        const diffHour = Math.floor(diffMs / 3_600_000);
        const diffDay  = Math.floor(diffMs / 86_400_000);

        if (diffMin < 1)  return 'Ora';
        if (diffHour < 1) return `${diffMin} min fa`;
        if (diffDay < 1)  return `${diffHour} ${diffHour === 1 ? 'ora' : 'ore'} fa`;
        if (diffDay === 1) return 'Ieri';
        if (diffDay < 7)   return `${diffDay} giorni fa`;
        if (diffDay < 30)  return `${Math.floor(diffDay / 7)} settimane fa`;
        return formatDate(date);
    };

    // ─── String helpers ───────────────────────────────────────────

    const truncate = (str, length = 50, suffix = '...') =>
        !str || str.length <= length ? (str ?? '') : str.slice(0, length).trim() + suffix;

    const capitalize = (str) =>
        !str ? str : str[0].toUpperCase() + str.slice(1).toLowerCase();

    const generateId = (prefix = '') => {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return prefix + crypto.randomUUID();
        }
        return prefix + Date.now().toString(36) + Math.random().toString(36).slice(2, 11);
    };

    // ─── Storage helpers ──────────────────────────────────────────

    const saveToStorage = (key, data) => {
        try {
            localStorage.setItem(key, JSON.stringify(data));
            return true;
        } catch (e) {
            if (e && (e.name === 'QuotaExceededError' || e.code === 22)) {
                console.warn(`localStorage quota exceeded saving "${key}"`);
            } else {
                console.error('saveToStorage:', e);
            }
            return false;
        }
    };

    const getFromStorage = (key, defaultValue = null) => {
        try {
            const item = localStorage.getItem(key);
            return item === null ? defaultValue : JSON.parse(item);
        } catch (e) {
            console.error('getFromStorage:', e);
            return defaultValue;
        }
    };

    // ─── Clipboard ────────────────────────────────────────────────

    const copyToClipboard = async (text) => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch { /* fallback below */ }

        // Fallback per browser legacy / contesti non sicuri
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        Object.assign(ta.style, { position: 'fixed', opacity: '0', pointerEvents: 'none' });
        document.body.appendChild(ta);
        ta.select();
        try {
            return document.execCommand('copy');
        } catch (e) {
            console.error('copyToClipboard fallback:', e);
            return false;
        } finally {
            ta.remove();
        }
    };

    // ─── Fullscreen loader ────────────────────────────────────────

    const LOADER_ID = 'fullscreen-loader';

    const showFullScreenLoader = (message = 'Caricamento...') => {
        hideFullScreenLoader(); // evita duplicati

        const overlay = document.createElement('div');
        overlay.id = LOADER_ID;
        Object.assign(overlay.style, {
            position: 'fixed', inset: '0',
            background: 'rgba(15,15,26,.9)',
            backdropFilter: 'blur(10px)',
            zIndex: '99999',
            display: 'flex', flexDirection: 'column',
            alignItems: 'center', justifyContent: 'center',
            gap: '20px',
        });
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-live', 'polite');

        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner loading-spinner-lg';
        Object.assign(spinner.style, {
            borderColor: 'rgba(255,255,255,.1)',
            borderTopColor: '#6366f1',
        });

        const msg = document.createElement('p');
        Object.assign(msg.style, {
            color: '#fff', fontSize: '1.1rem', fontWeight: '500',
            animation: 'pulse 2s ease-in-out infinite',
        });
        msg.textContent = String(message); // textContent → niente XSS

        overlay.append(spinner, msg);
        document.body.appendChild(overlay);
    };

    const hideFullScreenLoader = () => {
        const loader = document.getElementById(LOADER_ID);
        if (!loader) return;
        loader.style.transition = 'opacity .3s';
        loader.style.opacity = '0';
        setTimeout(() => loader.remove(), 300);
    };

    // ─── Skeleton helpers ─────────────────────────────────────────

    const createSkeletonCard = () => `
        <div class="skeleton skeleton-card" style="height:150px;margin-bottom:16px;">
            <div style="padding:20px;">
                <div class="skeleton skeleton-text skeleton-text-sm" style="margin-bottom:12px;"></div>
                <div class="skeleton skeleton-text skeleton-text-lg"></div>
                <div class="skeleton skeleton-text skeleton-text-md"></div>
            </div>
        </div>`;

    const createSkeletonList = (count = 5) =>
        Array.from({ length: count }, () =>
            `<div class="skeleton" style="height:60px;margin-bottom:12px;border-radius:12px;"></div>`
        ).join('');

    // ─── Scroll animations ────────────────────────────────────────

    const animateOnScroll = (selector, animationClass = 'animate-fadeInUp') => {
        const elements = document.querySelectorAll(selector);
        if (!elements.length || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries, obs) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add(animationClass);
                    entry.target.style.opacity = '';
                    obs.unobserve(entry.target);
                }
            }
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        elements.forEach((el) => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    };

    // ─── Ripple effect ────────────────────────────────────────────

    const addRippleEffect = (selector = '.btn') => {
        document.querySelectorAll(selector).forEach((button) => {
            if (button.dataset.rippleBound) return; // evita doppi binding
            button.dataset.rippleBound = '1';

            button.addEventListener('click', (e) => {
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top  - size / 2;

                const ripple = document.createElement('span');
                Object.assign(ripple.style, {
                    position: 'absolute', borderRadius: '50%',
                    background: 'rgba(255,255,255,.4)',
                    width: `${size}px`, height: `${size}px`,
                    left: `${x}px`, top: `${y}px`,
                    animation: 'ripple .6s ease-out',
                    pointerEvents: 'none',
                });

                button.style.position ||= 'relative';
                button.style.overflow = 'hidden';
                button.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    };

    // ─── Validatori ───────────────────────────────────────────────

    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValidEmail = (email) => typeof email === 'string' && EMAIL_RE.test(email);

    /** ≥ 8 char, almeno una lettera, almeno una cifra */
    const isValidPassword = (pw) =>
        typeof pw === 'string' && pw.length >= 8 && /[a-zA-Z]/.test(pw) && /\d/.test(pw);

    // ─── Format ───────────────────────────────────────────────────

    const formatBytes = (bytes, decimals = 2) => {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), sizes.length - 1);
        const dm = Math.max(0, decimals);
        return `${parseFloat((bytes / k ** i).toFixed(dm))} ${sizes[i]}`;
    };

    // ─── DOM utilities ────────────────────────────────────────────

    const scrollToElement = (elementId, offset = 80) => {
        const el = document.getElementById(elementId);
        if (!el) return;
        const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top, behavior: 'smooth' });
    };

    const disableForm = (formSelector, disabled = true) => {
        const form = document.querySelector(formSelector);
        if (!form) return;
        form.querySelectorAll('input, button, select, textarea').forEach((el) => {
            if (disabled) {
                if (el.disabled) el.dataset.wasDisabled = '1';
                el.disabled = true;
            } else {
                if (el.dataset.wasDisabled === '1') {
                    el.disabled = true;
                    delete el.dataset.wasDisabled;
                } else {
                    el.disabled = false;
                }
            }
        });
    };

    const getUrlParams = () =>
        Object.fromEntries(new URLSearchParams(window.location.search));

    const setUrlParam = (key, value) => {
        const url = new URL(window.location);
        if (value === null || value === undefined) url.searchParams.delete(key);
        else url.searchParams.set(key, value);
        window.history.pushState({}, '', url);
    };

    // ─── Device detection ─────────────────────────────────────────

    const isMobile = () => {
        if (window.matchMedia?.('(pointer: coarse)').matches) return true;
        if (window.innerWidth <= 768) return true;
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    };

    const isTouch = () =>
        'ontouchstart' in window || (navigator.maxTouchPoints ?? 0) > 0;

    // ─── Color scheme ─────────────────────────────────────────────

    let colorSchemeMql = null;
    let colorSchemeListener = null;

    const detectColorScheme = () => {
        if (!window.matchMedia) return;

        if (colorSchemeMql && colorSchemeListener) {
            colorSchemeMql.removeEventListener('change', colorSchemeListener);
        }
        colorSchemeMql = window.matchMedia('(prefers-color-scheme: dark)');
        const apply = (matches) => document.body.classList.toggle('dark-theme', matches);
        apply(colorSchemeMql.matches);
        colorSchemeListener = (e) => apply(e.matches);
        colorSchemeMql.addEventListener('change', colorSchemeListener);
    };

    // ─── Init ─────────────────────────────────────────────────────

    const init = () => {
        detectColorScheme();

        if (!document.getElementById('shared-utils-styles')) {
            const style = document.createElement('style');
            style.id = 'shared-utils-styles';
            style.textContent = `
                @keyframes ripple {
                    to { transform: scale(2); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    };

    // ─── API pubblica ─────────────────────────────────────────────

    const Utils = Object.freeze({
        debounce, throttle,
        formatDate, formatTime, formatRelativeTime,
        truncate, capitalize, generateId,
        saveToStorage, getFromStorage,
        copyToClipboard,
        showFullScreenLoader, hideFullScreenLoader,
        createSkeletonCard, createSkeletonList,
        animateOnScroll, addRippleEffect,
        isValidEmail, isValidPassword,
        formatBytes,
        scrollToElement, disableForm,
        getUrlParams, setUrlParam,
        isMobile, isTouch,
        detectColorScheme,
        init,
    });

    window.YW = window.YW || {};
    window.YW.utils = Utils;
    window.Utils = Utils; // retro-compatibilità

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
