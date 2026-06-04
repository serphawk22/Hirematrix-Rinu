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

    $inserted = 0;
    $duplicates = 0;

    foreach ($questions as $i => $q) {

        if (empty($q)) continue;

        // CHECK DUPLICATE
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE question = %s",
                $q
            )
        );

        if ($exists) {

            $duplicates++;

        } else {

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

            $inserted++;
        }
    }

    // SINGLE SUCCESS MESSAGE
    if ($inserted > 0) {

        echo '<div class="alert alert-success">
        ✅ ' . $inserted . ' Questions Added Successfully!
        </div>';
    }

    // SINGLE DUPLICATE MESSAGE
    if ($duplicates > 0) {

        echo '<div class="alert alert-warning">
        ⚠️ ' . $duplicates . ' Duplicate Questions Skipped
        </div>';
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
    // =========================
// ✅ DOCX / TXT / PDF IMPORT
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_document'])) {

    if (!empty($_FILES['document_file']['tmp_name'])) {

        $file_tmp  = $_FILES['document_file']['tmp_name'];
        $file_name = $_FILES['document_file']['name'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $content = '';

        // =========================
        // TXT IMPORT
        // =========================
        if ($ext == 'txt') {

            $content = file_get_contents($file_tmp);
        }

        // =========================
        // DOCX IMPORT
        // =========================
        elseif ($ext == 'docx') {

            if (!class_exists('ZipArchive')) {

                echo '<div class="alert alert-danger">
                ZIP extension not enabled in PHP.
                </div>';

            } else {

                $zip = new ZipArchive;

                if ($zip->open($file_tmp) === TRUE) {

                    $data = $zip->getFromName('word/document.xml');

                    $zip->close();

                    // CLEAN XML
                    $data = str_replace('</w:p>', "\n", $data);
                    $data = str_replace('</w:tr>', "\n", $data);

                    $content = strip_tags($data);

                } else {

                    echo '<div class="alert alert-danger">
                    Unable to open DOCX file.
                    </div>';
                }
            }
        }

        // =========================
        // PDF IMPORT
        // =========================
        // =========================
// PDF IMPORT
// =========================
elseif ($ext == 'pdf') {

    $pdftotext = 'C:\\poppler\\Library\\bin\\pdftotext.exe';

    if (file_exists($pdftotext)) {

        $content = shell_exec(
            '"' . $pdftotext . '" "' . $file_tmp . '" -'
        );

    } else {

        echo '<div class="alert alert-danger">
        pdftotext.exe not found.
        Install Poppler properly.
        </div>';
    }

    if (empty($content)) {

        echo '<div class="alert alert-danger">
        PDF text extraction failed.
        </div>';
    }
}

        // =========================
        // PARSE QUESTIONS
        // =========================
        if (!empty($content)) {

            $content = preg_replace('/\r\n|\r/', "\n", $content);

            $lines = preg_split('/\n+/', $content);

            $question = '';
            $options = [];
            $answer = '';

            foreach ($lines as $line) {

                $line = trim($line);

                if (empty($line)) continue;

                // =========================
                // QUESTION
                // =========================
                if (preg_match('/^[0-9]+\./', $line)) {

                    // SAVE OLD QUESTION
                    if (!empty($question)) {

                        $exists = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM $table WHERE question = %s",
                                $question
                            )
                        );

                        if (!$exists) {

                            $wpdb->insert($table, [

                                'question' => $question,
                                'skill' => sanitize_text_field($_POST['document_skill']),
'round' => sanitize_text_field($_POST['document_round']),
'difficulty' => sanitize_text_field($_POST['document_difficulty']),

                                'option_a' => $options['A'] ?? '',
                                'option_b' => $options['B'] ?? '',
                                'option_c' => $options['C'] ?? '',
                                'option_d' => $options['D'] ?? '',

                                'correct_answer' => $answer ?: 'A',

                                'created_at' => current_time('mysql')

                            ]);
                        }
                    }

                    // RESET
                    $question = preg_replace('/^[0-9]+\.\s*/', '', $line);

                    $options = [];

                    $answer = '';
                }

                // =========================
                // OPTIONS
                // =========================
                elseif (preg_match('/^A[\.\)\:]/i', $line)) {

                    $options['A'] = trim(
                        preg_replace('/^A[\.\)\:]/i', '', $line)
                    );
                }

                elseif (preg_match('/^B[\.\)\:]/i', $line)) {

                    $options['B'] = trim(
                        preg_replace('/^B[\.\)\:]/i', '', $line)
                    );
                }

                elseif (preg_match('/^C[\.\)\:]/i', $line)) {

                    $options['C'] = trim(
                        preg_replace('/^C[\.\)\:]/i', '', $line)
                    );
                }

                elseif (preg_match('/^D[\.\)\:]/i', $line)) {

                    $options['D'] = trim(
                        preg_replace('/^D[\.\)\:]/i', '', $line)
                    );
                }

                // =========================
                // ANSWER
                // =========================
                elseif (stripos($line, 'Answer:') !== false) {

                    preg_match('/Answer:\s*([A-D])/i', $line, $matches);

                    if (!empty($matches[1])) {

                        $answer = strtoupper($matches[1]);
                    }
                }
            }

            // =========================
            // INSERT LAST QUESTION
            // =========================
            if (!empty($question)) {

                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE question = %s",
                        $question
                    )
                );

                if (!$exists) {

                    $wpdb->insert($table, [

                        'question' => $question,
                        'skill' => sanitize_text_field($_POST['document_skill']),
'round' => sanitize_text_field($_POST['document_round']),
'difficulty' => sanitize_text_field($_POST['document_difficulty']),

                        'option_a' => $options['A'] ?? '',
                        'option_b' => $options['B'] ?? '',
                        'option_c' => $options['C'] ?? '',
                        'option_d' => $options['D'] ?? '',

                        'correct_answer' => $answer ?: 'A',

                        'created_at' => current_time('mysql')

                    ]);
                }
            }

            echo '<div class="alert alert-success">
            ✅ Questions Imported Successfully!
            </div>';
        }
    }
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

            <!-- IMPORT DOC/PDF/TXT -->
            <button class="btn btn-view btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#documentImportModal">
                📄 Import Document
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
<!--doc--><!-- DOCUMENT IMPORT MODAL -->
<div class="modal fade custom-modal"
     id="documentImportModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header custom-header">

                <div>

                    <h5 class="modal-title">
                        📄 Import Questions
                    </h5>

                    <p class="modal-subtitle">
                        Upload DOCX, PDF or TXT file
                    </p>

                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="csv-info-box mb-3">

