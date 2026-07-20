(function () {
    var scrollKey = "afrisense:last-action-scroll";
    var lastTrackedAt = 0;

    if ("scrollRestoration" in window.history) {
        window.history.scrollRestoration = "manual";
    }

    function initFooterAccordions() {
        document.querySelectorAll("[data-footer-toggle]").forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var section = toggle.closest("[data-footer-section]");
                if (section) {
                    section.classList.toggle("is-open");
                }
            });
        });
    }

    function normalizeUrl(url) {
        try {
            return new URL(url, window.location.href);
        } catch (error) {
            return null;
        }
    }

    function shouldTrackUrl(url) {
        return url !== null && url.origin === window.location.origin && url.pathname === window.location.pathname;
    }

    function saveScrollPosition(targetUrl) {
        if (!shouldTrackUrl(targetUrl)) {
            return;
        }

        lastTrackedAt = Date.now();

        try {
            var scrollingElement = document.scrollingElement || document.documentElement;

            sessionStorage.setItem(scrollKey, JSON.stringify({
                path: targetUrl.pathname,
                x: window.scrollX || window.pageXOffset || scrollingElement.scrollLeft || 0,
                y: window.scrollY || window.pageYOffset || scrollingElement.scrollTop || 0,
                expiresAt: Date.now() + 30000
            }));
        } catch (error) {
            // Ignore storage failures; the action should still continue normally.
        }
    }

    function restoreScrollPosition() {
        var payload;

        try {
            payload = JSON.parse(sessionStorage.getItem(scrollKey) || "null");
            sessionStorage.removeItem(scrollKey);
        } catch (error) {
            payload = null;
        }

        if (!payload || payload.path !== window.location.pathname || Date.now() > Number(payload.expiresAt || 0)) {
            return;
        }

        function restore() {
            var top = Number(payload.y || 0);
            var left = Number(payload.x || 0);

            window.scrollTo({
                left: left,
                top: top,
                behavior: "auto"
            });

            if (document.scrollingElement) {
                document.scrollingElement.scrollTop = top;
                document.scrollingElement.scrollLeft = left;
            }
        }

        restore();
        window.requestAnimationFrame(function () {
            restore();
            window.setTimeout(restore, 80);
            window.setTimeout(restore, 220);
            window.setTimeout(restore, 600);
        });
    }

    function saveSubmitControlPosition(control) {
        var form = control && control.form;

        if (!form || form.hasAttribute("data-no-scroll-restore")) {
            return;
        }

        saveScrollPosition(normalizeUrl(form.getAttribute("action") || window.location.href));
    }

    function initActionScrollRestore() {
        document.addEventListener("submit", function (event) {
            var form = event.target;

            if (!(form instanceof HTMLFormElement) || form.hasAttribute("data-no-scroll-restore")) {
                return;
            }

            saveScrollPosition(normalizeUrl(form.getAttribute("action") || window.location.href));
        }, true);

        document.addEventListener("pointerdown", function (event) {
            var control = event.target.closest("button, input[type='submit'], input[type='image']");

            if (!control || (control.tagName === "BUTTON" && control.type && control.type !== "submit")) {
                return;
            }

            saveSubmitControlPosition(control);
        }, true);

        document.addEventListener("click", function (event) {
            var link = event.target.closest("a[href]");
            var submitControl = event.target.closest("button, input[type='submit'], input[type='image']");

            if (submitControl) {
                saveSubmitControlPosition(submitControl);
            }

            if (!link || link.hasAttribute("data-no-scroll-restore") || link.target === "_blank" || link.hasAttribute("download")) {
                return;
            }

            var href = link.getAttribute("href") || "";

            if (href === "" || href.charAt(0) === "#" || /^(mailto|tel|javascript):/i.test(href)) {
                return;
            }

            saveScrollPosition(normalizeUrl(href));
        }, true);

        window.addEventListener("beforeunload", function () {
            if (Date.now() - lastTrackedAt < 1000) {
                return;
            }

            saveScrollPosition(normalizeUrl(window.location.href));
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initFooterAccordions();
        initActionScrollRestore();
        restoreScrollPosition();
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            restoreScrollPosition();
        }
    });

    document.documentElement.classList.add("js-ready");
})();
