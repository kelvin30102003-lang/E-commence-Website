(() => {
    "use strict";

    if (window.__luvshopAdminAjaxInitialized === true) {
        return;
    }
    window.__luvshopAdminAjaxInitialized = true;

    const MAIN_ID = "app-main";
    const SIDEBAR_SELECTOR = '[data-admin-sidebar="true"]';
    const AJAX_SELECTOR = '[data-ajax="true"]';
    const CACHE_TTL_MS = 20000;
    const cache = new Map();
    const loadedExternalScripts = new Set();
    let activeNavigationToken = 0;

    const parser = new DOMParser();

    function isJavaScriptType(type) {
        if (!type) {
            return true;
        }
        const normalized = String(type).trim().toLowerCase();
        return normalized === "text/javascript" || normalized === "application/javascript" || normalized === "module";
    }

    function normalizeUrl(url) {
        try {
            const parsed = new URL(url, window.location.href);
            parsed.hash = "";
            return parsed.toString();
        } catch (_error) {
            return null;
        }
    }

    function isAdminUrl(url) {
        try {
            const parsed = new URL(url);
            if (parsed.origin !== window.location.origin) {
                return false;
            }
            return /\/Admin(\/|$)/i.test(parsed.pathname);
        } catch (_error) {
            return false;
        }
    }

    function getEventTargetElement(event) {
        const target = event ? event.target : null;
        if (target instanceof Element) {
            return target;
        }
        if (target instanceof Node && target.parentElement instanceof Element) {
            return target.parentElement;
        }
        return null;
    }

    function setLoadingState(isLoading) {
        document.documentElement.classList.toggle("admin-ajax-loading", isLoading);
    }

    function setCache(url, html) {
        cache.set(url, {
            html,
            ts: Date.now(),
        });
    }

    function getCache(url) {
        const item = cache.get(url);
        if (!item) {
            return null;
        }

        if ((Date.now() - item.ts) > CACHE_TTL_MS) {
            cache.delete(url);
            return null;
        }

        return item.html;
    }

    async function fetchDocument(url, useCache = true) {
        if (useCache) {
            const cachedHtml = getCache(url);
            if (typeof cachedHtml === "string" && cachedHtml !== "") {
                return parser.parseFromString(cachedHtml, "text/html");
            }
        }

        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 2500);
        let response;
        try {
            response = await fetch(url, {
                credentials: "same-origin",
                signal: controller.signal,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-Admin-Ajax": "1",
                },
            });
        } finally {
            window.clearTimeout(timeout);
        }

        if (!response.ok) {
            throw new Error("Failed to fetch page: " + response.status);
        }

        const html = await response.text();
        setCache(url, html);
        return parser.parseFromString(html, "text/html");
    }

    async function runPageScripts(nextDoc, sourceUrl) {
        const scripts = Array.from(nextDoc.body.querySelectorAll("script"));

        for (const script of scripts) {
            const src = script.getAttribute("src");
            if (src) {
                const resolvedSrc = new URL(src, sourceUrl).toString();

                if (resolvedSrc.includes("/Assect/js/site-ajax.js") || resolvedSrc.includes("/Assect/js/tailwindcss-local.js")) {
                    continue;
                }

                if (loadedExternalScripts.has(resolvedSrc)) {
                    continue;
                }

                await new Promise((resolve) => {
                    const scriptNode = document.createElement("script");
                    scriptNode.src = resolvedSrc;
                    scriptNode.async = false;
                    scriptNode.onload = () => resolve();
                    scriptNode.onerror = () => resolve();
                    document.body.appendChild(scriptNode);
                });
                loadedExternalScripts.add(resolvedSrc);
                continue;
            }

            const type = script.getAttribute("type");
            if (!isJavaScriptType(type)) {
                continue;
            }

            const code = script.textContent || "";
            if (code.trim() === "") {
                continue;
            }

            try {
                (0, eval)(code);
            } catch (error) {
                console.error("Admin AJAX inline script error:", error);
            }
        }
    }

    function replaceShell(nextDoc) {
        const currentMain = document.getElementById(MAIN_ID);
        const nextMain = nextDoc.getElementById(MAIN_ID);
        if (!currentMain || !nextMain) {
            throw new Error("Main container missing");
        }

        const currentSidebar = document.querySelector(SIDEBAR_SELECTOR);
        const nextSidebar = nextDoc.querySelector(SIDEBAR_SELECTOR);
        if (currentSidebar && nextSidebar) {
            currentSidebar.replaceWith(nextSidebar.cloneNode(true));
        }

        currentMain.replaceWith(nextMain.cloneNode(true));

        if (typeof nextDoc.title === "string" && nextDoc.title !== "") {
            document.title = nextDoc.title;
        }
    }

    async function navigate(url, options = {}) {
        const targetUrl = normalizeUrl(url);
        if (!targetUrl || !isAdminUrl(targetUrl)) {
            window.location.href = url;
            return;
        }

        const navigationToken = ++activeNavigationToken;
        const shouldReplaceHistory = options.replace === true;
        const shouldScrollTop = options.scrollTop !== false;

        setLoadingState(true);

        try {
            const nextDoc = await fetchDocument(targetUrl, true);
            if (navigationToken !== activeNavigationToken) {
                return;
            }

            replaceShell(nextDoc);
            await runPageScripts(nextDoc, targetUrl);

            if (!shouldReplaceHistory) {
                window.history.pushState({ url: targetUrl }, "", targetUrl);
            }

            if (shouldScrollTop) {
                window.scrollTo({ top: 0, behavior: "auto" });
            }
        } catch (_error) {
            window.location.href = targetUrl;
        } finally {
            if (navigationToken === activeNavigationToken) {
                setLoadingState(false);
            }
        }
    }

    function onLinkClick(event) {
        const targetElement = getEventTargetElement(event);
        if (!targetElement) {
            return;
        }

        const anchor = targetElement.closest("a" + AJAX_SELECTOR);
        if (!anchor) {
            return;
        }

        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const href = anchor.getAttribute("href") || "";
        if (href === "" || href.startsWith("#")) {
            return;
        }

        if (anchor.getAttribute("target") === "_blank" || anchor.hasAttribute("download")) {
            return;
        }

        const targetUrl = normalizeUrl(href);
        if (!targetUrl || !isAdminUrl(targetUrl)) {
            return;
        }

        event.preventDefault();
        navigate(targetUrl);
    }

    function onFormSubmit(event) {
        const targetElement = getEventTargetElement(event);
        if (!targetElement) {
            return;
        }

        const form = targetElement.closest("form" + AJAX_SELECTOR);
        if (!form) {
            return;
        }

        const method = (form.getAttribute("method") || "get").trim().toLowerCase();
        if (method !== "get") {
            return;
        }

        const action = form.getAttribute("action") || window.location.href;
        const actionUrl = new URL(action, window.location.href);
        if (!isAdminUrl(actionUrl.toString())) {
            return;
        }

        const formData = new FormData(form);
        const searchParams = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            searchParams.append(key, String(value));
        }
        actionUrl.search = searchParams.toString();
        actionUrl.hash = "";

        event.preventDefault();
        navigate(actionUrl.toString());
    }

    function onPrefetchIntent(event) {
        return;
    }

    window.addEventListener("popstate", () => {
        navigate(window.location.href, { replace: true, scrollTop: false });
    });

    document.addEventListener("click", onLinkClick);
    document.addEventListener("submit", onFormSubmit);
    document.addEventListener("mouseenter", onPrefetchIntent, true);
    document.addEventListener("focusin", onPrefetchIntent, true);
})();
