<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

// Create table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS sus_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    q1 INT NOT NULL,
    q2 INT NOT NULL,
    q3 INT NOT NULL,
    q4 INT NOT NULL,
    q5 INT NOT NULL,
    q6 INT NOT NULL,
    q7 INT NOT NULL,
    q8 INT NOT NULL,
    q9 INT NOT NULL,
    q10 INT NOT NULL,
    total_score DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

$success = false;
$total_score = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q1 = intval($_POST['q1']);
    $q2 = intval($_POST['q2']);
    $q3 = intval($_POST['q3']);
    $q4 = intval($_POST['q4']);
    $q5 = intval($_POST['q5']);
    $q6 = intval($_POST['q6']);
    $q7 = intval($_POST['q7']);
    $q8 = intval($_POST['q8']);
    $q9 = intval($_POST['q9']);
    $q10 = intval($_POST['q10']);
    
    // SIMPLIFIED CALCULATION - All positive questions
    // Simply average all responses and convert to 0-100 scale
    $average = ($q1 + $q2 + $q3 + $q4 + $q5 + $q6 + $q7 + $q8 + $q9 + $q10) / 10;
    $total_score = (($average - 1) / 4) * 100; // Convert 1-5 scale to 0-100
    
    // Save to database
    $sql = "INSERT INTO sus_responses (q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total_score) 
            VALUES ($q1, $q2, $q3, $q4, $q5, $q6, $q7, $q8, $q9, $q10, $total_score)";
    
    if (mysqli_query($conn, $sql)) {
        $success = true;
    } else {
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUS Survey - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .survey-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .survey-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .survey-header h2 {
            color: #667eea;
            font-weight: bold;
        }
        .question-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .question-number {
            color: #667eea;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .question-text {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .likert-scale {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .likert-option {
            text-align: center;
            flex: 1;
        }
        .likert-option input[type="radio"] {
            width: 25px;
            height: 25px;
            cursor: pointer;
            margin-bottom: 8px;
        }
        .likert-option label {
            display: block;
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        .score-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
        }
        .score-display h1 {
            font-size: 5rem;
            font-weight: bold;
            margin: 20px 0;
        }
        .interpretation {
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 18px;
        }
        .excellent { background: #d4edda; color: #155724; }
        .good { background: #d1ecf1; color: #0c5460; }
        .ok { background: #fff3cd; color: #856404; }
        .poor { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="survey-container">
    <?php if ($success): ?>
        <!-- RESULT PAGE -->
        <div class="survey-header">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h2>Thank You for Your Feedback!</h2>
            <p class="text-muted">Your response has been recorded successfully.</p>
        </div>

        <div class="score-display">
            <p class="mb-0" style="font-size: 1.3rem;">Your SUS Score is:</p>
            <h1><?php echo number_format($total_score, 1); ?></h1>
            <p class="mb-0" style="font-size: 1.2rem;">out of 100</p>
        </div>

        <div class="interpretation <?php 
            if ($total_score >= 80) echo 'excellent';
            elseif ($total_score >= 68) echo 'good';
            elseif ($total_score >= 50) echo 'ok';
            else echo 'poor';
        ?>">
            <i class="fas fa-info-circle"></i>
            <?php
            if ($total_score >= 80) {
                echo "Excellent usability! The system performs exceptionally well.";
            } elseif ($total_score >= 68) {
                echo "Above average usability. The system is user-friendly.";
            } elseif ($total_score >= 50) {
                echo "Below average usability. There is room for improvement.";
            } else {
                echo "Poor usability. Significant improvements are needed.";
            }
            ?>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-chart-line"></i> <strong>Industry Standard:</strong> The average SUS score is 68 points. Scores above 80 are considered excellent.
        </div>

        <div class="text-center mt-4">
            <a href="sus_results.php" class="btn btn-primary btn-lg px-4 me-2">
                <i class="fas fa-chart-bar"></i> View All Results
            </a>
            <a href="dashboard.php" class="btn btn-secondary btn-lg px-4">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>

    <?php else: ?>
        <!-- SURVEY FORM -->
        <div class="survey-header">
            <i class="fas fa-clipboard-list fa-4x text-primary mb-3"></i>
            <h2>System Usability Scale (SUS)</h2>
            <p class="text-muted">Please rate your experience with the EHR Information System</p>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Instructions:</strong> Please respond to each statement on a scale from 1 (Strongly Disagree) to 5 (Strongly Agree).
        </div>

        <form method="POST" action="">
            <!-- Question 1 -->
            <div class="question-card">
                <div class="question-number">QUESTION 1</div>
                <div class="question-text">I think that I would like to use this system frequently.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q1" value="1" id="q1_1" required>
                        <label for="q1_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q1" value="2" id="q1_2">
                        <label for="q1_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q1" value="3" id="q1_3">
                        <label for="q1_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q1" value="4" id="q1_4">
                        <label for="q1_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q1" value="5" id="q1_5">
                        <label for="q1_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="question-card">
                <div class="question-number">QUESTION 2</div>
                <div class="question-text">I found the system easy to navigate and use.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q2" value="1" id="q2_1" required>
                        <label for="q2_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q2" value="2" id="q2_2">
                        <label for="q2_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q2" value="3" id="q2_3">
                        <label for="q2_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q2" value="4" id="q2_4">
                        <label for="q2_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q2" value="5" id="q2_5">
                        <label for="q2_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="question-card">
                <div class="question-number">QUESTION 3</div>
                <div class="question-text">The system interface is intuitive and user-friendly.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q3" value="1" id="q3_1" required>
                        <label for="q3_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q3" value="2" id="q3_2">
                        <label for="q3_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q3" value="3" id="q3_3">
                        <label for="q3_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q3" value="4" id="q3_4">
                        <label for="q3_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q3" value="5" id="q3_5">
                        <label for="q3_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 4 -->
            <div class="question-card">
                <div class="question-number">QUESTION 4</div>
                <div class="question-text">I can easily find the features I need in the system.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q4" value="1" id="q4_1" required>
                        <label for="q4_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q4" value="2" id="q4_2">
                        <label for="q4_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q4" value="3" id="q4_3">
                        <label for="q4_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q4" value="4" id="q4_4">
                        <label for="q4_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q4" value="5" id="q4_5">
                        <label for="q4_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 5 -->
            <div class="question-card">
                <div class="question-number">QUESTION 5</div>
                <div class="question-text">The various functions in this system work well together.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q5" value="1" id="q5_1" required>
                        <label for="q5_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q5" value="2" id="q5_2">
                        <label for="q5_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q5" value="3" id="q5_3">
                        <label for="q5_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q5" value="4" id="q5_4">
                        <label for="q5_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q5" value="5" id="q5_5">
                        <label for="q5_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 6 -->
            <div class="question-card">
                <div class="question-number">QUESTION 6</div>
                <div class="question-text">The system responds quickly to my actions.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q6" value="1" id="q6_1" required>
                        <label for="q6_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q6" value="2" id="q6_2">
                        <label for="q6_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q6" value="3" id="q6_3">
                        <label for="q6_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q6" value="4" id="q6_4">
                        <label for="q6_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q6" value="5" id="q6_5">
                        <label for="q6_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 7 -->
            <div class="question-card">
                <div class="question-number">QUESTION 7</div>
                <div class="question-text">I would recommend this system to other healthcare professionals.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q7" value="1" id="q7_1" required>
                        <label for="q7_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q7" value="2" id="q7_2">
                        <label for="q7_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q7" value="3" id="q7_3">
                        <label for="q7_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q7" value="4" id="q7_4">
                        <label for="q7_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q7" value="5" id="q7_5">
                        <label for="q7_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 8 -->
            <div class="question-card">
                <div class="question-number">QUESTION 8</div>
                <div class="question-text">The system provides helpful feedback and error messages.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q8" value="1" id="q8_1" required>
                        <label for="q8_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q8" value="2" id="q8_2">
                        <label for="q8_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q8" value="3" id="q8_3">
                        <label for="q8_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q8" value="4" id="q8_4">
                        <label for="q8_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q8" value="5" id="q8_5">
                        <label for="q8_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 9 -->
            <div class="question-card">
                <div class="question-number">QUESTION 9</div>
                <div class="question-text">I feel confident using the system without assistance.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q9" value="1" id="q9_1" required>
                        <label for="q9_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q9" value="2" id="q9_2">
                        <label for="q9_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q9" value="3" id="q9_3">
                        <label for="q9_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q9" value="4" id="q9_4">
                        <label for="q9_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q9" value="5" id="q9_5">
                        <label for="q9_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <!-- Question 10 -->
            <div class="question-card">
                <div class="question-number">QUESTION 10</div>
                <div class="question-text">Overall, I am satisfied with the functionality of the system.</div>
                <div class="likert-scale">
                    <div class="likert-option">
                        <input type="radio" name="q10" value="1" id="q10_1" required>
                        <label for="q10_1">1<br>Strongly Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q10" value="2" id="q10_2">
                        <label for="q10_2">2<br>Disagree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q10" value="3" id="q10_3">
                        <label for="q10_3">3<br>Neutral</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q10" value="4" id="q10_4">
                        <label for="q10_4">4<br>Agree</label>
                    </div>
                    <div class="likert-option">
                        <input type="radio" name="q10" value="5" id="q10_5">
                        <label for="q10_5">5<br>Strongly Agree</label>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 py-3">
                    <i class="fas fa-paper-plane"></i> Submit Survey
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-lg px-5 py-3 ms-3">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
