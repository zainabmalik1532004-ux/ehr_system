<?php
require_once 'db_connect.php';

// Create survey responses table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS sus_responses (
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
)");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q1 = $_POST['q1'];
    $q2 = $_POST['q2'];
    $q3 = $_POST['q3'];
    $q4 = $_POST['q4'];
    $q5 = $_POST['q5'];
    $q6 = $_POST['q6'];
    $q7 = $_POST['q7'];
    $q8 = $_POST['q8'];
    $q9 = $_POST['q9'];
    $q10 = $_POST['q10'];
    
    // Calculate SUS score
    // For odd questions (1,3,5,7,9): score = response - 1
    // For even questions (2,4,6,8,10): score = 5 - response
    $score = 0;
    $score += ($q1 - 1);
    $score += (5 - $q2);
    $score += ($q3 - 1);
    $score += (5 - $q4);
    $score += ($q5 - 1);
    $score += (5 - $q6);
    $score += ($q7 - 1);
    $score += (5 - $q8);
    $score += ($q9 - 1);
    $score += (5 - $q10);
    
    // Multiply by 2.5 to get final score (0-100)
    $total_score = $score * 2.5;
    
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO sus_responses (q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, total_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$q1, $q2, $q3, $q4, $q5, $q6, $q7, $q8, $q9, $q10, $total_score]);
    
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUS Survey - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">EHR System</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sus_results.php">View Results</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>System Usability Scale (SUS) Survey</h3>
                        <p class="mb-0">Please rate your experience with the EHR Information System</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <h5>Thank you for your feedback!</h5>
                                <p>Your SUS score is: <strong><?php echo number_format($total_score, 2); ?></strong> out of 100</p>
                                <p>
                                    <?php if ($total_score >= 80): ?>
                                        Excellent usability!
                                    <?php elseif ($total_score >= 68): ?>
                                        Above average usability
                                    <?php elseif ($total_score >= 50): ?>
                                        Below average usability
                                    <?php else: ?>
                                        Poor usability
                                    <?php endif; ?>
                                </p>
                                <a href="sus_results.php" class="btn btn-primary">View All Results</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <strong>Instructions:</strong> Please use the EHR system first (register, login, add/edit/delete patients), then complete this survey. Rate each statement from 1 (Strongly Disagree) to 5 (Strongly Agree).
                            </div>
                            
                            <form method="POST" action="">
                                <?php
                                $questions = [
                                    "I think that I would like to use this system frequently",
                                    "I found the system unnecessarily complex",
                                    "I thought the system was easy to use",
                                    "I think that I would need the support of a technical person to be able to use this system",
                                    "I found the various functions in this system were well integrated",
                                    "I thought there was too much inconsistency in this system",
                                    "I would imagine that most people would learn to use this system very quickly",
                                    "I found the system very cumbersome to use",
                                    "I felt very confident using the system",
                                    "I needed to learn a lot of things before I could get going with this system"
                                ];
                                
                                foreach ($questions as $index => $question):
                                    $qNum = $index + 1;
                                ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold"><?php echo $qNum; ?>. <?php echo $question; ?></label>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">Strongly Disagree</span>
                                            <span class="text-muted small">Strongly Agree</span>
                                        </div>
                                        <div class="btn-group w-100" role="group">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" class="btn-check" name="q<?php echo $qNum; ?>" id="q<?php echo $qNum; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="btn btn-outline-primary" for="q<?php echo $qNum; ?>_<?php echo $i; ?>"><?php echo $i; ?></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Submit Survey</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
