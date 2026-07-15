<?= view('Layouts/recruiter_header', ['title' => 'Reports']) ?>

<?php
$jobCategoryOptions = [
    'Software Development',
    'Data Science',
    'DevOps',
    'Quality Assurance',
    'UI/UX Design',
    'Product Management',
    'Project Management',
    'Marketing',
    'Sales',
    'Human Resources',
    'Finance',
    'Operations',
    'Customer Support',
    'Business Analysis',
    'Cybersecurity',
];

$employmentTypeOptions = ['Full-time', 'Part-time', 'Contract', 'Internship'];
?>

<div class="recruiter-reports-wrap">
  <div class="container-fluid py-5">

    <h1 class="page-heading">Job Posting Report</h1>
    <p class="page-sub">Generate and download a report of your posted jobs as an Excel file.</p>

    <form method="get" action="<?= site_url('recruiter/reports') ?>">

      <!-- One Click Report -->
      <div class="card">
        <h2 class="card-title">One Click Report</h2>
        <div class="field-row">
          <div class="d-flex flex-wrap gap-3">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="period" id="period_yesterday"
                     value="yesterday" <?= ($period ?? '') === 'yesterday' ? 'checked' : '' ?>>
              <label class="form-check-label" for="period_yesterday">Yesterday</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="period" id="period_week"
                     value="week" <?= ($period ?? '') === 'week' ? 'checked' : '' ?>>
              <label class="form-check-label" for="period_week">This Week</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="period" id="period_month"
                     value="month" <?= ($period ?? '') === 'month' ? 'checked' : '' ?>>
              <label class="form-check-label" for="period_month">This Month</label>
            </div>
          </div>
        </div>
        <div class="recruiter-mt-18">
          <button type="submit" name="generate" value="1" class="btn btn-primary">
            ⬇ Download Excel
          </button>
        </div>
      </div>

      <!-- Customised Report -->
      <div class="card">
        <h2 class="card-title">Customized Report</h2>

        <div class="field-row">
          <div class="field">
            <label>From</label>
            <input type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>">
          </div>
          <div class="field">
            <label>To</label>
            <input type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Status</label>
            <select name="status">
              <option value="">All</option>
              <option value="open" <?= ($filters['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
              <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
          </div>
          <div class="field">
            <label>Category</label>
            <select name="category">
              <option value="">All</option>
              <?php foreach ($jobCategoryOptions as $categoryOption): ?>
                <option value="<?= esc($categoryOption) ?>" <?= ($filters['category'] ?? '') === $categoryOption ? 'selected' : '' ?>>
                  <?= esc($categoryOption) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Employment Type</label>
            <select name="employment_type">
              <option value="">All</option>
              <?php foreach ($employmentTypeOptions as $typeOption): ?>
                <option value="<?= esc($typeOption) ?>" <?= ($filters['employment_type'] ?? '') === $typeOption ? 'selected' : '' ?>>
                  <?= esc($typeOption) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field recruiter-flex-1">
            <label>Keyword</label>
            <input type="text" name="keyword" placeholder="Title, company or location" value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <hr class="divider">

        <div class="recruiter-text-right">
          <button type="submit" name="generate" value="1" class="btn btn-primary">⬇ Download Excel</button>
          <a href="<?= site_url('recruiter/reports') ?>" class="btn btn-outline">Reset</a>
        </div>
      </div>
    </form>

  </div>
</div>

<?= view('Layouts/recruiter_footer') ?>