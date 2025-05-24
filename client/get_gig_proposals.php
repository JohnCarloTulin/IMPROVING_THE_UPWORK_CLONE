<?php 
  // Include database configuration and model files
  require_once 'core/dbConfig.php'; 
  require_once 'core/models.php'; 

  // Check if session is not started, and start it if not
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  // If no user is logged in, redirect to login page
  if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
  }

  // If the logged-in user is not a client (is_client = 0), redirect to freelancer homepage
  if ($_SESSION['is_client'] == 0) {
    header("Location: ../freelancer/index.php");
    exit;
  }

  // Initialize message variable for flash messages
  $message = '';

  // Handle scheduling a new interview
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['time_start'], $_POST['time_end'], $_POST['freelancer_id'], $_POST['gig_id']) && !isset($_POST['edit_interview_id']) && !isset($_POST['delete_interview_id'])) {
      // Extract values from the POST request
      $freelancer_id = $_POST['freelancer_id'];
      $gig_id_post = $_POST['gig_id'];
      $time_start = $_POST['time_start'];
      $time_end = $_POST['time_end'];

      // Validate that both start and end times are selected
      if (!$time_start || !$time_end) {
          $_SESSION['flash_message'] = '<div class="alert alert-danger">Please select both start and end times.</div>';
      } elseif ($time_start >= $time_end) {
          // Ensure that the start time is before the end time
          $_SESSION['flash_message'] = '<div class="alert alert-danger">End time must be after start time.</div>';
      } else {
          // Check for scheduling conflicts with existing interviews
          $sqlConflict = "SELECT COUNT(*) FROM gig_interviews WHERE gig_id = ? AND
                          ((time_start < ? AND time_end > ?) OR (time_start >= ? AND time_start < ?))
                          AND status != 'Rejected'";
          $stmtConflict = $pdo->prepare($sqlConflict);
          $stmtConflict->execute([$gig_id_post, $time_end, $time_start, $time_start, $time_end]);
          $conflictCount = $stmtConflict->fetchColumn();

          // If conflict found, show an error message
          if ($conflictCount > 0) {
              $_SESSION['flash_message'] = '<div class="alert alert-danger">This time slot conflicts with another scheduled interview.</div>';
          } else {
              // Insert the new interview into the database
              $sqlInsert = "INSERT INTO gig_interviews (gig_id, freelancer_id, time_start, time_end, status) VALUES (?, ?, ?, ?, 'Pending')";
              $stmtInsert = $pdo->prepare($sqlInsert);
              if ($stmtInsert->execute([$gig_id_post, $freelancer_id, $time_start, $time_end])) {
                  $_SESSION['flash_message'] = '<div class="alert alert-success">Interview scheduled successfully!</div>';
              } else {
                  $_SESSION['flash_message'] = '<div class="alert alert-danger">Failed to schedule interview.</div>';
              }
          }
      }

      // Redirect to the same page to prevent form resubmission
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
  }

  // Handle deleting an interview
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_interview_id'])) {
      // Get the interview ID to delete
      $interview_id = $_POST['delete_interview_id'];
      // Prepare the SQL delete statement
      $sql = "DELETE FROM gig_interviews WHERE gig_interview_id = ?";
      $stmt = $pdo->prepare($sql);
      if ($stmt->execute([$interview_id])) {
          $_SESSION['flash_message'] = '<div class="alert alert-success">Interview deleted successfully.</div>';
      } else {
          $_SESSION['flash_message'] = '<div class="alert alert-danger">Failed to delete interview.</div>';
      }
      // Redirect to the same page after deletion
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
  }

  // Handle updating an existing interview
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_interview_id'], $_POST['edit_time_start'], $_POST['edit_time_end'])) {
      // Extract the updated values
      $interview_id = $_POST['edit_interview_id'];
      $time_start = $_POST['edit_time_start'];
      $time_end = $_POST['edit_time_end'];

      // Validate the new time range
      if (!$time_start || !$time_end) {
          $_SESSION['flash_message'] = '<div class="alert alert-danger">Please select both start and end times for update.</div>';
      } elseif ($time_start >= $time_end) {
          // Ensure the end time is after the start time
          $_SESSION['flash_message'] = '<div class="alert alert-danger">End time must be after start time.</div>';
      } else {
          // Check for conflicts, excluding the current interview being updated
          $sqlConflict = "SELECT COUNT(*) FROM gig_interviews WHERE gig_id = ? AND gig_interview_id != ? AND
                          ((time_start < ? AND time_end > ?) OR (time_start >= ? AND time_start < ?))
                          AND status != 'Rejected'";
          $stmtConflict = $pdo->prepare($sqlConflict);
          $stmtConflict->execute([$gig_id, $interview_id, $time_end, $time_start, $time_start, $time_end]);
          $conflictCount = $stmtConflict->fetchColumn();

          // If a conflict is found, show an error message
          if ($conflictCount > 0) {
              $_SESSION['flash_message'] = '<div class="alert alert-danger">This time slot conflicts with another scheduled interview.</div>';
          } else {
              // Update the interview in the database
              $sqlUpdate = "UPDATE gig_interviews SET time_start = ?, time_end = ? WHERE gig_interview_id = ?";
              $stmtUpdate = $pdo->prepare($sqlUpdate);
              if ($stmtUpdate->execute([$time_start, $time_end, $interview_id])) {
                  $_SESSION['flash_message'] = '<div class="alert alert-success">Interview updated successfully!</div>';
              } else {
                  $_SESSION['flash_message'] = '<div class="alert alert-danger">Error updating interview.</div>';
              }
          }
      }
      // Redirect to the same page after updating
      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
  }

  // Get gig details and interviews from the database
  $gig_id = $_GET['gig_id'];
  $gigDetails = getGigById($pdo, $gig_id);
  $interviews = getAllInterviewsByGig($pdo, $gig_id);
  $proposals = getProposalsByGigId($pdo, $gig_id);
