<footer class="bg-white border-top mt-5 py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Holidaze -->
            <div class="col-md-3">
                <h3 class="h5 fw-bold text-primary mb-3">Holidaze</h3>
                <p class="text-muted small">Pour des séjours inoubliables, partout en France.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-secondary text-decoration-none fs-5">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="text-secondary text-decoration-none fs-5">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="text-secondary text-decoration-none fs-5">
                        <i class="bi bi-twitter"></i>
                    </a>
                </div>
            </div>

            <!-- Découvrir -->
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">Découvrir</h4>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Destinations</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Nouveautés</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Expériences</a>
                    </li>
                </ul>
            </div>

            <!-- Propriétaires -->
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">Propriétaires</h4>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <a href="/Pages/Bien/bien_form.php" class="text-muted text-decoration-none hover-link">Publier un bien</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Tarifs</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Conseils</a>
                    </li>
                </ul>
            </div>

            <!-- Assistance -->
            <div class="col-md-3">
                <h4 class="h6 fw-semibold mb-3">Assistance</h4>
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Centre d'aide</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">Contact</a>
                    </li>
                    <li class="mb-2">
                        <a href="#" class="text-muted text-decoration-none hover-link">CGU</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ligne de séparation -->
        <hr class="my-4">

        <!-- Footer bottom -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-muted small">
            <span>© <?= date('Y') ?> Holidaze. Tous droits réservés.</span>
            <span class="mt-2 mt-md-0">
                Fait avec <i class="bi bi-heart-fill text-danger"></i> pour les voyageurs
            </span>
        </div>
    </div>
</footer>

<style>
    footer {
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    }
    
    .hover-link:hover {
        color: #2563eb !important;
        transition: color 0.3s ease;
    }
    
    footer a i:hover {
        color: #2563eb !important;
        transform: scale(1.1);
        transition: all 0.3s ease;
    }
    
    /* Pour que le footer reste en bas */
    html, body {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    body > footer {
        margin-top: auto;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>