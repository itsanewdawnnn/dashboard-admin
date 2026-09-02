        </div>
    </div>
<?php if (!empty($_SESSION['login_toast_message'])): ?>
    <link rel="stylesheet" href="/assets/extensions/toastify-js/src/toastify.css">
<?php endif; ?>
    <script src="/assets/static/js/components/dark.js"></script>
    <script src="/assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="/assets/compiled/js/app.js"></script>
<script>
    // IIFE supaya variabel di dalamnya tidak bocor jadi global.
    (function () {
        var searchInput   = document.getElementById('globalSearchInput');
        var resultsBox    = document.getElementById('globalSearchResults');
        var searchWrapper = document.getElementById('globalSearchWrapper');

        if (!searchInput || !resultsBox || !searchWrapper) {
            return;
        }

        var groupLabels = <?= json_encode(getMenuGroupLabels(), JSON_UNESCAPED_UNICODE) ?>;

        var debounceTimer   = null;
        var latestRequestId = 0;

        function hideResults() {
            resultsBox.classList.remove('show');
        }

        function showResults() {
            resultsBox.classList.add('show');
        }

        function clearResults() {
            while (resultsBox.firstChild) {
                resultsBox.removeChild(resultsBox.firstChild);
            }
        }

        // createElement + textContent (bukan innerHTML) supaya data dari database aman.
        function buildResultItem(item) {
            var link = document.createElement('a');
            link.className = 'dropdown-item py-2';
            link.href = item.link;

            var title = document.createElement('div');
            title.className = 'fw-bold text-truncate';
            title.textContent = item.title;

            var subtitle = document.createElement('div');
            subtitle.className = 'text-muted text-sm text-truncate';
            subtitle.textContent = item.subtitle;

            link.appendChild(title);
            link.appendChild(subtitle);

            return link;
        }

        function buildGroupHeader(groupKey) {
            var header = document.createElement('h6');
            header.className = 'dropdown-header';
            header.textContent = groupLabels[groupKey];

            return header;
        }

        function buildEmptyMessage() {
            var message = document.createElement('p');
            message.className = 'text-muted text-center text-sm mb-0 px-3 py-2';
            message.textContent = 'Tidak ada hasil ditemukan.';

            return message;
        }

        function renderResults(data) {
            clearResults();

            var groupKeys  = Object.keys(groupLabels);
            var totalCount = 0;

            for (var i = 0; i < groupKeys.length; i++) {
                var groupKey   = groupKeys[i];
                var groupItems = data[groupKey] || [];

                if (groupItems.length === 0) {
                    continue;
                }

                totalCount = totalCount + groupItems.length;

                resultsBox.appendChild(buildGroupHeader(groupKey));

                for (var j = 0; j < groupItems.length; j++) {
                    resultsBox.appendChild(buildResultItem(groupItems[j]));
                }
            }

            if (totalCount === 0) {
                resultsBox.appendChild(buildEmptyMessage());
            }

            showResults();
        }

        function runSearch(keyword) {
            latestRequestId = latestRequestId + 1;
            var thisRequestId = latestRequestId;

            fetch('/includes/global-search.php?q=' + encodeURIComponent(keyword))
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    // Respons fetch() bisa datang tidak berurutan -- abaikan kalau sudah usang.
                    if (thisRequestId !== latestRequestId) {
                        return;
                    }

                    renderResults(data);
                })
                .catch(function () {
                    if (thisRequestId !== latestRequestId) {
                        return;
                    }

                    clearResults();
                    hideResults();
                });
        }

        searchInput.addEventListener('input', function () {
            var keyword = searchInput.value.trim();

            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
            }

            if (keyword.length < 2) {
                hideResults();
                return;
            }

            debounceTimer = setTimeout(function () {
                runSearch(keyword);
            }, 300);
        });

        document.addEventListener('click', function (event) {
            if (!searchWrapper.contains(event.target)) {
                hideResults();
            }
        });

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideResults();
            }
        });
    })();
</script>
<?php if (!empty($_SESSION['login_toast_message'])): ?>
    <script src="/assets/extensions/toastify-js/src/toastify.js"></script>
    <script>
        // Sapaan sekali saat login, posisi top-right dengan tombol close.
        Toastify({
            text: <?= json_encode($_SESSION['login_toast_message']) ?>,
            duration: 5000,
            close: true,
            gravity: 'top',
            position: 'right',
        }).showToast();
    </script>
<?php
    unset($_SESSION['login_toast_message']);
endif;
?>
</body>
</html>