?>

<!-- HTML Structure starts here -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gig Proposals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS for styling -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
  <style>
    /* Custom Styles */
    body {
      font-family: "Arial", sans-serif;
      background-color: #f8f9fa;
    }
    .display-4 {
      font-weight: 700;
      margin-top: 2rem;
      margin-bottom: 0.5rem;
      color:rgb(0, 0, 0);
    }
    .subheading {
      color:rgb(40, 42, 44);
      margin-bottom: 2rem;
      font-size: 1.1rem;
    }
    .gig-info-card, .interviews-card {
      box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
      border-radius: 0.75rem;
      background: white;
      padding: 1.5rem;
      margin-bottom: 2rem;
      max-width: 1140px;
      margin-left: auto;
      margin-right: auto;
    }
    .gig-info-card h4, .interviews-card h4 {
      font-weight: 700;
      color:rgb(6, 129, 129);
      margin-bottom: 1rem;
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
      color:rgb(0, 0, 0);
      margin-bottom: 0.5rem;
    }
    .submission-date {
      font-size: 0.85rem;
      color:rgb(67, 73, 78);
      margin-bottom: 1rem;
    }
    .proposal-description {
      flex-grow: 1;
      font-size: 0.95rem;
      color:rgb(61, 66, 71);
      white-space: pre-line;
      margin-bottom: 1.25rem;
    }
    .btn-outline-primary {
      width: 100%;
      margin-bottom: 0.75rem;
      font-weight: 600;
    }
    .addNewInterviewForm {
      border-top: 1px solid #dee2e6;
      padding-top: 1rem;
    }
    .addNewInterviewForm .form-group label {
      font-weight: 600;
      font-size: 0.9rem;
    }
    .addNewInterviewForm input[type="datetime-local"] {
      font-size: 0.9rem;
    }
    .addNewInterviewForm .btn-success {
      font-weight: 700;
      font-size: 0.95rem;
    }
    table.table {
      margin-bottom: 0;
    }
    table.table th, table.table td {
      vertical-align: middle !important;
    }
  </style>
