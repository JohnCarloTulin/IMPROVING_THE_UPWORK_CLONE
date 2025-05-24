<?php
// Load the database configuration and model logic
require_once 'core/dbConfig.php';
require_once 'core/models.php';

// Start session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Redirect freelancers away — only clients can access this page
if ($_SESSION['is_client'] == 0) {
    header("Location: ../freelancer/index.php");
    exit;
}

// SQL query to retrieve all proposals along with the gig and freelancer details
$sql = "
    SELECT 
        p.gig_proposal_id,
        p.gig_proposal_description AS proposal_description,
        p.date_added AS proposal_date_added,
        g.gig_id,
        g.gig_title,
        g.gig_description,
        f.user_id AS freelancer_id,
        f.first_name,
        f.last_name
    FROM gig_proposals p
    JOIN gigs g ON p.gig_id = g.gig_id
    JOIN fiverr_users f ON p.user_id = f.user_id
    ORDER BY p.date_added DESC
";

// Execute the prepared statement and fetch all results
$stmt = $pdo->prepare($sql);
$stmt->execute();
$allProposals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>All Gig Proposals</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <!-- Bootstrap CSS for styling -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    />

    <!-- Inline styles for proposal cards and layout -->
    <style>
        body {
            font-family: "Arial", sans-serif;
            background-color: #f8f9fa;
        }
        .display-4 {
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 0.5rem;
            color: rgb(0, 0, 0);
            text-align: center;
        }
        .subheading {
            color: rgb(40, 42, 44);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            text-align: center;
        }
        .proposal-section {
            max-width: 1140px;
            margin: 0 auto 4rem auto;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: center;
        }
        .proposal-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 3px 12px rgb(0 0 0 / 0.1);
            width: 350px;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgb(0 0 0 / 0.15);
        }
        .proposal-title {
            font-size: 1rem;
            font-weight: 700;
            color: #008080;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .proposal-proponent-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: rgb(0, 0, 0);
            margin-bottom: 0.5rem;
        }
        .submission-date {
            font-size: 0.85rem;
            color: rgb(0, 0, 0);
            margin-bottom: 1rem;
        }
        .proposal-description {
            flex-grow: 1;
            font-size: 0.95rem;
            color: rgb(0, 0, 0);
            white-space: pre-line;
            margin-bottom: 1.25rem;
        }
        .gig-info {
            font-size: 0.9rem;
            color: rgb(40, 42, 44);
            margin-bottom: 1rem;
        }
        .btn-outline-primary {
            width: 100%;
            font-weight: 600;
            color: #008080;
            border-color: #008080;
            background-color: transparent;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .btn-outline-primary:hover {
            background-color: #008080 !important;
            color: white !important;
            border-color: #008080 !important;
        }
    </style>
</head>
<body>

<!-- Navigation bar -->
<?php include 'includes/navbar.php'; ?>

<!-- Main container -->
<div class="container">
    <h1 class="display-4">All Gig Proposals</h1>
    <p class="subheading">Browse all proposals submitted by freelancers</p>

    <section class="proposal-section">
        <?php if (count($allProposals) === 0): ?>
            <!-- Display this message if no proposals are found -->
            <p>No proposals found.</p>
        <?php else: ?>
            <?php foreach ($allProposals as $proposal): ?>
                <!-- Card layout for each proposal -->
                <div class="proposal-card">
                    <div class="proposal-title">Proposal</div>
                    
                    <!-- Display freelancer's name -->
                    <h2 class="proposal-proponent-name">
                        Proponent: <?= htmlspecialchars("{$proposal['last_name']}, {$proposal['first_name']}") ?>
                    </h2>
                    
                    <!-- Display proposal submission date -->
                    <div class="submission-date">
                        Submitted on <?= date("F j, Y, g:i a", strtotime($proposal['proposal_date_added'])) ?>
                    </div>

                    <!-- Show proposal description with line breaks preserved -->
                    <p class="proposal-description">
                        <strong>Proposal description:</strong><br />
                        <?= nl2br(htmlspecialchars($proposal['proposal_description'])) ?>
                    </p>

                    <!-- Show related gig title and description -->
                    <div class="gig-info">
                        <strong>Gig Title:</strong> <?= htmlspecialchars($proposal['gig_title']) ?><br /><br>
                        <strong>Gig Description:</strong> <?= nl2br(htmlspecialchars($proposal['gig_description'])) ?>
                    </div>

                    <!-- Button linking to all proposals for the specific gig -->
                    <a href="get_gig_proposals.php?gig_id=<?= urlencode($proposal['gig_id']) ?>" 
                        class="btn btn-outline-primary" 
                        role="button">
                        View proposals for the specific gig
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
