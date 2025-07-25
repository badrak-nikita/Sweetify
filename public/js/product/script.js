document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.product-card').forEach(card => {
        const btns = card.querySelectorAll('.qty-btn');
        const quantityEl = card.querySelector('.qty-value');

        let quantity = 1;

        btns[0].addEventListener('click', () => {
            if (quantity > 1) {
                quantity--;
                quantityEl.textContent = quantity;
            }
        });

        btns[1].addEventListener('click', () => {
            quantity++;
            quantityEl.textContent = quantity;
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.product-card').forEach(card => {
        const slides = card.querySelectorAll('.slide');
        const prevBtn = card.querySelector('.slide-btn.prev');
        const nextBtn = card.querySelector('.slide-btn.next');

        if (!slides.length) return;

        let currentIndex = 0;

        const showSlide = (index) => {
            slides.forEach((img, i) => {
                img.classList.toggle('active', i === index);
            });
        };

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + slides.length) % slides.length;
                showSlide(currentIndex);
            });

            nextBtn.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % slides.length;
                showSlide(currentIndex);
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.product-card').forEach(card => {
        const minusBtn = card.querySelector('.qty-btn:first-of-type');
        const plusBtn = card.querySelector('.qty-btn:last-of-type');
        const qtyValue = card.querySelector('.qty-value');
        let quantity = 1;

        plusBtn.addEventListener('click', () => {
            quantity++;
            qtyValue.textContent = quantity;
        });

        minusBtn.addEventListener('click', () => {
            if (quantity > 1) {
                quantity--;
                qtyValue.textContent = quantity;
            }
        });

        document.dispatchEvent(new Event('cart-updated'));

        const addToCartBtn = card.querySelector('.add-to-cart');
        addToCartBtn.addEventListener('click', () => {
            const productId = addToCartBtn.dataset.id;
            const qty = quantity;

            fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    productId: productId,
                    quantity: qty
                })
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-sidebar').classList.add('open');
                    document.getElementById('cart-overlay').style.display = 'block';
                    loadCartItems();
                    updateCartCount();
                });
        });
    });
});
