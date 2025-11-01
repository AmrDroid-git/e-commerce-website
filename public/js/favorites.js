document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.favorite-btn').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            const actionUrl = button.dataset.actionUrl;
            const csrfToken = button.dataset.csrfToken;
            const productId = button.dataset.productId;
            const addUrl = button.dataset.addUrl;
            const removeUrl = button.dataset.removeUrl;
            const addToken = button.dataset.addToken;
            const removeToken = button.dataset.removeToken;

            if (!actionUrl || !csrfToken || !productId) return;

            button.disabled = true;
            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ _token: csrfToken }),
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Could not update favorite.');
                }

                if (data.favorite) {
                    button.classList.remove('btn-outline-danger');
                    button.classList.add('btn-danger');
                    button.dataset.actionUrl = removeUrl;
                    button.dataset.csrfToken = removeToken;
                    button.innerHTML = '<i class="fas fa-heart"></i>';
                    button.setAttribute('aria-label', 'Remove from favorites');
                } else {
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-outline-danger');
                    button.dataset.actionUrl = addUrl;
                    button.dataset.csrfToken = addToken;
                    button.innerHTML = '<i class="far fa-heart"></i>';
                    button.setAttribute('aria-label', 'Add to favorites');
                }
            } catch (error) {
                console.error(error);
                alert(error.message || 'Could not update favorite.');
            } finally {
                button.disabled = false;
            }
        });
    });
});

# backdated-commit: 2025-11-01 00:00:00
