    </div>
    <!-- End Main Content -->

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Metro Rail</h5>
                    <p>
                        A comprehensive metro rail booking and management system designed to make urban travel easy and efficient.
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i> contact@metrorail.com<br>
                        <i class="fas fa-phone me-2"></i> (123) 456-7890
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>" class="text-light">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/schedule.php" class="text-light">Schedules</a></li>
                        <li><a href="<?php echo APP_URL; ?>/stations.php" class="text-light">Stations</a></li>
                        <li><a href="<?php echo APP_URL; ?>/fare.php" class="text-light">Fare Calculator</a></li>
                        <li><a href="<?php echo APP_URL; ?>/contact.php" class="text-light">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-icons">
                        <a href="#" class="text-light me-2"><i class="fab fa-facebook-square fa-2x"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-twitter-square fa-2x"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-linkedin fa-2x"></i></a>
                    </div>
                    <div class="mt-3">
                        <h6>Subscribe to our newsletter</h6>
                        <form class="d-flex">
                            <input type="email" class="form-control form-control-sm me-2" placeholder="Email address">
                            <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Metro Rail. All rights reserved.</p>
            </div>
        </div>
    </footer>    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?php echo APP_URL; ?>/js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Apply hover effects to all cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('shadow-lg');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('shadow-lg');
                });
            });
            
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            
            // Initialize popovers
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
        });
    </script>
    
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
