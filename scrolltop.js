document.addEventListener("DOMContentLoaded", function() {
    const btn = document.createElement("button");
    btn.id = "btn-back-to-top";
    btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
    btn.title = "Volver arriba";
    document.body.appendChild(btn);

    let lastScrollContainer = window;

    window.addEventListener("scroll", function(e) {
        lastScrollContainer = e.target === document ? window : e.target;
        const scrollTop = lastScrollContainer.scrollTop || window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        
        if (scrollTop > 300) {
            btn.style.display = "flex";
        } else {
            btn.style.display = "none";
        }
    }, true); // El true (capture phase) es vital para detectar scrolls en divs internos

    btn.addEventListener("click", function() {
        try {
            if (lastScrollContainer && typeof lastScrollContainer.scrollTo === 'function') {
                lastScrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.documentElement.scrollTo({ top: 0, behavior: 'smooth' });
            document.body.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            // Fallback for older browsers
            if (lastScrollContainer) lastScrollContainer.scrollTop = 0;
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
        }
    });
});
