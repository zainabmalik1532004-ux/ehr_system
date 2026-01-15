<?php
require_once 'db_connect.php';

// Get all survey responses
$stmt = $pdo->query("SELECT * FROM sus_responses ORDER BY created_at DESC");
$responses = $stmt->fetchAll();

// Calculate statistics
$total_responses = count($responses);
$avg_score = 0;
if ($total_responses > 0) {
    $sum = 0;
    foreach ($responses as $response) {
        $sum += $response['total_score'];
    }
    $avg_score = $sum / $total_responses;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUS Results</title>
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
                        <a class="nav-link" href="sus_survey.php">Take Survey</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>SUS Survey Results</h2>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>Total Responses</h5>
                        <h2><?php echo $total_responses; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>Average SUS Score</h5>
                        <h2><?php echo number_format($avg_score, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>Rating</h5>
                        <h2>
                            <?php 
                            if ($avg_score >= 80) echo "Excellent";
                            elseif ($avg_score >= 68) echo "Good";
                            elseif ($avg_score >= 50) echo "OK";
                            else echo "Poor";
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h4>All Survey Responses</h4>
            </div>
            <div class="card-body">
                <?php if ($total_responses > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Response #</th>
                                <th>SUS Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses as $index => $response): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo number_format($response['total_score'], 2); ?></strong></td>
                                    <td><?php echo date('F d, Y H:i', strtotime($response['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No survey responses yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <h5>About SUS Scores:</h5>
            <ul>
                <li><strong>80-100:</strong> Excellent usability</li>
                <li><strong>68-79:</strong> Good usability (above average)</li>
                <li><strong>50-67:</strong> OK usability (below average)</li>
                <li><strong>Below 50:</strong> Poor usability</li>
            </ul>
            <p class="mb-0"><strong>Industry average:</strong> 68 points</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
