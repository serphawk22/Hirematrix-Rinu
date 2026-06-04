        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">

        <span class="navbar-brand fw-bold">HireMatrix Admin</span>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navMenu">
            <ul class="navbar-nav align-items-lg-center gap-2">

                <li class="nav-item">
                    <a class="nav-link <?= uri_string() == 'admin/dashboard' ? 'active' : '' ?>" href="<?= base_url('admin/dashboard') ?>">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= uri_string() == 'admin/users' ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">Users</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= uri_string() == 'admin/feedback' ? 'active' : '' ?>" href="<?= base_url('admin/feedback') ?>">Feedback</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= uri_string() == 'admin/companies' ? 'active' : '' ?>" href="<?= base_url('admin/companies') ?>">Companies</a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link <?= uri_string() == 'admin/jobs' ? 'active' : '' ?>" href="<?= base_url('admin/jobs') ?>">Jobs</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= str_starts_with(uri_string(), 'admin/blogs') ? 'active' : '' ?>" href="<?= base_url('admin/blogs') ?>">Blog</a>
                </li>

                <li class="nav-item">
                    <a class="btn btn-outline-danger btn-sm" href="<?= base_url('admin/logout') ?>">Logout</a>
                </li>

            </ul>
        </div>
    </div>
</nav>    