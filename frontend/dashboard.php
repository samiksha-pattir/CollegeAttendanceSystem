<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - College Attendance System</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">College Attendance System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="dashboard.html">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="attendance.html">Attendance</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="reports.html">Reports</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.html">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mt-4">
    <h1 class="text-center">Welcome, [User's Name]!</h1>
    <p class="lead text-center">This is your personal dashboard to manage attendance and view reports.</p>

    <!-- Dashboard Cards -->
    <div class="row mt-4">
      <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
          <div class="card-header">Attendance</div>
          <div class="card-body">
            <h5 class="card-title">View Your Attendance</h5>
            <p class="card-text">Track your attendance for different courses here.</p>
            <a href="attendance.html" class="btn btn-light">View Attendance</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
          <div class="card-header">Reports</div>
          <div class="card-body">
            <h5 class="card-title">View Attendance Reports</h5>
            <p class="card-text">Download and analyze your attendance records.</p>
            <a href="reports.html" class="btn btn-light">View Reports</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
          <div class="card-header">Settings</div>
          <div class="card-body">
            <h5 class="card-title">Manage Your Account</h5>
            <p class="card-text">Update your account settings or change your password.</p>
            <a href="settings.html" class="btn btn-light">Account Settings</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
