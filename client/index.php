<?php 
require_once 'core/dbConfig.php'; 
require_once 'core/models.php'; 

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

if ($_SESSION['is_client'] == 0) {
  header("Location: ../freelancer/index.php");
  exit;
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" 
          integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous" />
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <style>
      body {
        font-family: Arial, sans-serif;
        background-color: #f9f9f9;
        color: #212529;
      }

      .container-fluid {
        padding: 2rem 1rem 3rem;
      }

      /* Global font override */
      .card, .card-body, .card-header, .card-body p, .welcome-card {
        font-family: Arial, sans-serif !important;
      }

      .card {
        border: none;
        border-left: 5px solid #008080;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        background-color: #fff;
      }

      .welcome-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        background-color: #fff;
        padding: 2rem;
        margin: 0 auto 2rem;
      }

      .welcome-card h1 {
        margin-bottom: 0;
        font-weight: bold;
        font-size: 50px;
      }

      .welcome-card .text-success {
        font-weight: 700;
      }

      form.createNewGig {
        margin-top: 2rem;
        padding: 1.5rem;
        border-radius: 10px;
      }

      form.createNewGig label {
        font-weight: bold;
      }

      .btn-primary {
        font-weight: 600;
      }

      .card-header h4 {
        margin: 0;
        font-weight: 700;
      }

      .card-body p {
        font-size: 1rem;
      }

      .card-body i {
        color: #6c757d;
        font-style: italic;
        font-size: 0.9rem;
      }

      .d-none {
        display: none !important;
      }
    </style>
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="container-fluid">

    <!-- Welcome Card -->
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="welcome-card">
          <h1>
            Hello,
            <span class="text-success"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
          </h1>
          <p>Great to see you again! Got any gig plans in mind? Let’s make it happen!</p>
          <div class="col" style="text-align: right; margin-top: 20px;">
            <button class="showCreateGigForm btn" style="background-color: #008080; color: white; border: none; font-weight: bold;">
              Create New Gig
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Gig Form -->
    <div class="row justify-content-center">
      <div class="col-md-8">
        <form class="createNewGig card p-4 d-none w-100">
          <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" class="title form-control" required>
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea 
              id="description" 
              class="description form-control" 
              required 
              style="width: 100%; min-height: 50px; resize: none; overflow: hidden;" 
              oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';"
            ></textarea>
          </div>
          
          <input type="submit" class="btn float-right mt-3" value="Create Gig" style="background-color: #008080; color: white; border: none;">
        </form>

      </div>
    </div>

    <!-- Gigs Section Header -->
    <h1 style="text-align: center; margin-top: 20px; font-weight: bold ; margin-bottom: -20px; font-size: 50px;">
      Gigs Posted
    </h1>
    <p style="text-align: center; margin-top: 20px; font-size: 20px;">
      Here are all the gigs open
    </p>

    <!-- Gigs List -->
    <div class="row justify-content-center">
      <div class="col-md-8">
        <?php $getAllGigs = getAllGigs($pdo); ?>
        <?php if (!empty($getAllGigs)) { ?>
          <?php foreach ($getAllGigs as $row) { ?>
            <div class="card shadow mt-4 p-4">
              <div class="card-header">
                <h4>Title: <?php echo htmlspecialchars($row['title']); ?></h4>
              </div>
              <div class="card-body">
                <p><strong>Description: </strong><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <p><i><strong>Created by: </strong><?php echo htmlspecialchars($row['username']); ?></i></p>
                <p><i><strong>Date added: </strong><?php echo htmlspecialchars($row['date_added']); ?></i></p>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
              <p style="text-align: center; margin-top: 20px; font-size: 20px;">
                -- No records yet --
              </p>
        <?php } ?>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>

  <script>
    $(document).ready(function() {
      $('.showCreateGigForm').on('click', function () {
        $('.createNewGig').toggleClass('d-none');
      });

      $('.createNewGig').on('submit', function (event) {
        event.preventDefault();

        var formData = {
          title: $(this).find('.title').val(),
          description: $(this).find('.description').val(),
          createNewGig: 1
        };

        if (formData.title !== "" && formData.description !== "") {
          $.ajax({
            type: "POST",
            url: "core/handleForms.php",
            data: formData,
            success: function () {
              location.reload();
            },
            error: function() {
              alert("An error occurred, please try again.");
            }
          });
        } else {
          alert("Make sure there are no empty input fields!");
        }
      });
    });
  </script>
</body>
</html>
