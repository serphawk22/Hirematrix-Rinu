        <?= view('Layouts/candidate_header', ['title' => 'Companies']) ?>
<?php
$totalCompanies = (int) ($totalCompanies ?? 0);
$totalOpenJobs  = (int) ($totalOpenJobs ?? 0);
$filters        = $filters ?? [];
?>

<div class="job-details-jobboard companies-directory-page companies-directory-jobboard">
    <div class="container">
        <div class="company-profile-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-building"></i> Employer directory</span>
                <h1 class="page-board-title">Explore Companies</h1>
                <p class="page-board-subtitle">Search employers, compare company profiles, and discover open roles.</p>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div class="detail-card companies-search-card mb-4">
                <div class="panel-body company-search-panel">
                    <form id="companySearchForm" method="get" action="<?= base_url('companies') ?>">
                        <div class="row align-items-end company-search-grid">
                            <div class="col-12 col-md-6">
                                <label class="small text-muted text-uppercase font-weight-bold">Search Company</label>
                                <input id="companySearchInput" name="q" type="text" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search company, e.g. HubSpot, Shopify, Infosys..." autocomplete="off" />
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="small text-muted text-uppercase font-weight-bold">Industry</label>
                                <select name="industry" class="form-control">
                                    <option value="">All industries</option>
                                    <?php foreach (($industries ?? []) as $industryOption): ?>
                                        <option value="<?= esc($industryOption) ?>" <?= ($filters['industry'] ?? '') === $industryOption ? 'selected' : '' ?>>
                                            <?= esc($industryOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="small text-muted text-uppercase font-weight-bold">Location</label>
                                <input type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="City or HQ">
                            </div>
                            <div class="col-12 col-md-auto company-search-actions">
                                <button type="submit" class="btn btn-primary company-search-btn">
                                    <i class="fas fa-search mr-1"></i> Search
                                </button>
                                <a href="<?= base_url('companies') ?>" class="btn btn-outline-secondary company-search-btn">Clear</a>
                            </div>
                        </div>
                    </form>
                    <div class="search-help-line">
                        <i class="fas fa-robot mr-1"></i>
                        AI fetches company details, headquarters, description, and live job count from the official careers page.
                    </div>
                </div>
            </div>
 <script>
document.addEventListener("DOMContentLoaded", function(){

    if (typeof jQuery === "undefined") {
        console.error("jQuery NOT loaded");
        return;
    }

    let $ = jQuery;
    let timer;
    $("#companySearchInput").on("keyup", function(){
        clearTimeout(timer);

        let term = $(this).val();

        timer = setTimeout(() => {

            if(term.length < 2){
                $("#suggestions").remove();
                return;
            }

            $.get("<?= base_url('companies/suggest') ?>", { term: term }, function(data){

                $("#suggestions").remove();

                if(!data.length) return;

                let html = `
                <ul id="suggestions" class="list-group shadow" style="
                    position:absolute;
                    top:100%;
                    left:0;
                    right:0;
                    z-index:9999;
                    background:#fff;
                ">`;

                data.forEach(item => {
                    html += `<li class="list-group-item suggestion-item" data-value="${item.value}">
                                ${item.label}
                             </li>`;
                });

                html += "</ul>";

                $("#companySearchInput").parent().append(html);

            });

        }, 300);

    });

    $(document).on("click", ".suggestion-item", function(){
        $("#companySearchInput").val($(this).data("value"));
        $("#suggestions").remove();
    });

    $(document).on("click", function(e){
        if(!$(e.target).closest("#companySearchInput, #suggestions").length){
            $("#suggestions").remove();
        }
    });

});
</script>
            <style>
            .company-search-panel {
    position: relative;
}

#companySearchInput {
    position: relative;
    z-index: 2;
}

/* 🔥 Dropdown container */
#suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 9999;
    background: #ffffff;
    border-radius: 12px;
    margin-top: 6px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid #e6eaf2;
    overflow: hidden;
    animation: fadeIn 0.15s ease-in-out;
}

