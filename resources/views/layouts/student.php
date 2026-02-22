<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="bg-body text-body font-display d-flex vh-100 overflow-hidden">
    <?php include __DIR__ . '/../partials/student-sidebar.php'; ?>
    <main class="d-flex flex-column flex-grow-1 overflow-hidden bg-body position-relative">
        <?php include __DIR__ . '/../partials/student-header.php'; ?>
        <div class="flex-grow-1 overflow-auto p-4 p-lg-5 custom-scrollbar">
            <div class="mx-auto d-flex flex-column gap-5" style="max-width: 1200px;">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
