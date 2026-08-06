// Wrap this script in an isolated scope to avoid leaking globals.
(function () {
    var scrollKey = "afrisense:last-action-scroll";
    var lastTrackedAt = 0;

    // Run this branch only when the required UI state is present.
    if ("scrollRestoration" in window.history) {
        window.history.scrollRestoration = "manual";
    }

    // Defines the initFooterAccordions helper for this browser module.
    function initFooterAccordions() {
        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-footer-toggle]").forEach(function (toggle) {
            // Bind the UI event handler for this interactive control.
            toggle.addEventListener("click", function () {
                var section = toggle.closest("[data-footer-section]");
                // Run this branch only when the required UI state is present.
                if (section) {
                    section.classList.toggle("is-open");
                }
            });
        });
    }

    // Defines the normalizeUrl helper for this browser module.
    function normalizeUrl(url) {
        // Protect optional browser behavior from stopping the page script.
        try {
            return new URL(url, window.location.href);
        } catch (error) {
            return null;
        }
    }

    // Defines the shouldTrackUrl helper for this browser module.
    function shouldTrackUrl(url) {
        return url !== null && url.origin === window.location.origin && url.pathname === window.location.pathname;
    }

    // Defines the submitMethod helper for this browser module.
    function submitMethod(form, control) {
        return String(
            control && control.getAttribute("formmethod")
                ? control.getAttribute("formmethod")
                : form.getAttribute("method") || "get"
        ).toLowerCase();
    }

    // Defines the submitTargetUrl helper for this browser module.
    function submitTargetUrl(form, control) {
        var rawUrl = control && control.getAttribute("formaction")
            ? control.getAttribute("formaction")
            : form.getAttribute("action") || window.location.href;

        return normalizeUrl(rawUrl);
    }

    // Defines the saveScrollPosition helper for this browser module.
    function saveScrollPosition(targetUrl) {
        // Run this branch only when the required UI state is present.
        if (!shouldTrackUrl(targetUrl)) {
            return;
        }

        lastTrackedAt = Date.now();

        // Protect optional browser behavior from stopping the page script.
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

    // Defines the restoreScrollPosition helper for this browser module.
    function restoreScrollPosition() {
        var payload;

        // Protect optional browser behavior from stopping the page script.
        try {
            payload = JSON.parse(sessionStorage.getItem(scrollKey) || "null");
            sessionStorage.removeItem(scrollKey);
        } catch (error) {
            payload = null;
        }

        // Run this branch only when the required UI state is present.
        if (!payload || payload.path !== window.location.pathname || Date.now() > Number(payload.expiresAt || 0)) {
            return;
        }

        // Defines the restore helper for this browser module.
        function restore() {
            var top = Number(payload.y || 0);
            var left = Number(payload.x || 0);

            window.scrollTo({
                left: left,
                top: top,
                behavior: "auto"
            });

            // Run this branch only when the required UI state is present.
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

    // Defines the stripHashFromPostTarget helper for this browser module.
    function stripHashFromPostTarget(form, control) {
        var targetUrl;

        // Run this branch only when the required UI state is present.
        if (!form || submitMethod(form, control) !== "post") {
            return;
        }

        targetUrl = submitTargetUrl(form, control);

        // Run this branch only when the required UI state is present.
        if (!shouldTrackUrl(targetUrl) || !targetUrl.hash) {
            return;
        }

        targetUrl.hash = "";

        // Run this branch only when the required UI state is present.
        if (control && control.getAttribute("formaction")) {
            control.setAttribute("formaction", targetUrl.pathname + targetUrl.search);
        } else {
            form.setAttribute("action", targetUrl.pathname + targetUrl.search);
        }
    }

    // Defines the saveSubmitControlPosition helper for this browser module.
    function saveSubmitControlPosition(control) {
        var form = control && control.form;

        // Run this branch only when the required UI state is present.
        if (!form || form.hasAttribute("data-no-scroll-restore")) {
            return;
        }

        saveScrollPosition(submitTargetUrl(form, control));
        stripHashFromPostTarget(form, control);
    }

    // Defines the initActionScrollRestore helper for this browser module.
    function initActionScrollRestore() {
        // Bind the UI event handler for this interactive control.
        document.addEventListener("submit", function (event) {
            var form = event.target;
            var submitter = event.submitter || null;

            // Run this branch only when the required UI state is present.
            if (!(form instanceof HTMLFormElement) || form.hasAttribute("data-no-scroll-restore")) {
                return;
            }

            saveScrollPosition(submitTargetUrl(form, submitter));
            stripHashFromPostTarget(form, submitter);
        }, true);

        // Bind the UI event handler for this interactive control.
        document.addEventListener("pointerdown", function (event) {
            var control = event.target.closest("button, input[type='submit'], input[type='image']");

            // Run this branch only when the required UI state is present.
            if (!control || (control.tagName === "BUTTON" && control.type && control.type !== "submit")) {
                return;
            }

            saveSubmitControlPosition(control);
        }, true);

        // Bind the UI event handler for this interactive control.
        document.addEventListener("click", function (event) {
            var link = event.target.closest("a[href]");
            var submitControl = event.target.closest("button, input[type='submit'], input[type='image']");

            // Run this branch only when the required UI state is present.
            if (submitControl) {
                saveSubmitControlPosition(submitControl);
            }

            // Run this branch only when the required UI state is present.
            if (!link || link.hasAttribute("data-no-scroll-restore") || link.target === "_blank" || link.hasAttribute("download")) {
                return;
            }

            var href = link.getAttribute("href") || "";

            // Run this branch only when the required UI state is present.
            if (href.trim() === "#") {
                event.preventDefault();
                return;
            }

            // Run this branch only when the required UI state is present.
            if (href === "" || href.charAt(0) === "#" || /^(mailto|tel|javascript):/i.test(href)) {
                return;
            }

            saveScrollPosition(normalizeUrl(href));
        }, true);

        // Bind the UI event handler for this interactive control.
        window.addEventListener("beforeunload", function () {
            // Run this branch only when the required UI state is present.
            if (Date.now() - lastTrackedAt < 1000) {
                return;
            }

            saveScrollPosition(normalizeUrl(window.location.href));
        });
    }

    // Defines the fullscreenElement helper for this browser module.
    function fullscreenElement() {
        return document.fullscreenElement
            || document.webkitFullscreenElement
            || document.msFullscreenElement
            || null;
    }

    // Defines the requestFullscreen helper for this browser module.
    function requestFullscreen(element) {
        // Run this branch only when the required UI state is present.
        if (element.requestFullscreen) {
            return element.requestFullscreen();
        }

        // Run this branch only when the required UI state is present.
        if (element.webkitRequestFullscreen) {
            return element.webkitRequestFullscreen();
        }

        // Run this branch only when the required UI state is present.
        if (element.msRequestFullscreen) {
            return element.msRequestFullscreen();
        }

        return Promise.reject(new Error("Fullscreen is not supported."));
    }

    // Defines the exitFullscreen helper for this browser module.
    function exitFullscreen() {
        // Run this branch only when the required UI state is present.
        if (document.exitFullscreen) {
            return document.exitFullscreen();
        }

        // Run this branch only when the required UI state is present.
        if (document.webkitExitFullscreen) {
            return document.webkitExitFullscreen();
        }

        // Run this branch only when the required UI state is present.
        if (document.msExitFullscreen) {
            return document.msExitFullscreen();
        }

        return Promise.resolve();
    }

    // Defines the updateFullscreenButtons helper for this browser module.
    function updateFullscreenButtons() {
        var isFullscreen = fullscreenElement() !== null;

        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-fullscreen-toggle]").forEach(function (button) {
            var icon = button.querySelector(".bi");
            var label = button.querySelector("small");

            button.setAttribute("aria-label", isFullscreen ? "Exit fullscreen" : "Fullscreen");
            button.setAttribute("title", isFullscreen ? "Exit fullscreen" : "Toggle fullscreen");
            button.classList.toggle("is-fullscreen", isFullscreen);

            // Run this branch only when the required UI state is present.
            if (icon) {
                icon.classList.toggle("bi-fullscreen", !isFullscreen);
                icon.classList.toggle("bi-fullscreen-exit", isFullscreen);
            }

            // Run this branch only when the required UI state is present.
            if (label) {
                label.textContent = isFullscreen ? "Exit" : "Fullscreen";
            }
        });
    }

    // Defines the initFullscreenToggle helper for this browser module.
    function initFullscreenToggle() {
        // Find the page elements controlled by this script.
        document.querySelectorAll("[data-fullscreen-toggle]").forEach(function (button) {
            // Bind the UI event handler for this interactive control.
            button.addEventListener("click", function () {
                var target = document.documentElement;
                var action = fullscreenElement() ? exitFullscreen() : requestFullscreen(target);

                Promise.resolve(action).catch(function () {
                    button.classList.add("is-fullscreen-unavailable");
                    window.setTimeout(function () {
                        button.classList.remove("is-fullscreen-unavailable");
                    }, 900);
                });
            });
        });

        ["fullscreenchange", "webkitfullscreenchange", "msfullscreenchange"].forEach(function (eventName) {
            // Bind the UI event handler for this interactive control.
            document.addEventListener(eventName, updateFullscreenButtons);
        });

        updateFullscreenButtons();
    }

    // Bind the UI event handler for this interactive control.
    document.addEventListener("DOMContentLoaded", function () {
        initFooterAccordions();
        initActionScrollRestore();
        initFullscreenToggle();
        restoreScrollPosition();
    });

    // Bind the UI event handler for this interactive control.
    window.addEventListener("pageshow", function (event) {
        // Run this branch only when the required UI state is present.
        if (event.persisted) {
            restoreScrollPosition();
        }
    });

    document.documentElement.classList.add("js-ready");
})();