</head>
<body>
  <!-- Navbar inclusion -->
  <?php include 'includes/navbar.php'; ?>
  
  <div class="container">
    <h1 class="display-4 text-center">Gig Proposals</h1>
    <p class="subheading text-center">Review and schedule interviews below</p>

    <!-- Flash message handling -->
    <?php 
      if (!empty($_SESSION['flash_message'])) {
          echo $_SESSION['flash_message'];
          unset($_SESSION['flash_message']); // Remove the flash message after it's displayed
      }
    ?>

    <!-- Gig Info Section -->
    <div class="gig-info-card">
      <h4>Title: <?php echo htmlspecialchars($gigDetails['gig_title']); ?></h4>
      <p>Description: <?php echo nl2br(htmlspecialchars($gigDetails['gig_description'])); ?></p>
      <small class="text-muted"><?php echo htmlspecialchars($gigDetails['date_added']); ?></small><br>
      <small class="text-muted">Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?></small>
    </div>

    <!-- Scheduled Interviews Section -->
    <div class="interviews-card">
      <h4>Scheduled Interviews</h4>
      <table class="table table-striped table-sm">
        <thead>
          <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($interviews as $interview): ?>
            <tr>
              <!-- Display interview details -->
              <td><?= htmlspecialchars($interview['first_name']) ?></td>
              <td><?= htmlspecialchars($interview['last_name']) ?></td>
              <td><?= htmlspecialchars($interview['time_start']) ?></td>
              <td><?= htmlspecialchars($interview['time_end']) ?></td>
              <td><strong class="text-<?= strtolower($interview['status']) ?>">
                <?= htmlspecialchars($interview['status']) ?>
              </strong></td>
              <td>
                <!-- Edit interview button -->
                <button class="btn btn-sm btn-primary btn-edit" data-id="<?= $interview['gig_interview_id'] ?>">Edit</button>
                
                <!-- Delete interview form -->
                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this interview?');">
                  <input type="hidden" name="delete_interview_id" value="<?= $interview['gig_interview_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
            
            <!-- Row for editing interview time -->
            <tr id="edit-row-<?= $interview['gig_interview_id'] ?>" style="display:none;">
              <td colspan="6">
                <form method="POST" class="form-inline">
                  <input type="hidden" name="edit_interview_id" value="<?= $interview['gig_interview_id'] ?>">
                  <div class="form-group mr-2">
                    <label for="edit_time_start_<?= $interview['gig_interview_id'] ?>" class="mr-1">Start</label>
                    <input type="datetime-local" id="edit_time_start_<?= $interview['gig_interview_id'] ?>" name="edit_time_start" class="form-control" required
                          value="<?= date('Y-m-d\TH:i', strtotime($interview['time_start'])) ?>" min="<?= date('Y-m-d\TH:i') ?>">
                  </div>
                  <div class="form-group mr-2">
                    <label for="edit_time_end_<?= $interview['gig_interview_id'] ?>" class="mr-1">End</label>
                    <input type="datetime-local" id="edit_time_end_<?= $interview['gig_interview_id'] ?>" name="edit_time_end" class="form-control" required
                          value="<?= date('Y-m-d\TH:i', strtotime($interview['time_end'])) ?>" min="<?= date('Y-m-d\TH:i') ?>">
                  </div>
                  <button type="submit" class="btn btn-success btn-sm">Save</button>
                  <button type="button" class="btn btn-secondary btn-sm btn-cancel" data-id="<?= $interview['gig_interview_id'] ?>">Cancel</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Proposals Section -->
    <section class="proposal-section">
      <?php foreach ($proposals as $proposal): ?>
      <div class="proposal-card">
        <div class="proposal-title">Proposal</div>
        <h2 class="proposal-proponent-name">Proponent: <?= htmlspecialchars("{$proposal['last_name']}, {$proposal['first_name']}") ?></h2>
        <div class="submission-date">Submitted on <?= date("F j, Y, g:i a", strtotime($proposal['date_added'])) ?></div>
        <p class="proposal-description"><strong>Proposal description: </strong><br><?= nl2br(htmlspecialchars($proposal['description'])) ?></p>

        <!-- Schedule Interview form -->
        <form method="POST" class="addNewInterviewForm">
          <input type="hidden" name="freelancer_id" value="<?= $proposal['user_id'] ?>">
          <input type="hidden" name="gig_id" value="<?= $gig_id ?>">
          <div class="form-group">
            <label for="start">Start Time</label>
            <input type="datetime-local" name="time_start" class="form-control" required min="<?= date('Y-m-d\TH:i') ?>">
          </div>
          <div class="form-group">
            <label for="end">End Time</label>
            <input type="datetime-local" name="time_end" class="form-control" required min="<?= date('Y-m-d\TH:i') ?>">
          </div>
          <button type="submit" class="btn btn-success btn-block">Schedule Interview</button>
        </form>
      </div>
      <?php endforeach; ?>
    </section>
  </div>

  <!-- JavaScript for handling the Edit/Cancel buttons -->
  <script>
    // Handle the toggle of the edit interview row visibility
    document.querySelectorAll('.btn-edit').forEach(button => {
      button.addEventListener('click', () => {
        const id = button.dataset.id;
        const editRow = document.getElementById('edit-row-' + id);
        // Toggle visibility of the row
        if (editRow.style.display === 'none') {
          editRow.style.display = 'table-row';
        } else {
          editRow.style.display = 'none';
        }
      });
    });

    // Handle the cancel button to hide the edit row
    document.querySelectorAll('.btn-cancel').forEach(button => {
      button.addEventListener('click', () => {
        const id = button.dataset.id;
        const editRow = document.getElementById('edit-row-' + id);
        editRow.style.display = 'none';
      });
    });
  </script>
</body>
</html>
