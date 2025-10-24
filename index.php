<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | BSU Lipa Centralized Lost & Found System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include 'imports.php'; ?>
</head>
<body class="bg-light">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.php">FOUND-IT</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="accounts/login.php">Login</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section id="home" class="d-flex align-items-center text-center text-white" style="height:100vh; background: linear-gradient(rgba(220,53,69,0.85), rgba(220,53,69,0.85)), url('assets/bsulipa.jpg') center/cover no-repeat;">
    <div class="container">
      <h1 class="display-4 fw-bold mb-3">FOUND-IT</h1>
      <p class="lead mb-4">BSU Lipa Centralized Lost & Found System</p>
      <a href="accounts/login.php" class="btn btn-light btn-lg fw-semibold">Go to Dashboard</a>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
          <img src="assets/bsulipa.jpg" class="img-fluid rounded shadow" alt="Lost and Found">
        </div>
        <div class="col-md-6">
          <h2 class="fw-bold mb-3 text-danger">About FOUND-IT</h2>
          <p class="text-muted">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Distinctio, eos molestiae! Perspiciatis culpa voluptatibus corporis rem fuga, consectetur quas. In, quae tempora voluptas veniam maiores consectetur repudiandae ipsum porro officia.
          </p>
          <p class="text-muted">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Numquam aperiam fugit possimus rem laboriosam quo odio corporis reiciendis! Error recusandae exercitationem architecto? Dolor ab consequatur atque! Earum cum quaerat impedit.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="features" class="py-5 bg-white">
    <div class="container text-center">
      <h2 class="fw-bold text-danger mb-5">Key Features</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="display-4 text-danger mb-3">
                <i class="bi bi-database"></i>
              </div>
              <h5 class="card-title fw-bold">Centralized Database</h5>
              <p class="card-text text-muted">All lost and found reports are securely stored and searchable in one platform.</p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="display-4 text-danger mb-3">
                <i class="bi bi-chat-dots"></i>
              </div>
              <h5 class="card-title fw-bold">SMS Notifications</h5>
              <p class="card-text text-muted">Users receive instant SMS alerts for updates, claims, or matches to their reports.</p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="display-4 text-danger mb-3">
                <i class="bi bi-bar-chart-line"></i>
              </div>
              <h5 class="card-title fw-bold">Analytics Dashboard</h5>
              <p class="card-text text-muted">Admins can visualize lost/found trends and report insights via Chart.js.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section
  <section id="contact" class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="fw-bold text-danger mb-4">Contact Us</h2>
      <p class="text-muted mb-5">Need help or have inquiries? Get in touch with the FOUND-IT team below.</p>

      <div class="row justify-content-center">
        <div class="col-md-6">
          <form>
            <div class="mb-3">
              <input type="text" class="form-control" placeholder="Your Name" required>
            </div>
            <div class="mb-3">
              <input type="email" class="form-control" placeholder="Your Email" required>
            </div>
            <div class="mb-3">
              <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger w-100">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </section> -->

  <!-- FOOTER -->
  <footer class="bg-danger text-white text-center py-3">
    <div class="container">
      <small>FOUND-IT | BSU Lipa Centralized Lost & Found System. This is for educational purposes only.</small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>
</html>