/* 🔥 Each item */
#suggestions li {
    cursor: pointer;
    padding: 12px 14px;
    font-size: 14px;
    color: #2d3748;
    border-bottom: 1px solid #f1f3f7;
    transition: all 0.2s ease;
}

/* remove last border */
#suggestions li:last-child {
    border-bottom: none;
}

/* 🔥 Hover effect */
#suggestions li:hover {
    background: #f7faff;
    color: #4dc000;
    padding-left: 18px;
}

/* 🔥 Active (keyboard support ready) */
#suggestions li.active {
    background: #eaf2ff;
    color: #4dc000;
}

/* 🔥 Smooth animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
                .companies-search-card { background:#fff; border:1px solid #e9edf8; border-radius:22px; box-shadow:0 14px 35px rgba(15,23,42,.05); }
                .company-search-panel { padding:1.6rem 1.6rem 1.25rem; }
                .company-search-grid>[class*="col-"] { margin-bottom:1rem; }
                .company-search-grid label { margin-bottom:.45rem; letter-spacing:.04em; }
                .company-search-actions { display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; margin-bottom:1rem; }
                .company-search-btn { min-width:120px; height:48px; border-radius:14px; white-space:nowrap; }
                .search-help-line { color:#748097; font-size:.9rem; display:flex; align-items:center; padding-top:.35rem; }
                .company-directory-card { border:1px solid #e8edf8; border-radius:18px; padding:22px; background:#fff; box-shadow:0 12px 26px rgba(15,23,42,.045); display:flex; flex-direction:column; min-height:100%; }
                .company-directory-actions { margin-top:auto; display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
                .company-directory-tags { margin-top:.75rem; }
                .company-directory-tags .badge { background:#eef2ff; color:#2f4bbd; font-weight:600; }
                .empty-state { text-align:center; padding:3rem 2rem; border:1px solid #e9edf8; border-radius:18px; background:#fff; color:#5b6476; }
                .empty-state i { font-size:3rem; margin-bottom:1rem; color:#4f6dca; }
                .searching-state { border:1px solid #e9edf8; border-radius:18px; background:#fff; box-shadow:0 12px 26px rgba(15,23,42,.04); padding:1.25rem 1.4rem; display:flex; align-items:center; gap:1rem; }
                .searching-state__icon { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#f0f6ff 0%,#e8f7f4 100%); color:#0b66ff; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
                .searching-state__text h5 { margin-bottom:.2rem; }
                .searching-state__text p { margin-bottom:0; color:#677487; }
                @media(max-width:575px) { .company-search-panel{padding:1.2rem 1rem 1rem;} .company-search-actions{width:100%;} .company-search-btn{flex:1 1 140px;} }
            </style>

            <?php if (empty($companies)): ?>
                <?php if (!empty(trim((string) ($filters['q'] ?? '')))): ?>
                    <div id="companySearchResults" class="mt-4">
                        <div class="searching-state mb-4">
                            <div class="searching-state__icon">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div class="searching-state__text">
                                <h5>Searching AI for <strong><?= esc($filters['q']) ?></strong></h5>
                                <p>No registered company matched. Checking the official careers site for company details and live jobs.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <h5>No companies found</h5>
                        <p>Try a different company name, location, or industry filter.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="companies-directory-grid company-directory-grid mb-4">
                    <?php foreach ($companies as $company): ?>
                        <?php
                        $companyName    = (string) ($company['name'] ?? 'Company');
                        $companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
                        $companyIndustry = trim((string) ($company['industry'] ?? ''));
                        $companyHq      = trim((string) ($company['hq'] ?? ''));
                        $companySize    = trim((string) ($company['size'] ?? ''));
                        $openJobsCount  = (int) ($company['open_jobs_count'] ?? 0);
                        ?>
                        <article class="job-card company-directory-card" data-company-id="<?= (int) $company['id'] ?>" data-company-name="<?= esc($companyName) ?>">
                            <div class="job-card-icon company-directory-logo">
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?= base_url($company['logo']) ?>" alt="<?= esc($companyName) ?>">
                                <?php else: ?>
                                    <span><?= esc($companyInitial) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="job-card-title">
                                <a href="<?= base_url('company/' . (int) $company['id']) ?>"><?= esc($companyName) ?></a>
                            </h3>
                            <p class="job-card-company"><?= esc($companyIndustry ?: 'Industry not specified') ?></p>
                            <div class="job-card-meta company-directory-meta">
                                <span><i class="fas fa-map-pin"></i> <?= esc($companyHq ?: 'HQ not specified') ?></span>
                                <span class="company-job-count">
                                    <i class="fas fa-briefcase"></i>
                                    <span class="company-job-count-number"><?= $openJobsCount ?></span>
                                    <span class="company-job-count-label">open jobs</span>
                                </span>
                            </div>
                            <div class="job-card-tags company-directory-tags">
                                <span class="badge badge-primary"><?= esc($companySize ?: 'Size not specified') ?></span>
                            </div>
                            <div class="company-directory-actions">
                                <a href="<?= base_url('jobs?company=' . urlencode($companyName)) ?>"
                                   class="company-directory-jobs-link">
                                    <i class="fas fa-briefcase mr-1"></i> <span class="jobs-link-text">See live jobs</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
                    <div class="row pagination-wrap mt-4">
                        <div class="col-md-6 text-center text-md-left mb-3 mb-md-0">
                            <span>Showing page <?= $pager->getCurrentPage() ?> of <?= $pager->getPageCount() ?></span>
                        </div>
                        <div class="col-md-6 text-center text-md-right">
                            <div class="custom-pagination ml-auto">
                                <?php
                                $cur   = $pager->getCurrentPage();
                                $total = $pager->getPageCount();
                                $base  = preg_replace('/[?&]page=\d+/', '', current_url(true)->__toString());
                                $sep   = strpos($base, '?') !== false ? '&' : '?';
                                if ($cur > 1): ?>
                                    <a class="prev" href="<?= $base . $sep . 'page=' . ($cur - 1) ?>">Prev</a>
                                <?php endif; ?>
                                <div class="d-inline-block">
                                    <?php for ($i = 1; $i <= $total; $i++): ?>
                                        <?php if ($i == $cur): ?>
                                            <a class="active" href="#"><?= $i ?></a>
                                        <?php else: ?>
                                            <a href="<?= $base . $sep . 'page=' . $i ?>"><?= $i ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($cur < $total): ?>
                                    <a class="next" href="<?= $base . $sep . 'page=' . ($cur + 1) ?>">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty(trim((string) ($filters['q'] ?? '')))): ?>
                    <div id="companySearchResults" class="mt-4"></div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </section>
</div>

<script>
(function waitForjQuery() {
    if (typeof window.jQuery === 'undefined') {
        return window.setTimeout(waitForjQuery, 50);
    }
    var $ = window.jQuery;
    var searchUrl        = '<?= base_url('companies/search-jobs') ?>';
    var csrfName         = '<?= csrf_token() ?>';
    var csrfHash         = '<?= csrf_hash() ?>';
    var fallbackQuery    = <?= json_encode(trim((string) ($filters['q'] ?? ''))) ?>;
    var fallbackJobLimit = <?= json_encode((int) ($filters['limit'] ?? 10)) ?>;
    var useAiFallback    = <?= json_encode(!empty(trim((string) ($filters['q'] ?? ''))) && empty($companies)) ?>;

    $(function () {

        function escHtml(value) {
            return $('<div>').text(value || '').html();
        }

        // Update the job count and "See live jobs" link on the company card
        function updateCompanyCardLiveJobs(result) {
            if (!result || !result.saved_company_id || !result.count || result.count <= 0) return;
            var $card = $('[data-company-id="' + result.saved_company_id + '"]');
            if (!$card.length) return;
            $card.find('.company-job-count-number').text(result.count);
            $card.find('.company-job-count-label').text('live jobs');
            var companyName = $card.data('company-name') || '';
            $card.find('.company-directory-jobs-link')
                .attr('href', '<?= base_url("jobs?company=") ?>' + encodeURIComponent(companyName))
                .find('span.jobs-link-text').text('See ' + result.count + ' live jobs').end()
                .removeClass('d-none');
        }

        // Render the AI-fetched company card (for unregistered companies)
        function renderCompanyCard(info, savedCompanyId) {
            if (!info || !info.name) return '';
            var initial = (info.name.charAt(0) || 'C').toUpperCase();
            var companyName = info.name;
            var jobsUrl = '<?= base_url('jobs?company=') ?>' + encodeURIComponent(companyName);

            var html = '<article class="job-card company-directory-card mb-4" data-company-id="' + (savedCompanyId || 0) + '" data-company-name="' + escHtml(companyName) + '">';
            html += '<div class="job-card-icon company-directory-logo">';
            html += info.logo_url
                ? '<img src="' + escHtml(info.logo_url) + '" alt="' + escHtml(companyName) + '">'
                : '<span>' + escHtml(initial) + '</span>';
            html += '</div>';
            html += '<h3 class="job-card-title"><span>' + escHtml(companyName) + '</span></h3>';
            html += '<p class="job-card-company">' + escHtml(info.industry || 'Industry not specified') + '</p>';
            html += '<div class="job-card-meta company-directory-meta">';
            html += '<span><i class="fas fa-map-pin"></i> ' + escHtml(info.hq || info.location || 'HQ not specified') + '</span>';
            if (info.founded_year) {
                html += '<span><i class="fas fa-calendar mr-1"></i>Est. ' + escHtml(info.founded_year) + '</span>';
            }
            html += '</div>';
            if (info.short_description || info.description) {
                html += '<p class="small text-muted mt-2 mb-0">' + escHtml(info.short_description || info.description) + '</p>';
            }
            html += '<div class="job-card-tags company-directory-tags">';
            html += '<span class="badge badge-primary">' + escHtml(info.size || 'Size not specified') + '</span>';
            html += '</div>';
            html += '<div class="company-directory-actions">';
            // "See live jobs" — shown after job count is fetched
            html += '<a href="' + jobsUrl + '" class="company-directory-jobs-link">';
            html += '<i class="fas fa-briefcase mr-1"></i> <span class="jobs-link-text">See live jobs</span></a>';
            html += '</div>';
            html += '</article>';
            return html;
        }

        function fetchCompanyInfo(companyName) {
            if (!companyName) return;

            $('#companySearchResults').html(
                '<div class="searching-state mb-4">' +
                    '<div class="searching-state__icon">' +
                        '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
                    '</div>' +
                    '<div class="searching-state__text">' +
                        '<h5>AI searching for <strong>' + escHtml(companyName) + '</strong></h5>' +
                        '<p>Fetching company details, HQ, description, and social links from the official website.</p>' +
                    '</div>' +
                '</div>'
            );

            $.post(searchUrl, { company_name: companyName, info_only: 1, [csrfName]: csrfHash })
                .done(function (result) {
                    var savedId = result.saved_company_id || 0;
                    var html = renderCompanyCard(result.company_info || { name: companyName }, savedId);
                    $('#companySearchResults').html(html);
                    // Fetch job count in background to update the card's "See live jobs" button
                    fetchCompanyJobCount(companyName, fallbackJobLimit);
                })
                .fail(function (xhr) {
                    $('#companySearchResults').html(
                        '<div class="alert alert-danger">' +
                        (xhr.responseJSON && xhr.responseJSON.error ? escHtml(xhr.responseJSON.error) : 'Company details fetch failed. Please try again.') +
                        '</div>'
                    );
                });
        }

        // Only fetch job count to update the card — no job list rendered
        function fetchCompanyJobCount(companyName, limit) {
            if (!companyName) return;
            $.post(searchUrl, { company_name: companyName, limit: limit || fallbackJobLimit, [csrfName]: csrfHash })
                .done(function (result) {
                    updateCompanyCardLiveJobs(result);
                });
        }

        // Auto-trigger AI fetch when no local company matched
        if (useAiFallback) {
            fetchCompanyInfo(fallbackQuery);
        }

        // If local company found, fetch job count in background to update card
        if (fallbackQuery && !useAiFallback) {
            fetchCompanyJobCount(fallbackQuery, fallbackJobLimit);
        }

    });
})();
</script>

<?= view('Layouts/candidate_footer') ?>
    