<pre class="mb-0">
1. What is PHP?
A. Language
B. Database
C. OS
D. Browser
Answer: A
</pre>

                </div>

                <form method="POST"
                      enctype="multipart/form-data">

                    <input type="hidden"
                           name="import_document"
                           value="1">

                    <!-- FILE -->
                    <div class="mb-3">

                        <label class="form-label">
                            Select File
                        </label>

                        <input type="file"
                               name="document_file"
                               accept=".docx,.txt,.pdf"
                               class="form-control"
                               required>

                    </div>

                    <!-- ROUND -->
                    <div class="mb-3">

                        <label class="form-label">
                            Select Round
                        </label>

                        <select name="document_round"
                                class="form-select"
                                required>

                            <option value="aptitude">
                                Aptitude
                            </option>

                            <option value="technical">
                                Technical
                            </option>

                        </select>

                    </div>

                    <!-- SKILL -->
                    <div class="mb-3">

                        <label class="form-label">
                            Skill
                        </label>

                        <input type="text"
                               name="document_skill"
                               class="form-control"
                               placeholder="Example: PHP, Flutter, React">

                    </div>

                    <!-- DIFFICULTY -->
                    <div class="mb-3">

                        <label class="form-label">
                            Difficulty
                        </label>

                        <select name="document_difficulty"
                                class="form-select">

                            <option value="easy">
                                Easy
                            </option>

                            <option value="medium" selected>
                                Medium
                            </option>

                            <option value="hard">
                                Hard
                            </option>

                        </select>

                    </div>

                    <button class="btn btn-view w-100">

                        Import Questions

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
<!--doc-->

</div>
 

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