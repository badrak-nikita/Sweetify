document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filter-form');
    const searchInput = document.getElementById('search-input');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const productList = document.getElementById('product-list');

    function updateProducts() {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        fetch('/products?' + params, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newList = doc.querySelector('#product-list');
                if (newList) {
                    productList.innerHTML = newList.innerHTML;
                }
            });
    }

    searchInput.addEventListener('input', () => {
        updateProducts();
    });

    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            updateProducts();
        });
    });
});
