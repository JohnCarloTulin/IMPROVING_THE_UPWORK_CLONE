<?php
// Include database configuration and model functions
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Start a session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, redirect to login if not
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// If the user is a client, redirect to the client dashboard
if ($_SESSION['is_client'] == 1) {
    header("Location: ../client/index.php");
    exit;
}

// Retrieve the username and user ID from the session
$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Fetch all the gigs from the database
$gigs = getAllGigs($pdo);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Open Gigs</title>

  <!-- Bootstrap CSS (for styling the page) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  
  <!-- jQuery (needed for the JavaScript functions below) -->
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  
  <!-- Google Fonts (Roboto for text styling) -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    /* Basic styling for the body, including font and background */
    body {
      font-family: Arial, sans-serif;
      font-size: 1rem;
      line-height: 1.6;
      background-color: #f9f9f9;
      color: rgb(0, 0, 0);
    }

    /* Styling for the greeting box that displays a welcome message */
    .greeting-box {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
    }

    /* Styling for individual gig cards */
    .card {
      border: none;
      border-left: 5px solid #008080;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
      background-color: #fff;
    }

    /* Styling for the proposal box (section where proposals are shown) */
    .proposal-box {
      background-color: #f1f1f1;
      padding: 1rem;
      border-radius: 8px;
      font-style: italic;
    }

    /* Styling for form labels */
    .form-group label {
      font-weight: bold;
    }

    /* Meta information for gigs (e.g., who posted, when) */
    .gig-meta {
      font-size: 0.9rem;
      color: rgb(0, 0, 0);
    }

    /* Placeholder text styling */
    input::placeholder {
      color: #888;
      opacity: 1;
    }

    /* Button styling */
    .btn {
      font-weight: bold;
      font-size: 0.95rem;
    }
  </style>

</head>
<body>
  <!-- Include the navigation bar -->
  <?php include 'includes/navbar.php'; ?>

  <div class="container py-4">
    <!-- Greeting message for the logged-in user -->
    <div class="greeting-box text-center">
      <h2 class="mb-3" style="font-weight: bold;">Welcome, <span class="text-primary"><?= htmlspecialchars($username) ?></span>!</h2>
      <p class="lead">Here are all the gigs currently open. You may submit only <strong>one proposal per gig</strong>.</p>
    </div>

    <!-- Loop through all gigs to display them -->
    <?php foreach ($gigs as $gig): ?>
      <!-- Get the user's proposal for this specific gig -->
      <?php $proposal = getProposalByGig($pdo, $gig['gig_id'], $userId); ?>
      
      <!-- Display each gig inside a card -->
      <div class="card mb-4">
        <div class="card-body">
          <!-- Display the gig title -->
          <h4 class="card-title mb-2" style="font-weight: bold;">Title: <?= htmlspecialchars($gig['title']) ?></h4> <br>
          
          <!-- Display the gig description -->
          <p class="card-text"><strong>Description: </strong><?= nl2br(htmlspecialchars($gig['description'])) ?></p>
          
          <!-- Display meta information about the gig: who posted it and when -->
          <p class="gig-meta">
            <span class="badge" style="background-color: #008080; color: white;">Posted by <?= htmlspecialchars($gig['username']) ?></span>
            <span class="ml-2 text-muted"><?= htmlspecialchars($gig['date_added']) ?></span>
          </p>

          <!-- Check if a proposal has already been submitted for this gig -->
          <?php if ($proposal): ?>
            <!-- Display the submitted proposal -->
            <h5 class="mt-4 text-success">Your Submitted Proposal:</h5>
            <div class="proposal-box">
              <strong>Your Response: </strong><?= nl2br(htmlspecialchars($proposal['gig_proposal_description'])) ?><br>
              <small class="text-muted">Submitted on <?= htmlspecialchars($proposal['date_added']) ?></small>
            </div>
            <!-- Option to delete the proposal -->
            <form class="deleteProposalForm mt-3 text-right">
              <input type="hidden" class="gig_proposal_id" value="<?= $proposal['gig_proposal_id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm">Delete Proposal</button>
            </form>
          <?php else: ?>
            <!-- If no proposal has been submitted, show a warning message -->
            <div class="alert alert-warning mt-4" role="alert">
              <strong>No proposal submitted yet.</strong>
            </div>
          <?php endif; ?>

          <!-- Form to submit a new proposal for the gig -->
          <form class="submitGigProposal mt-4">
            <input type="hidden" class="gig_id" value="<?= $gig['gig_id'] ?>">

            <!-- Textarea for entering the proposal description -->
            <div class="form-group">
              <label for="proposal">Enter Your Proposal:</label>
              <textarea 
                class="form-control gig_proposal_description" 
                placeholder="Why are you the best candidate?" 
                required 
                style="width: 100%; min-height: 50px; resize: none; overflow: hidden;" 
                oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';"
              ></textarea>
            </div>

            <!-- Submit button for the proposal -->
            <div class="text-right">
              <button type="submit" class="btn" style="background-color: #008080; color: white;">Submit</button>
            </div>
          </form>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Include the footer -->
  <?php include 'includes/footer.php'; ?>

  <script>
    $(function () {
      // Handle form submission for submitting a new proposal
      $('.submitGigProposal').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const gigId = $form.find('.gig_id').val().trim();
        const description = $form.find('.gig_proposal_description').val().trim();

        if (gigId && description) {
          // Send the proposal data to the server via POST request
          $.post("core/handleForms.php", {
            newGigProposal: 1,
            gig_id: gigId,
            gig_proposal_description: description
          }, function (response) {
            if (response) {
              location.reload();  // Reload the page to reflect the new proposal
            } else {
              alert("You're allowed to submit your proposal only once!");  // Alert if already submitted
            }
          });
        } else {
          alert("Please fill in all required fields.");  // Alert if fields are empty
        }
      });

      // Handle form submission for deleting an existing proposal
      $('.deleteProposalForm').on('submit', function (e) {
        e.preventDefault();
        const gigProposalId = $(this).find('.gig_proposal_id').val();

        // Confirm the delete action before proceeding
        if (confirm("Are you sure you want to delete this proposal?")) {
          $.post("core/handleForms.php", {
            deleteGigProposal: 1,
            gig_proposal_id: gigProposalId
          }, function () {
            location.reload();  // Reload the page after deleting the proposal
          });
        }
      });
    });
  </script>
</body>
</html>
