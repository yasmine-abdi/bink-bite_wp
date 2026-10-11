<?php
/**
 * FastBite - Admin Footer Component
 * Step 23: FastBite Admin Menu/Food Management System
 */
$currentDirName = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
$relRoot = ($currentDirName === 'admin') ? '../' : '../../';
?>
    </div> <!-- /admin-layout -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- FastBite Main JS -->
    <script src="<?= $relRoot; ?>assets/js/main.js"></script>
</body>
</html>
