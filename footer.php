</main> <!-- /container -->

<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row">
            <!-- Brand / About -->
            <div class="col-md-4 col-lg-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-3 text-primary">MTP Store</h5>
                <p class="text-secondary small">
                    Elevating your style with curated fashion collections. 
                    Quality meets affordability in every piece we offer.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 col-lg-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '/MTP_ND' ?>/index.php" class="text-decoration-none text-secondary hover-white">Home</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '/MTP_ND' ?>/index.php?cat=male" class="text-decoration-none text-secondary hover-white">Men's Collection</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= defined('BASE_URL') ? BASE_URL : '/MTP_ND' ?>/index.php?cat=female" class="text-decoration-none text-secondary hover-white">Women's Collection</a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-md-4 col-lg-4 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">Contact</h5>
                <ul class="list-unstyled text-secondary small">
                    <li class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-primary"></i> 123 Fashion Ave, New York, NY</li>
                    <li class="mb-2"><i class="bi bi-envelope-fill me-2 text-primary"></i> support@mtpstore.com</li>
                    <li class="mb-2"><i class="bi bi-telephone-fill me-2 text-primary"></i> +1 (555) 123-4567</li>
                </ul>
                <div class="mt-3">
                    <a href="#" class="text-white me-3 fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3 fs-5"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start text-secondary small">
                &copy; <?= date('Y') ?> MTP Store. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="#" class="text-secondary text-decoration-none small me-3">Privacy Policy</a>
                <a href="#" class="text-secondary text-decoration-none small">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Optional: Your custom JS file -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/main.js"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addToCartForm', (config) => ({
        productId: config.productId,
        hasSizes: config.hasSizes,
        hasColors: config.hasColors,
        size: '',
        color: '',
        loading: false,
        errorMessage: '',
        feedbackMessage: '',
        submit() {
            this.errorMessage = '';
            if (this.hasSizes && !this.size) {
                this.errorMessage = 'Please select a size.';
                return;
            }
            if (this.hasColors && !this.color) {
                this.errorMessage = 'Please select a color.';
                return;
            }

            this.loading = true;

            const formData = new FormData();
            formData.append('product_id', this.productId);
            formData.append('quantity', 1);
            formData.append('size', this.size || '');
            formData.append('color', this.color || '');
            formData.append('ajax', '1');

            fetch(`${config.baseUrl}/cart/add_to_cart.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                    this.feedbackMessage = 'Added!';
                    setTimeout(() => this.feedbackMessage = '', 2000);
                } else {
                    this.errorMessage = data.message || 'Could not add to cart.';
                }
            })
            .catch(() => this.errorMessage = 'An unexpected error occurred.')
            .finally(() => this.loading = false);
        }
    }));
});
</script>
</body>
</html>
