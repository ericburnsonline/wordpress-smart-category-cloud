(function () {
    'use strict';

    function initSmartCategoryCloud() {
        var roots = document.querySelectorAll('.scc');

        if (!roots.length) {
            return;
        }

        roots.forEach(function (root) {
            var terms = root.querySelectorAll('.scc-term');

            if (!terms.length) {
                return;
            }

            terms.forEach(function (term) {
                term.addEventListener('click', function () {
                    var categoryUrl = new URL(term.href, window.location.origin);
                    var selectedCategory = categoryUrl.searchParams.get('scc_cat');

                    if (!selectedCategory) {
                        return;
                    }

                    try {
                        var currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('scc_cat', selectedCategory);
                        window.history.replaceState({}, '', currentUrl.toString());
                    } catch (e) {
                        // Safe no-op fallback for older browsers or URL parsing issues.
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmartCategoryCloud);
    } else {
        initSmartCategoryCloud();
    }
})();
