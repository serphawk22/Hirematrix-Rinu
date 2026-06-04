               
<footer class="site-footer">
    <a href="#top" class="smoothscroll scroll-top">
        <span class="icon-keyboard_arrow_up"></span>
    </a>
    <div class="container">
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h3>Recruiter</h3>
                <ul class="list-unstyled">
                    <li><a href="<?= base_url('recruiter/dashboard') ?>">Dashboard</a></li>
                    <li><a href="<?= base_url('recruiter/jobs') ?>">My Jobs</a></li>
                    <li><a href="<?= base_url('recruiter/post_job') ?>">Post Job</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h3>Hiring</h3>
                <ul class="list-unstyled">
                    <li><a href="<?= base_url('recruiter/jobs') ?>">Applications</a></li>
                    <li><a href="<?= base_url('recruiter/candidates') ?>">Candidate Database</a></li>
                    <li><a href="<?= base_url('recruiter/slots') ?>">Interview Slots</a></li>
                    <li><a href="<?= base_url('recruiter/slots/bookings') ?>">Interview Bookings</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h3>Analytics</h3>
                <ul class="list-unstyled">
                    <li><a href="<?= base_url('recruiter/dashboard/export-excel') ?>">Export Data</a></li>
                    <li><a href="<?= base_url('feedback') ?>">Feedback</a></li>
                    <li><a href="<?= base_url('logout') ?>">Logout</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 mb-4 mb-md-0">
                <h3>Tools</h3>
                <div class="translate-widget-wrap mb-3">
                    <?= view('components/google_translate_widget') ?>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <p class="copyright">
                        <small>Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- SCRIPTS -->
<script src="<?= base_url('jobboard/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/isotope.pkgd.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/stickyfill.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.fancybox.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.easing.1.3.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.waypoints.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.animateNumber.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/owl.carousel.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/bootstrap-select.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/custom.js') ?>"></script>
<script src="<?= base_url('jobboard/js/recruiter-pages.js') ?>"></script>
<script src="<?= base_url('jobboard/js/notification-actions.js?v=' . @filemtime(FCPATH . 'jobboard/js/notification-actions.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $scriptSrc): ?>
<script src="<?= esc($scriptSrc) ?>"></script>
<?php endforeach; ?>

</div>
</body>
</html>


        
