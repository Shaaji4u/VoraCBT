<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="bg-body text-body min-vh-100 d-flex flex-column theme-login font-display">
    <?= $content ?? '' ?>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
