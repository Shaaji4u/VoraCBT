<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="font-display bg-body text-body overflow-hidden">
    <div class="d-flex vh-100 overflow-hidden bg-body">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="d-flex flex-column flex-grow-1 overflow-hidden position-relative">
            <?php include __DIR__ . '/../partials/topbar.php'; ?>
            <div class="flex-grow-1 overflow-auto p-4 p-lg-5 scroll-smooth">
                <div class="container-xl px-0 d-flex flex-column gap-5">
                    <?= $content ?? '' ?>
                    <?php include __DIR__ . '/../partials/footer.php'; ?>
                </div>
            </div>
        </main>
    </div>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
