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

<style>
/* ===========================================================
   HireMatrix visual system — light (default) + dark (body.dark)
   =========================================================== */
:root {
  --primary: #1FB7B5;
  --primary-dark: #0D8A90;

  --secondary: #53B86C;
  --secondary-dark: #3F9E58;

  --accent: #B5D84E;
  --accent-dark: #9DC23D;

  --background: #F8FCFB;
  --foreground: #16212B;

  --card: #FFFFFF;

  --muted: #EDF8F5;
  --muted-foreground: #64748B;

  --border: #D9ECE5;
  --text-light: #94A3B8;

  --gradient-primary: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
  --gradient-soft: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
}

/* Dark theme applied via a class on <body>, not <html> */
body.dark {
  --primary: #1FB7B5;
  --primary-dark: #0D8A90;

  --secondary: #53B86C;
  --secondary-dark: #3F9E58;

  --accent: #B5D84E;
  --accent-dark: #9DC23D;

  --background: #0E1619;
  --foreground: #F8FAFC;

  --card: #162327;

  --muted: #1B2A2F;
  --muted-foreground: #94A3B8;

  --border: #23343A;
  --text-light: #7A8B96;

  --gradient-primary: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
  --gradient-soft: linear-gradient(135deg, #162327 0%, #1B2A2F 100%);
}

* { box-sizing: border-box; }

/* Page background per spec: soft gradient in light mode, solid black in dark mode */
body {
  background: var(--gradient-soft);
}
body.dark {
  background: #000000 !important;
}

.recruiter-reports-wrap {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: var(--foreground);
  width: 100%;
}

/* recruiter_header.php targets `main > [class*="-jobboard"]` to strip
   max-width / padding from .container / .container-fluid and stretch
   content to fill .hm-main. The class below (recruiter-reports-jobboard)
   opts this page into that existing full-width mechanism — no need to
   fight the layout with viewport-width hacks. */
.recruiter-reports-wrap .container-fluid {
  width: 100%;
  max-width: 100%;
}

.page-heading { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
.page-sub { color: var(--muted-foreground); font-size: 14px; margin: 0 0 24px; }

/* ---------- Cards ---------- */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
}
.card-title { font-size: 16px; font-weight: 700; margin: 0 0 18px; }

/* ---------- Form controls ---------- */
.field-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; margin-bottom: 4px; }
.field { display: flex; flex-direction: column; gap: 6px; min-width: 160px; }
.field label { font-size: 12px; font-weight: 600; color: var(--muted-foreground); }
.field input[type="text"],
.field input[type="date"],
.field select {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 9px 12px;
  font-size: 13px;
  color: var(--foreground);
  min-width: 160px;
}
.field input:focus, .field select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(31,183,181,0.15);
}

/* Bootstrap form-check radios, tinted with brand color */
.form-check-input:checked {
  background-color: var(--primary);
  border-color: var(--primary);
}
.form-check-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(31,183,181,0.15);
}
.form-check-label {
  font-size: 13px;
  color: var(--foreground);
}

.divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

.btn {
  border: none; border-radius: 9px; padding: 10px 20px;
  font-size: 13px; font-weight: 600; cursor: pointer;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-primary { background: var(--gradient-primary); color: #fff; }
.btn-primary:hover { filter: brightness(1.05); }
.btn-outline { background: transparent; border: 1px solid var(--border); color: var(--foreground); }
.btn-outline:hover { background: var(--muted); }

.note { font-size: 12px; color: var(--text-light); margin: 8px 0 18px; }
</style>

<div class="recruiter-reports-wrap recruiter-reports-jobboard">
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
        <div style="margin-top:18px;">
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
          <div class="field" style="flex:1;">
            <label>Keyword</label>
            <input type="text" name="keyword" placeholder="Title, company or location" value="<?= esc($filters['keyword'] ?? '') ?>">
          </div>
        </div>

        <hr class="divider">

        <div style="text-align:right;">
          <button type="submit" name="generate" value="1" class="btn btn-primary">⬇ Download Excel</button>
          <a href="<?= site_url('recruiter/reports') ?>" class="btn btn-outline">Reset</a>
        </div>
      </div>
    </form>

  </div>
</div>

<?= view('Layouts/recruiter_footer') ?>