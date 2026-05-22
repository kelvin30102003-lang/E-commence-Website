(function () {
    var isNavigating = false;

    function isSameOriginUrl(url) {
        try {
            var parsed = new URL(url, window.location.href);
            return parsed.origin === window.location.origin;
        } catch (error) {
            return false;
        }
    }

    function shouldIgnoreLink(anchor, event) {
        if (!anchor) {
            return true;
        }

        if (event.defaultPrevented || event.button !== 0) {
            return true;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        var href = anchor.getAttribute('href');
        if (!href || href.indexOf('javascript:') === 0) {
            return true;
        }

        if (anchor.target && anchor.target !== '_self') {
            return true;
        }

        if (anchor.hasAttribute('download') || anchor.getAttribute('data-no-ajax') === 'true') {
            return true;
        }

        if (href.indexOf('#') === 0) {
            return true;
        }

        return !isSameOriginUrl(href);
    }

    function replaceLayoutFromDocument(nextDoc) {
        var currentMain = document.querySelector('#app-main');
        var nextMain = nextDoc.querySelector('#app-main');

        if (!currentMain || !nextMain) {
            return false;
        }

        var currentSidebar = document.querySelector('[data-admin-sidebar]');
        var nextSidebar = nextDoc.querySelector('[data-admin-sidebar]');

        if ((currentSidebar && !nextSidebar) || (!currentSidebar && nextSidebar)) {
            return false;
        }

        if (currentSidebar && nextSidebar) {
            currentSidebar.replaceWith(nextSidebar);
        }

        currentMain.replaceWith(nextMain);
        document.title = nextDoc.title || document.title;

        executeInlineScripts(nextMain);
        return true;
    }

    function executeInlineScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function (script) {
            var nextScript = document.createElement('script');
            Array.from(script.attributes).forEach(function (attribute) {
                nextScript.setAttribute(attribute.name, attribute.value);
            });
            nextScript.textContent = script.textContent || '';
            script.parentNode.replaceChild(nextScript, script);
        });
    }

    function fetchPage(url, options) {
        var settings = options || {};
        var method = settings.method || 'GET';
        var body = settings.body || null;
        var pushState = settings.pushState !== false;

        if (isNavigating) {
            return;
        }
        isNavigating = true;

        fetch(url, {
            method: method,
            body: body,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            var contentType = response.headers.get('content-type') || '';

            if (contentType.indexOf('text/html') === -1) {
                window.location.href = response.url || url;
                return null;
            }

            return response.text().then(function (html) {
                return {
                    html: html,
                    finalUrl: response.url || url,
                    pushState: pushState
                };
            });
        }).then(function (payload) {
            if (!payload) {
                return;
            }

            var parser = new DOMParser();
            var nextDoc = parser.parseFromString(payload.html, 'text/html');
            var replaced = replaceLayoutFromDocument(nextDoc);

            if (!replaced) {
                window.location.href = payload.finalUrl;
                return;
            }

            if (payload.pushState) {
                window.history.pushState({ ajax: true }, '', payload.finalUrl);
            }

            window.scrollTo({ top: 0, behavior: 'auto' });
        }).catch(function () {
            window.location.href = url;
        }).finally(function () {
            isNavigating = false;
        });
    }

    document.addEventListener('click', function (event) {
        var anchor = event.target.closest('a');
        if (shouldIgnoreLink(anchor, event)) {
            return;
        }

        event.preventDefault();
        var href = anchor.getAttribute('href');
        fetchPage(href, { method: 'GET', pushState: true });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        if (form.getAttribute('data-no-ajax') === 'true') {
            return;
        }

        event.preventDefault();

        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        var action = form.getAttribute('action') || window.location.href;
        var formData = new FormData(form);

        if (method === 'GET') {
            var query = new URLSearchParams(formData).toString();
            var url = action.split('#')[0];
            url += (url.indexOf('?') === -1 ? '?' : '&') + query;
            fetchPage(url, { method: 'GET', pushState: true });
            return;
        }

        fetchPage(action, {
            method: method,
            body: formData,
            pushState: true
        });
    });

    window.addEventListener('popstate', function () {
        fetchPage(window.location.href, { method: 'GET', pushState: false });
    });
})();
