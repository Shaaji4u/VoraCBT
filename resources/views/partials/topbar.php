<header class="navbar navbar-expand bg-surface border-bottom px-4 py-2" style="height: 64px;">
    <div class="d-flex align-items-center gap-3 w-100">
        <button class="btn btn-link text-secondary d-md-none p-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
            <span class="material-symbols-outlined fs-3">menu</span>
        </button>

        <nav aria-label="breadcrumb" class="d-none d-sm-block">
            <ol class="breadcrumb mb-0 small fw-medium">
                <li class="breadcrumb-item"><a href="/" class="text-decoration-none text-secondary hover-text-primary">Home</a></li>
                <li class="breadcrumb-item active text-body" aria-current="page"><?= $breadcrumb ?? 'Dashboard' ?></li>
            </ol>
        </nav>

        <div class="ms-auto d-flex align-items-center gap-4">
            <!-- Search -->
            <div class="position-relative d-none d-lg-block" style="width: 320px;">
                <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary fs-5">search</span>
                <input type="text" class="form-control bg-body-secondary border-0 ps-5 rounded-3" placeholder="Search exams, students...">
            </div>

            <!-- Notification -->
            <button class="btn btn-light btn-sm rounded-circle p-2 position-relative text-secondary hover-text-dark">
                <span class="material-symbols-outlined fs-5">notifications</span>
                <span class="position-absolute top-0 end-0 m-2 p-1 bg-danger border border-white rounded-circle"></span>
            </button>

            <!-- User Profile -->
            <div class="d-flex align-items-center gap-3 ps-3 border-start">
                <div class="text-end d-none d-sm-block lh-sm">
                    <p class="mb-0 small fw-bold text-body"><?= $userName ?? 'Admin User' ?></p>
                    <span class="text-secondary x-small" style="font-size: 0.7rem;"><?= $userRole ?? 'Head Teacher' ?></span>
                </div>
                <div class="avatar-circle border border-2 border-white shadow-sm" style="width: 40px; height: 40px; background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCJKcUpb8cVxs1PUT7YOH5R1P99ZYKlmwGuTtUyBC6-JP5SOyGyvDOIoBpLq5sWi2tncKQeZJyxRiJZUe9IzPjtrfOJgTQp8FkuFZ2l4a3cPNPthvxIK2ELurJitvhAXEA92h5mVuSP1bgM7qOBfXGBbeyWEcmiYGtWeCIhqRx7GbX9g5xiyo_mA8wKZxDOgje9Lnd-MUxQQaBKFkk4mE1FlA96dJnFUWBJuRyjrqEQlTxf2xLWzGnBeCS6XoKvT5sIJAxUjQbhzcY');"></div>
            </div>
        </div>
    </div>
</header>
