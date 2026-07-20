<script>
    (function () {
        function sameOrigin(url) {
            try {
                return new URL(url, window.location.origin).origin === window.location.origin;
            } catch (e) {
                return false;
            }
        }

        function isBlockedPath(pathname) {
            const blocked = [
                '/login',
                '/register',
                '/logout',
                '/forgot-password',
                '/reset-password',
                '/community/posts',
                '/community/comments',
            ];
            return blocked.some((p) => pathname === p || pathname.startsWith(p + '/'));
        }

        function pathOf(url) {
            try {
                return new URL(url, window.location.origin).pathname;
            } catch (e) {
                return '';
            }
        }

        /** Append current page as ?from= for deep links that must restore list/tabs state. */
        window.appWithFrom = function (url) {
            if (!url) return url;
            try {
                const current = window.location.pathname + window.location.search;
                const hashIdx = String(url).indexOf('#');
                const hash = hashIdx >= 0 ? String(url).slice(hashIdx) : '';
                const base = hashIdx >= 0 ? String(url).slice(0, hashIdx) : String(url);
                const target = new URL(base, window.location.origin);
                if (!target.searchParams.has('from')) {
                    target.searchParams.set('from', current);
                }
                return target.pathname + target.search + hash;
            } catch (err) {
                return url;
            }
        };

        window.appNavigateWithFrom = function (url) {
            window.location.href = window.appWithFrom(url);
        };

        document.addEventListener('click', function (e) {
            const el = e.target.closest('[data-back-nav]');
            if (!el) return;

            const from = el.getAttribute('data-back-from');
            const fallback = el.getAttribute('data-back-fallback') || el.getAttribute('href');

            if (from && sameOrigin(from) && !isBlockedPath(pathOf(from))) {
                e.preventDefault();
                window.location.href = from;
                return;
            }

            const ref = document.referrer;
            if (ref && sameOrigin(ref) && window.history.length > 1) {
                try {
                    const refUrl = new URL(ref);
                    if (!isBlockedPath(refUrl.pathname)) {
                        e.preventDefault();
                        window.history.back();
                        return;
                    }
                } catch (err) {}
            }

            if (fallback && !isBlockedPath(pathOf(fallback))) {
                e.preventDefault();
                window.location.href = fallback;
            }
        });
    })();
</script>
