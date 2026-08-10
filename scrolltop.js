document.addEventListener("DOMContentLoaded", function() {
    const btn = document.createElement("button");
    btn.id = "btn-back-to-top";
    btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
    btn.title = "Volver arriba";
    document.body.appendChild(btn);

    window.addEventListener("scroll", function() {
        if (window.pageYOffset > 300) {
            btn.style.display = "flex";
        } else {
            btn.style.display = "none";
        }
    });

    btn.addEventListener("click", function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
