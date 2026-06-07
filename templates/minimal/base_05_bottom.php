<!-- Swiper JS (только если нужен) -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // Инициализация Swiper для галерей
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.swiper').forEach(container => {
            new Swiper(container, {
                loop: false,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                slidesPerView: 1,
                spaceBetween: 10,
                breakpoints: {
                    640: { slidesPerView: 1.2 },
                    768: { slidesPerView: 1.5 },
                    1024: { slidesPerView: 2 }
                }
            });
        });
    });
</script>

</body>
</html>