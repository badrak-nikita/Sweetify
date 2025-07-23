const sliders = {};

function showSlide(productId, index) {
    const slider = document.getElementById('slider-' + productId);
    const slides = slider.querySelectorAll('.slide');

    if (!sliders[productId]) {
        sliders[productId] = 0;
    }

    if (index >= slides.length) {
        sliders[productId] = 0;
    } else if (index < 0) {
        sliders[productId] = slides.length - 1;
    } else {
        sliders[productId] = index;
    }

    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === sliders[productId]) {
            slide.classList.add('active');
        }
    });
}

function nextSlide(productId) {
    showSlide(productId, sliders[productId] + 1);
}

function prevSlide(productId) {
    showSlide(productId, sliders[productId] - 1);
}
