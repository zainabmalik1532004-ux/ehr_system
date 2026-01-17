<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

// Get all responses
$sql = "SELECT * FROM sus_responses ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Calculate statistics
$total_responses = 0;
$avg_score = 0;
$scores = [];

if ($result && mysqli_num_rows($result) > 0) {
    $sum = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $scores[] = $row;
        $sum += $row['total_score'];
        $total_responses++;
    }
    if ($total_responses > 0) {
        $avg_score = $sum / $total_responses;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUS Results - EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .results-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .results-header h2 {
            color: #667eea;
            font-weight: bold;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .stat-card h3 {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .score-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #d1ecf1; color: #0c5460; }
        .score-ok { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="results-container">
    <div class="results-header">
        <i class="fas fa-chart-bar fa-4x text-primary mb-3"></i>
        <h2>System Usability Survey Results</h2>
        <p class="text-muted">Comprehensive analysis of user feedback</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <p class="mb-0">Average Score</p>
                <h3><?php echo $total_responses > 0 ? number_format($avg_score, 1) : '0.0'; ?></h3>
                <p class="mb-0">out of 100</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <p class="mb-0">Total Responses</p>
                <h3><?php echo $total_responses; ?></h3>
                <p class="mb-0">surveys completed</p>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>Benchmark:</strong> Scores above 80 indicate excellent usability. Scores above 60 are considered good.
    </div>

    <h4 class="mb-3"><i class="fas fa-list"></i> All Survey Responses</h4>

    <?php if ($total_responses > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Response #</th>
                        <th>Score</th>
                        <th>Rating</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($scores as $score): 
                        $score_value = $score['total_score'];
                        if ($score_value >= 80) {
                            $badge_class = 'score-excellent';
                            $rating = 'Excellent';
                        } elseif ($score_value >= 60) {
                            $badge_class = 'score-good';
                            $rating = 'Good';
                        } elseif ($score_value >= 40) {
                            $badge_class = 'score-ok';
                            $rating = 'Fair';
                        } else {
                            $badge_class = 'score-poor';
                            $rating = 'Poor';
                        }
                    ?>
                        <tr>
                            <td><strong>#<?php echo $counter++; ?></strong></td>
                            <td><strong><?php echo number_format($score_value, 1); ?></strong> / 100</td>
                            <td><span class="score-badge <?php echo $badge_class; ?>"><?php echo $rating; ?></span></td>
                            <td><?php echo date('d M Y, H:i', strtotime($score['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center py-5">
            <i class="fas fa-inbox fa-4x mb-3 d-block text-muted"></i>
            <h5><strong>No survey responses yet.</strong></h5>
            <p class="mb-0">Be the first to complete the survey!</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="sus_survey.php" class="btn btn-primary btn-lg px-4 me-2">
            <i class="fas fa-clipboard-list"></i> Take Survey
        </a>
        <a href="dashboard.php" class="btn btn-secondary btn-lg px-4">
            <i class="fas fa-home"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
