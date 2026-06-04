<?php 
/*
Plugin Name: Add Question
Description: Manage interview questions with add multiple feature
Version: 1.0
Author: Kruti
*/

add_shortcode('add_interview_question', 'render_add_question_page');

function render_add_question_page() {
    global $wpdb;
    $table = 'ai_interview_questions';

      // =========================
    // ✅ INSERT MULTIPLE QUESTIONS
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {

        $questions = $_POST['question'];

        foreach ($questions as $i => $q) {

            if (empty($q)) continue;

            // ✅ CHECK DUPLICATE
            $exists = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE question = %s", $q)
            );

            if ($exists) {
                 echo '<div class="alert alert-warning">⚠️ Question Already Available</div>'; 
            }
else{

            $wpdb->insert($table, [
                'question' => wp_kses_post($q),
                'skill' => sanitize_text_field($_POST['skill'][$i]),
                'round' => sanitize_text_field($_POST['round'][$i]),
                'difficulty' => sanitize_text_field($_POST['difficulty'][$i]),
                'option_a' => wp_kses_post($_POST['a'][$i]),
                'option_b' => wp_kses_post($_POST['b'][$i]),
                'option_c' => wp_kses_post($_POST['c'][$i]),
                'option_d' => wp_kses_post($_POST['d'][$i]),
                'correct_answer' => sanitize_text_field($_POST['answer'][$i]),
                'created_at' => current_time('mysql')
            ]);
              echo '<div class="alert alert-success">✅ Questions Added Successfully!</div>';
}
        }

      
    }

    // =========================
    // ✅ CSV IMPORT
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {

        if (!empty($_FILES['csv_file']['tmp_name'])) {

            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            fgetcsv($file); // skip header

            while (($row = fgetcsv($file)) !== FALSE) {

                $question = $row[0];

                if (empty($question)) continue;

                // ✅ DUPLICATE CHECK
                $exists = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE question = %s", $question)
                );

                 if ($exists) {
                echo '<div class="alert alert-warning">⚠️ Question Already Available</div>'; 
            }
else{
 $wpdb->insert($table, [
                    'question' => $question,
                    'skill' => $row[1],
                    'round' => $row[2],
                    'difficulty' => $row[3],
                    'option_a' => $row[4],
                    'option_b' => $row[5],
                    'option_c' => $row[6],
                    'option_d' => $row[7],
                    'correct_answer' => $row[8],
                    'created_at' => current_time('mysql')
                ]);
                echo '<div class="alert alert-success">✅ Questions Added Successfully!</div>';
}
               
            }

            fclose($file);
        }
    }


    // ✅ SAMPLE CSV DOWNLOAD
    if (isset($_GET['download_sample'])) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sample_questions.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['question','skill','round','difficulty','option_a','option_b','option_c','option_d','correct_answer']);

        fputcsv($output, [
            'What is PHP?',
            'Backend',
            'technical',
            'easy',
            'Language',
            'Database',
            'Server',
            'OS',
            'A'
        ]);

        fclose($output);
        exit;
    }
    ob_start();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo plugin_dir_url(__FILE__) . 'dashboard.css'; ?>" rel="stylesheet">

<div class="container py-5 dashboard-wrapper">

    <div class="card p-4 filter-card">
        <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>➕ Add Interview Questions</h3>

        <div class="d-flex gap-2">

            <!-- DOWNLOAD SAMPLE -->
            <a href="?download_sample=1" class="btn btn-orange btn-sm">
                ⬇ Sample File
            </a>

            <!-- IMPORT BUTTON -->
            <button class="btn btn-edit btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                📂 Import Excel
            </button>

        </div>
    </div>

        <form method="POST" id="questionForm">

            <input type="hidden" name="submit_question" value="1">

            <!-- DEFAULT QUESTION -->
            <div class="question-block card p-3 border mb-3">
                <h5>📝 Question 1</h5>

                <div class="mb-3">
                    <label>Question</label>
                    <textarea name="question[]" class="form-control" required></textarea>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label>Skill</label>
                        <input type="text" name="skill[]" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label>Round</label>
                        <select name="round[]" class="form-select">
                            <option value="aptitude">Aptitude</option>
                            <option value="technical">Technical</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <label>Difficulty</label>
                        <select name="difficulty[]" class="form-select">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Correct Answer</label>
                        <select name="answer[]" class="form-select">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-md-6"><input name="a[]" class="form-control" placeholder="Option A"></div>
                    <div class="col-md-6"><input name="b[]" class="form-control" placeholder="Option B"></div>
                    <div class="col-md-6"><input name="c[]" class="form-control" placeholder="Option C"></div>
                    <div class="col-md-6"><input name="d[]" class="form-control" placeholder="Option D"></div>
                </div>
            </div>

            <!-- NEW QUESTIONS APPEND HERE -->
            <div id="extra-questions"></div>

            <!-- ADD BUTTON -->
            <button type="button" id="addQuestion" class="btn btn-orange mb-3">
                + Add Another Question
            </button>

            <!-- SUBMIT -->
            <div>
                <button class="btn btn-orange">Save All Questions</button>
                <a href="/ai_interview_questions" class="btn btn-light">Back</a>
            </div>

        </form>

       
    </div>
 <!-- IMPORT MODAL -->
