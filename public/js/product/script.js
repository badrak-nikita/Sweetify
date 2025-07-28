document.addEventListener('DOMContentLoaded', () => {
    const productList = document.getElementById('product-list');

    if (!productList) return;

    productList.addEventListener('click', (e) => {
        const btn = e.target.closest('.qty-btn');
        if (!btn) return;

        const card = btn.closest('.product-card');
        const qtyValue = card.querySelector('.qty-value');
        let quantity = parseInt(qtyValue.textContent, 10) || 1;

        if (btn.textContent.trim() === '+' || btn.classList.contains('plus')) {
            quantity++;
        } else {
            quantity = Math.max(1, quantity - 1);
        }

        qtyValue.textContent = quantity;
    });

    productList.addEventListener('click', (e) => {
        const btn = e.target.closest('.flip-btn');
        if (!btn) return;

        const card = btn.closest('.product-card');
        card.classList.toggle('flipped');
    });

    productList.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-to-cart');
        if (!btn) return;

        const card = btn.closest('.product-card');
        const qty = parseInt(card.querySelector('.qty-value').textContent, 10) || 1;
        const productId = btn.dataset.id;

        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ productId, quantity: qty })
        })
            .then(res => res.json())
            .then(() => {
                document.getElementById('cart-sidebar').classList.add('open');
                document.getElementById('cart-overlay').style.display = 'block';
                loadCartItems();
                updateCartCount();
            });
    });

    productList.addEventListener('click', (e) => {
        const btn = e.target.closest('.slide-btn');
        if (!btn) return;

        const card = btn.closest('.product-card');
        const slides = card.querySelectorAll('.slide');
        if (!slides.length) return;

        let currentIndex = Array.from(slides).findIndex(slide => slide.classList.contains('active'));

        if (btn.classList.contains('prev')) {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        } else {
            currentIndex = (currentIndex + 1) % slides.length;
        }

        slides.forEach((img, i) => {
            img.classList.toggle('active', i === currentIndex);
        });
    });
});
