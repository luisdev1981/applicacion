<?php
// sistema-inces/templates/pie.php

// Definimos $show_navbar por defecto para evitar errores si no se establece antes
if (!isset($show_navbar)) {
    $show_navbar = true;
}
?>

<?php if ($show_navbar): ?>
    </div> <!-- Cierre de .main-content -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pequeño script para activar el enlace del menú correspondiente a la página actual
    document.addEventListener("DOMContentLoaded", function() {
        const currentUrl = window.location.href;
        const navLinks = document.querySelectorAll('.sidebar .nav-link');

        navLinks.forEach(link => {
            if (currentUrl.includes(link.getAttribute('href'))) {
                link.classList.add('active');
                link.style.fontWeight = 'bold';
                link.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            }
        });
    });
</script>
</body>
</html>
