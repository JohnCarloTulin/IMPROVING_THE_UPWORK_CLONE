<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

// Redirect to login page if user is not logged in
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

// Redirect non-client users (e.g., employees) to their respective dashboard
if ($_SESSION['is_client'] == 0) {
  header("Location: ../employees/index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS and jQuery -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
      integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <style>
      /* Styling for layout and UI components */
      body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        color: #212529;
      }

      .container-fluid {
        padding: 2rem 1rem 3rem;
      }

      .display-4 {
        font-weight: 700;
        margin-bottom: 2rem;
      }

      .card {
        border: none;
        border-left: 5px solid #008080;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        background-color: #fff;
      }

      .card-header {
        background-color: rgb(243, 243, 243);
        border-bottom: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
        font-size: 1.2rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        border-radius: 12px 12px 0 0;
        padding: 1rem 1.5rem;
      }

      .card-body p {
        font-size: 1rem;
        margin-bottom: 0.5rem;
      }

      .card-body i {
        font-size: 0.9rem;
        color: #6c757d;
      }

      .btn-danger {
        font-weight: 500;
      }

      .btn-primary {
        font-weight: 600;
      }

      .editGigForm {
        background-color: #f1f1f1;
        padding: 1rem;
        border-radius: 8px;
      }

      .editGigForm label {
        font-weight: 600;
      }

      .gigContainer {
        transition: box-shadow 0.3s;
      }

      .gigContainer:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
      }

      a {
        color: #007BFF;
        text-decoration: none;
      }

      a:hover {
        text-decoration: underline;
      }
    </style>
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
      <div class="display-4 text-center">Gigs Posted. Double click to edit</div>
      <div class="row justify-content-center">
        <div class="col-md-8">
          <?php 
          // Fetch all gigs posted by the logged-in client
          $getAllGigsByUserId = getAllGigsByUserId($pdo, $_SESSION['user_id']); 
          ?>
          
          <?php foreach ($getAllGigsByUserId as $row) { ?>
            <!-- Display gig in a styled card -->
            <div class="gigContainer card shadow mt-4 p-4" gig_id="<?php echo $row['gig_id'] ?>">
              <div class="card-header">
                <span style="font-weight: bold; font-size: 30px;">Title: <?php echo htmlspecialchars($row['title']); ?></span>
                <button class="deleteGigBtn btn btn-danger btn-sm" style="font-weight: bold; font-size: 18px;">Delete Gig</button>
              </div>
              <div class="card-body">
                <p><strong>Description: </strong><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <p><i>Date added: <?php echo htmlspecialchars($row['date_added']); ?></i></p>
                <p><i>By: <?php echo htmlspecialchars($row['username']); ?></i></p>
                <div class="d-flex justify-content-end mb-3">
                  <a href="get_gig_proposals.php?gig_id=<?php echo $row['gig_id']; ?>" class="btn btn-sm" style="font-weight: bold; font-size: 18px; background-color: #008080; color: white;">
                    See Gig Proposals
                  </a>
                </div>

                <!-- Editable form for gig details (hidden by default) -->
                <form class="editGigForm d-none mt-5">
                  <div class="form-group">
                    <input type="hidden" value="<?php echo $row['gig_id']; ?>" class="form-control gig_id" required>
                    <label>Title</label>
                    <input type="text" value="<?php echo htmlspecialchars($row['title']); ?>" class="form-control title" required>
                  </div>
                  <div class="form-group">
                    <label>Description</label>
                    <input type="text" value="<?php echo htmlspecialchars($row['description']); ?>" class="form-control description" required>
                  </div>
                  <input type="submit" class="btn btn-primary float-right mt-5" value="Save Changes">
                </form>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
      // Toggle the edit form on double-click
      $('.gigContainer').on('dblclick', function () {
        $(this).find('.editGigForm').toggleClass('d-none');
      });

      // Handle gig deletion
      $('.deleteGigBtn').on('click', function () {
        var gig_id = $(this).closest('.gigContainer').attr('gig_id');
        if (gig_id !== "") {
          if (confirm("Are you sure you want to delete this gig?")) {
            $.ajax({
              type: "POST",
              url: "core/handleForms.php",
              data: { gig_id: gig_id, deleteGig: 1 },
              success: function () {
                location.reload();
              }
            });
          }
        } else {
          alert("An error occurred with your input");
        }
      });

      // Handle gig update form submission
      $('.editGigForm').on('submit', function (event) {
        event.preventDefault();
        var form = $(this);
        var formData = {
          gig_id: form.find('.gig_id').val(),
          title: form.find('.title').val(),
          description: form.find('.description').val(),
          updateGig: 1
        };

        if (formData.gig_id && formData.title && formData.description) {
          $.ajax({
            type: "POST",
            url: "core/handleForms.php",
            data: formData,
            success: function () {
              location.reload();
            }
          });
        } else {
          alert("Make sure the input fields are not empty!");
        }
      });
    </script>
  </body>
</html>