<div class="modal fade custom-modal" id="importModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header custom-header">

                <div>

                    <h5 class="modal-title">
                        📂 Import Questions CSV
                    </h5>

                    <p class="modal-subtitle">
                        Upload CSV file to bulk import interview questions
                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body">

                <div class="profile-card">

                    <div class="csv-info-box">

                        <h6>CSV Format Required</h6>

                        <p>
                            question, skill, round, difficulty,
                            option_a, option_b, option_c,
                            option_d, correct_answer
                        </p>

                    </div>

                    <form method="POST" enctype="multipart/form-data">

                        <input type="hidden" name="import_csv" value="1">

                        <div class="mb-3">

                            <input
                                type="file"
                                name="csv_file"
                                accept=".csv"
                                class="form-control"
                                required>

                        </div>

                        <button class="btn btn-primary w-100">
                            Upload CSV
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</div>

<script>
let count = 1;

document.getElementById('addQuestion').onclick = function () {

    count++;

    let container = document.getElementById('extra-questions');

    let div = document.createElement('div');
    div.className = 'card p-3 mb-3';

    div.innerHTML = `
        <h5>Question ${count}</h5>
        <textarea name="question[]" class="form-control mb-2"></textarea>

        <div class="row g-2">
            <div class="col"><input name="skill[]" class="form-control"></div>
            <div class="col">
                <select name="round[]" class="form-select">
                    <option value="aptitude">Aptitude</option>
                    <option value="technical">Technical</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col">
                <select name="difficulty[]" class="form-select">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div class="col">
                <select name="answer[]" class="form-select">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col"><input name="a[]" class="form-control" placeholder="A"></div>
            <div class="col"><input name="b[]" class="form-control" placeholder="B"></div>
            <div class="col"><input name="c[]" class="form-control" placeholder="C"></div>
            <div class="col"><input name="d[]" class="form-control" placeholder="D"></div>
        </div>
    `;

    container.appendChild(div);
};
</script>


<script>
let count = 1;

document.getElementById('addQuestion').onclick = function () {

    count++;

    let container = document.getElementById('extra-questions');

    let div = document.createElement('div');
    div.className = 'question-block card p-3 border mb-3';

    div.innerHTML = `
        <h5>📝 Question ${count}</h5>

        <div class="mb-3">
            <label>Question</label>
            <textarea name="question[]" class="form-control" required></textarea>
        </div>

        <div class="row g-2">
            <div class="col-md-6">
                <label>Skill</label>
                <input type="text" name="skill[]" class="form-control">
            </div>

            <div class="col-md-6">
                <label>Round</label>
                <select name="round[]" class="form-select">
                    <option value="aptitude">Aptitude</option>
                    <option value="technical">Technical</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-md-6">
                <label>Difficulty</label>
                <select name="difficulty[]" class="form-select">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div class="col-md-6">
                <label>Correct Answer</label>
                <select name="answer[]" class="form-select">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-3">
            <div class="col-md-6"><input name="a[]" class="form-control" placeholder="Option A"></div>
            <div class="col-md-6"><input name="b[]" class="form-control" placeholder="Option B"></div>
            <div class="col-md-6"><input name="c[]" class="form-control" placeholder="Option C"></div>
            <div class="col-md-6"><input name="d[]" class="form-control" placeholder="Option D"></div>
        </div>
    `;

    container.appendChild(div);
};
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
    return ob_get_clean();
}