<?php
/*
Plugin Name: Interview Dashboard
Description: Manage interview questions with add, edit, delete features
Version: 1.0
Author: Kruti
*/
add_shortcode('interview_dashboard', 'render_interview_dashboard');

function render_interview_dashboard() {
    global $wpdb;

    $table = 'ai_interview_questions';

    // ✅ EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {

        $wpdb->update(
            $table,
            [
                'question' => sanitize_text_field($_POST['question']),
                'skill' => sanitize_text_field($_POST['skill']),
                'round' => sanitize_text_field($_POST['round']),
                'difficulty' => sanitize_text_field($_POST['difficulty']),
                'option_a' =>  ($_POST['a']),
                'option_b' =>  ($_POST['b']),
                'option_c' =>  ($_POST['c']),
                'option_d' =>  ($_POST['d']),
                'correct_answer' => sanitize_text_field($_POST['answer']),
            ],
            ['id' => intval($_POST['edit_id'])]
        );

        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // ✅ DELETE
    if (isset($_GET['delete_id'])) {
        $wpdb->delete($table, ['id' => intval($_GET['delete_id'])]);
        wp_redirect(remove_query_arg('delete_id'));
        exit;
    }

    // Filters
    $round = $_GET['round'] ?? '';
    $difficulty = $_GET['difficulty'] ?? '';
    $created_at = $_GET['created_at'] ?? '';

    $query = "SELECT * FROM $table WHERE 1=1";

    if ($round) {
        $query .= $wpdb->prepare(" AND round = %s", $round);
    }

    if ($difficulty) {
        $query .= $wpdb->prepare(" AND difficulty = %s", $difficulty);
    }

    if ($created_at) {
        $query .= $wpdb->prepare(" AND DATE(created_at) = %s", $created_at);
    }

    $query .= " ORDER BY created_at DESC";

    $results = $wpdb->get_results($query);

    ob_start();
    ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="<?php echo plugin_dir_url(__FILE__) . 'dashboard.css'; ?>" rel="stylesheet"> 
<div class="container py-5 dashboard-wrapper">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
       
            <h2 class="fw-bold">📋 Interview Questions</h2>
            <p class="text-muted">Manage questions, difficulty, rounds and filtering.</p>
        </div>

        <a href="/ai_interview_questions/add-question" class="btn btn-orange">
            + Add Question
        </a>
    </div>

    <!-- Filters -->
<div class="card p-3 mb-4 filter-card">
    <form method="GET" class="row g-3 align-items-end">

        <div class="col-md-3">
            <label class="form-label">Round</label>
            <select name="round" class="form-select">
                <option value="">All</option>
                <option value="aptitude">Aptitude</option>
                <option value="technical">Technical</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Difficulty</label>
            <select name="difficulty" class="form-select">
                <option value="">All</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Created Date</label>
            <input type="date" name="created_at" class="form-control">
        </div>

        <div class="col-md-3">
            <button class="btn btn-orange w-100">Filter</button>
        </div>

    </form>
</div>

    <!-- Table -->
    <div class="card table-card p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>QUESTION</th>
                    <th>ROUND</th>
                    <th>SKILL</th>
                    <th>DIFFICULTY</th>
                    <th>DATE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td class="fw-semibold"><?php echo $row->question; ?></td>

                    <td>
                        <span class="badge badge-round">
                            <?php echo ucfirst($row->round); ?>
                        </span>
                    </td>

                    <td><?php echo $row->skill; ?></td>

                    <td>
                        <span class="badge badge-difficulty <?php echo $row->difficulty; ?>">
                            <?php echo ucfirst($row->difficulty); ?>
                        </span>
                    </td>

                    <td><?php echo date('M d, Y', strtotime($row->created_at)); ?></td>

                    <td>
                         <button class="btn btn-sm btn-view"
    data-id="<?php echo esc_attr($row->id); ?>"
    data-question="<?php echo esc_attr($row->question); ?>"
    data-skill="<?php echo esc_attr($row->skill); ?>"
    data-round="<?php echo esc_attr($row->round); ?>"
    data-difficulty="<?php echo esc_attr($row->difficulty); ?>"
    data-a="<?php echo esc_attr($row->option_a); ?>"
    data-b="<?php echo esc_attr($row->option_b); ?>"
    data-c="<?php echo esc_attr($row->option_c); ?>"
    data-d="<?php echo esc_attr($row->option_d); ?>"
    data-answer="<?php echo esc_attr($row->correct_answer); ?>"
    data-date="<?php echo esc_attr($row->created_at); ?>"
>View</button>
                        <button class="btn btn-sm btn-edit" data-id="<?php echo esc_attr($row->id); ?>"
                         data-question="<?php echo esc_attr($row->question); ?>"
    data-skill="<?php echo esc_attr($row->skill); ?>"
    data-round="<?php echo esc_attr($row->round); ?>"
    data-difficulty="<?php echo esc_attr($row->difficulty); ?>"
    data-a="<?php echo esc_attr($row->option_a); ?>"
    data-b="<?php echo esc_attr($row->option_b); ?>"
    data-c="<?php echo esc_attr($row->option_c); ?>"
    data-d="<?php echo esc_attr($row->option_d); ?>"
    data-answer="<?php echo esc_attr($row->correct_answer); ?>">Edit</button>
                       <button class="btn btn-sm btn-delete" data-id="<?php echo $row->id; ?>">Delete</button> </td> 
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
                <!--model-->
        <div class="modal fade custom-modal" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content custom-modal-content">

      <!-- Header -->
      <div class="modal-header custom-header">
        <div>
          <h5 class="modal-title"> Question Details</h5>
          <p class="modal-subtitle">Detailed question information</p>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- Top Card -->
      <div class="profile-card mb-4">
  <h4 id="m_question_title"></h4>
  <p id="m_skill" class="text-muted mb-2"></p>

  <div class="answer-pill">
    ✅ Correct Answer: <span id="m_answer_top"></span>
  </div>
</div>

         <div class="row g-4">

  <!-- LEFT: Question Info -->
  <div class="col-md-6">
    <div class="info-card h-100">
      <h6 class="card-title">Question Info</h6>

      <p><strong>Round:</strong> <span id="m_round"></span></p>
      <p><strong>Difficulty:</strong> <span id="m_difficulty"></span></p>
      <p><strong>Date:</strong> <span id="m_date"></span></p>
    </div>
  </div>

  <!-- RIGHT: Options -->
  <div class="col-md-6">
    <div class="info-card h-100">
      <h6 class="card-title">Options</h6>

      <ul class="option-list">
        <li id="m_a"></li>
        <li id="m_b"></li>
        <li id="m_c"></li>
        <li id="m_d"></li>
      </ul>
    </div>
  </div>

</div>

        </div>

      </div>

    </div>
  </div>
</div>
<!--model-->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered"> <!-- smaller -->
    <div class="modal-content custom-modal-content">

      <form method="POST">
        <input type="hidden" name="edit_id" id="edit_id">

        <!-- Header -->
        <div class="modal-header custom-header">
          <h5 class="modal-title">✏️ Edit Question</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">

          <!-- Question -->
          <div class="mb-3">
            <label class="form-label">Question</label>
            <textarea name="question" id="edit_question" class="form-control" rows="2"></textarea>
          </div>

          <!-- Row 1 -->
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Skill</label>
              <input type="text" name="skill" id="edit_skill" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Round</label>
              <select name="round" id="edit_round" class="form-select">
                <option value="aptitude">Aptitude</option>
                <option value="technical">Technical</option>
              </select>
            </div>
          </div>

          <!-- Row 2 -->
          <div class="row g-2 mt-2">
            <div class="col-md-6">
              <label class="form-label">Difficulty</label>
              <select name="difficulty" id="edit_difficulty" class="form-select">
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Answer</label>
              <select name="answer" id="edit_answer" class="form-select">
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
              </select>
            </div>
          </div>

          <!-- Options -->
          <div class="mt-3">
            <label class="form-label">Options</label>

            <div class="row g-2">
              <div class="col-md-6">
                <input type="text" name="a" id="edit_a" class="form-control" placeholder="A">
              </div>
              <div class="col-md-6">
                <input type="text" name="b" id="edit_b" class="form-control" placeholder="B">
              </div>
              <div class="col-md-6">
                <input type="text" name="c" id="edit_c" class="form-control" placeholder="C">
              </div>
              <div class="col-md-6">
                <input type="text" name="d" id="edit_d" class="form-control" placeholder="D">
              </div>
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-orange">Update</button>
        </div>

      </form>

    </div>
  </div>
</div>
<!--edit-->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content p-3 text-center">

      <h5 class="mb-3">⚠️ Delete Question?</h5>
      <p class="text-muted small">This action cannot be undone.</p>

      <div class="d-flex justify-content-center gap-2 mt-3">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete</a>
      </div>

    </div>
  </div>
</div>
<!--delete-->
    </div>

</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // VIEW
  document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', function () {

        document.getElementById('m_question_title').innerText = this.dataset.question || '';
        document.getElementById('m_skill').innerText = this.dataset.skill || '';
        document.getElementById('m_round').innerText = this.dataset.round || '';
        document.getElementById('m_difficulty').innerText = this.dataset.difficulty || '';
        document.getElementById('m_date').innerText = this.dataset.date || '';

        document.getElementById('m_a').innerText = "A: " + (this.dataset.a || '');
        document.getElementById('m_b').innerText = "B: " + (this.dataset.b || '');
        document.getElementById('m_c').innerText = "C: " + (this.dataset.c || '');
        document.getElementById('m_d').innerText = "D: " + (this.dataset.d || '');

        document.getElementById('m_answer_top').innerText = this.dataset.answer || '';

        new bootstrap.Modal(document.getElementById('viewModal')).show();
    });
});
    // EDIT
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {

            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_question').value = this.dataset.question;
            document.getElementById('edit_skill').value = this.dataset.skill;
            document.getElementById('edit_round').value = this.dataset.round;
            document.getElementById('edit_difficulty').value = this.dataset.difficulty;

            document.getElementById('edit_a').value = this.dataset.a;
            document.getElementById('edit_b').value = this.dataset.b;
            document.getElementById('edit_c').value = this.dataset.c;
            document.getElementById('edit_d').value = this.dataset.d;

            document.getElementById('edit_answer').value = this.dataset.answer;

            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
    });

    // DELETE
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function () {

            let id = this.dataset.id;
            document.getElementById('confirmDeleteBtn').href = "?delete_id=" + id;

            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

});
            </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
    return ob_get_clean();
}