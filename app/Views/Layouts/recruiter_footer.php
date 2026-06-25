</main>
 
<style>
body.recruiter-jobboard table thead th,
body.recruiter-jobboard .table thead th,
body.recruiter-jobboard .recruiter-jobs-table thead th,
body.recruiter-jobboard .recruiter-candidates-table thead th,
body.recruiter-jobboard .recruiter-slots-table thead th,
body.recruiter-jobboard .pipeline-table thead th,
body.recruiter-jobboard .conversion-table thead th,
body.recruiter-jobboard .ai-modal #aiReportContent .table thead th {
    font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    line-height: 1.3 !important;
    letter-spacing: 0.04em !important;
}

body.recruiter-jobboard table tbody td,
body.recruiter-jobboard .table tbody td,
body.recruiter-jobboard .recruiter-jobs-table tbody td,
body.recruiter-jobboard .recruiter-candidates-table tbody td,
body.recruiter-jobboard .recruiter-slots-table tbody td,
body.recruiter-jobboard .pipeline-table tbody td,
body.recruiter-jobboard .conversion-table tbody td,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td {
    font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
    font-size: 13.5px !important;
    line-height: 1.45 !important;
}

body.recruiter-jobboard table tbody td strong,
body.recruiter-jobboard table tbody td .font-weight-bold,
body.recruiter-jobboard .table tbody td strong,
body.recruiter-jobboard .table tbody td .font-weight-bold,
body.recruiter-jobboard .recruiter-jobs-table tbody td strong,
body.recruiter-jobboard .recruiter-candidates-table tbody td strong,
body.recruiter-jobboard .pipeline-table tbody td strong {
    font-size: 14px !important;
    line-height: 1.35 !important;
    font-weight: 700 !important;
}

body.recruiter-jobboard table tbody td small,
body.recruiter-jobboard table tbody td .text-muted,
body.recruiter-jobboard .table tbody td small,
body.recruiter-jobboard .table tbody td .text-muted,
body.recruiter-jobboard .recruiter-jobs-table tbody td small,
body.recruiter-jobboard .recruiter-jobs-table tbody td .text-muted,
body.recruiter-jobboard .recruiter-candidates-table tbody td small,
body.recruiter-jobboard .recruiter-candidates-table tbody td .text-muted,
body.recruiter-jobboard .recruiter-slots-table tbody td small,
body.recruiter-jobboard .recruiter-slots-table tbody td .text-muted,
body.recruiter-jobboard .pipeline-table tbody td small,
body.recruiter-jobboard .pipeline-table tbody td .text-muted,
body.recruiter-jobboard .conversion-table tbody td small,
body.recruiter-jobboard .conversion-table tbody td .text-muted,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td small,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td .text-muted {
    font-size: 12.5px !important;
    line-height: 1.4 !important;
}
</style>

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
<?php foreach ((array) ($pageScripts ?? []) as $pageScript): ?>
    <?php if (is_string($pageScript) && trim($pageScript) !== ''): ?>
        <script src="<?= esc($pageScript, 'attr') ?>"></script>
    <?php endif; ?>
<?php endforeach; ?>

</div>
</body>
</html>