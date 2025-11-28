<?php
session_start();
require_once __DIR__ . "/Config/database.php";

$database = new Database();
$conn = $database->connect();

$userEmail = $_POST['username'] ?? '';
$userDisplay = $_POST['user_display'] ?? '';

$showRegister = false;
$modalMessage = '';
$modalType = '';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = trim($_POST['username'] ?? '');
    $user_name = trim($_POST['user_display'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$email || !$user_name || !$password || !$confirm) {
        $modalMessage = "Please fill in all fields.";
        $modalType = 'error';
        $showRegister = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $modalMessage = "Invalid email address.";
        $modalType = 'error';
        $showRegister = true;
    } elseif ($password !== $confirm) {
        $modalMessage = "Passwords do not match.";
        $modalType = 'error';
        $showRegister = true;
    } else {
        $role = null;
        $roleId = null;

        $stmt = $conn->prepare("SELECT doc_id FROM doctor WHERE doc_email = ?");
        $stmt->execute([$email]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $role = 'doctor';
            $roleId = $row['doc_id'];
        }

        if (!$role) {
            $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE staff_email = ?");
            $stmt->execute([$email]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $role = 'staff';
                $roleId = $row['staff_id'];
            }
        }

        if (!$role) {
            $stmt = $conn->prepare("SELECT pat_id FROM patient WHERE pat_email = ?");
            $stmt->execute([$email]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $role = 'patient';
                $roleId = $row['pat_id'];
            }
        }

        if (!$role) {
            $modalMessage = "Email not found in any records!";
            $modalType = 'error';
            $showRegister = true;
        } else {
            $check = $conn->prepare("SELECT user_id FROM user WHERE user_name = ?");
            $check->execute([$user_name]);
            if ($check->fetch()) {
                $modalMessage = "Username already taken.";
                $modalType = 'error';
                $showRegister = true;
            } else {
                if ($role === 'doctor') {
                    $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, doc_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
                } elseif ($role === 'staff') {
                    $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, staff_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
                } else {
                    $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, pat_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
                }

                $stmt->execute([$user_name, $password, $roleId]);
                $modalMessage = "🎉 $role account successfully registered! You can now log in.";
                $modalType = 'success';
                $showRegister = true;
                $userEmail = '';
                $userDisplay = '';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BioBridge Medical Center - Your Health, Our Priority</title>
<link rel="icon" type="image/png" href="Assets/BioBridge_Medical_Center_Logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .transition-section { transition: all 0.8s ease-in-out; }
    .translate-left { transform: translateX(-100%); opacity: 0; pointer-events: none; }
    .translate-center { transform: translateX(0); opacity: 1; pointer-events: auto; }
    html { scroll-behavior: smooth; }
    .service-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .service-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
</style>
</head>
<body class="bg-white text-gray-900">

<!-- Navigation -->
<nav class="fixed top-0 w-full bg-white shadow-md z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <div class="flex items-center space-x-3">
        <img src="Assets/BioBridgeMedicalCenter.png" alt="BioBridge Logo" class="h-14 w-auto" />
        <span class="font-bold text-2xl text-sky-700">BioBridge</span>
      </div>
      <div class="hidden md:flex space-x-10">
        <a href="#home" class="text-gray-700 hover:text-sky-600 transition text-lg font-medium">Home</a>
        <a href="#services" class="text-gray-700 hover:text-sky-600 transition text-lg font-medium">Services</a>
        <a href="#about" class="text-gray-700 hover:text-sky-600 transition text-lg font-medium">About</a>
      </div>
      <button onclick="showSection('patient-notice')" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg transition text-lg font-medium shadow-lg">
        Book Appointment
      </button>
    </div>
  </div>
</nav>

<!-- Home Section -->
<section id="home" class="min-h-screen flex items-center pt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
      
      <!-- Text Content -->
      <div class="space-y-6 order-2 lg:order-1">
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
          Your Health, <span class="text-sky-600">Our Priority</span>
        </h1>
        <p class="text-lg sm:text-xl text-gray-600">
          Experience world-class healthcare through advanced technology, skilled specialists, and compassionate patient-centered care.
        </p>
        
        <!-- WHO News Search Form -->
        <form id="who-search-form" class="flex flex-col sm:flex-row gap-4 mt-4" onsubmit="searchWHO(event)">
          <input type="text" id="who-topic" placeholder="Enter disease or topic" 
                 class="p-3 rounded-lg border border-gray-300 w-full sm:w-auto flex-1" />
          <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg text-lg transition shadow-lg">
            Search WHO News
          </button>
        </form>

      </div>

      <!-- Image -->
      <div class="order-1 lg:order-2">
        <img src="Assets/BioBridge_Medical_Center_Info_TheDoctors.png" alt="Medical Center" 
             class="w-full h-auto rounded-2xl shadow-2xl object-cover" />
      </div>

    </div>
  </div>
</section>

<!-- Services Section -->
<section id="services" class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-4xl font-bold mb-4">Our Services</h2>
      <p class="text-xl text-gray-600">Comprehensive healthcare solutions tailored to your needs</p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="service-card bg-white p-6 rounded-xl shadow-lg">
        <div class="text-sky-600 text-5xl mb-4">🩺</div>
        <h3 class="text-xl font-bold mb-3">General Consultation</h3>
        <p class="text-gray-600">Comprehensive medical check-ups and personalized treatment plans for your wellbeing.</p>
      </div>
      
      <div class="service-card bg-white p-6 rounded-xl shadow-lg">
        <div class="text-sky-600 text-5xl mb-4">🔬</div>
        <h3 class="text-xl font-bold mb-3">Laboratory & Diagnostics</h3>
        <p class="text-gray-600">Fast and accurate test results with state-of-the-art diagnostic equipment.</p>
      </div>
      
      <div class="service-card bg-white p-6 rounded-xl shadow-lg">
        <div class="text-sky-600 text-5xl mb-4">❤️</div>
        <h3 class="text-xl font-bold mb-3">Specialty Clinics</h3>
        <p class="text-gray-600">Cardiology, Pediatrics, OB-Gyne, Orthopedics, and more specialized care.</p>
      </div>
      
      <div class="service-card bg-white p-6 rounded-xl shadow-lg">
        <div class="text-sky-600 text-5xl mb-4">🚑</div>
        <h3 class="text-xl font-bold mb-3">Emergency Care</h3>
        <p class="text-gray-600">24/7 professional response for urgent health concerns and emergencies.</p>
      </div>
    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" class="py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-4xl font-bold mb-4">Why Choose BioBridge?</h2>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="text-center">
        <div class="bg-sky-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-3xl">✅</span>
        </div>
        <h3 class="font-bold text-lg mb-2">Certified Medical Professionals</h3>
        <p class="text-gray-600">Highly trained and experienced healthcare providers</p>
      </div>
      
      <div class="text-center">
        <div class="bg-sky-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-3xl">💙</span>
        </div>
        <h3 class="font-bold text-lg mb-2">Patient-Centered Service</h3>
        <p class="text-gray-600">Your comfort and care are our top priorities</p>
      </div>
      
      <div class="text-center">
        <div class="bg-sky-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-3xl">🏥</span>
        </div>
        <h3 class="font-bold text-lg mb-2">State-of-the-Art Facilities</h3>
        <p class="text-gray-600">Modern equipment and comfortable environment</p>
      </div>
      
      <div class="text-center">
        <div class="bg-sky-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-3xl">📱</span>
        </div>
        <h3 class="font-bold text-lg mb-2">Integrated Digital Records</h3>
        <p class="text-gray-600">Seamless access to your health information</p>
      </div>
    </div>
  </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-20 bg-sky-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-4xl font-bold mb-4">Visit Us</h2>
      <p class="text-sky-100 text-lg">Get in touch with us for appointments and inquiries</p>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="text-center bg-sky-600 bg-opacity-50 p-6 rounded-xl hover:bg-opacity-70 transition">
        <span class="text-5xl mb-3 block">📍</span>
        <p class="font-bold text-lg mb-2">Address</p>
        <p class="text-sky-100">BioBridge Medical Center, Cebu City, Philippines</p>
      </div>
      
      <div class="text-center bg-sky-600 bg-opacity-50 p-6 rounded-xl hover:bg-opacity-70 transition">
        <span class="text-5xl mb-3 block">📞</span>
        <p class="font-bold text-lg mb-2">Contact</p>
        <p class="text-sky-100">(032) 123-4567</p>
      </div>
      
      <div class="text-center bg-sky-600 bg-opacity-50 p-6 rounded-xl hover:bg-opacity-70 transition">
        <span class="text-5xl mb-3 block">✉️</span>
        <p class="font-bold text-lg mb-2">Email</p>
        <p class="text-sky-100">biobridgecare@gmail.com</p>
      </div>
      
      <div class="text-center bg-sky-600 bg-opacity-50 p-6 rounded-xl hover:bg-opacity-70 transition">
        <span class="text-5xl mb-3 block">🕒</span>
        <p class="font-bold text-lg mb-2">Open Hours</p>
        <p class="text-sky-100">Monday – Saturday<br>8:00 AM – 6:00 PM</p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <p>&copy; 2025 BioBridge Medical Center. All rights reserved.</p>
  </div>
</footer>

<!-- Patient Notice Modal -->
<div id="patient-notice" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-2xl p-10 w-full max-w-lg mx-4 shadow-2xl">
    <h1 class="text-3xl font-bold text-sky-700 mb-4">Before we continue...</h1>
    <p class="text-gray-600 mb-6">Please tell us if you're an <span class="font-semibold">existing patient</span> or a <span class="font-semibold">new patient</span>.</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <button onclick="goToAuth('login')" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg transition shadow-md w-full sm:w-auto">
        I'm an Existing Patient
      </button>
      <a href="Public/patient_register_link.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-lg transition shadow-md w-full sm:w-auto text-center block">
        I'm a New Patient
      </a>
    </div>
    <button onclick="hideSection('patient-notice')" class="text-sm text-gray-500 hover:underline mt-6 block mx-auto">
      ← Back to Home
    </button>
  </div>
</div>

<!-- Auth Section -->
<div id="auth" class="fixed inset-0 bg-white z-50 hidden overflow-y-auto">
  <div class="min-h-screen flex">
    <div class="w-full md:w-1/3 flex items-center justify-center p-8">
      <div class="w-full max-w-md space-y-6">
        <div class="flex justify-center">
          <img src="Assets/BioBridgeMedicalCenter.png" alt="Logo" class="w-40 h-auto mb-4" />
        </div>
        <div class="shadow-2xl rounded-xl bg-white p-8 w-full max-w-md mx-auto">
          <div id="login-form">
            <form method="POST" action="Public/login_register.php" class="space-y-4" autocomplete="off">
              <h2 class="text-2xl font-bold text-center">Sign in to your account</h2>
              <input type="text" name="username" placeholder="Username" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded" />
              <div class="relative">
                <input id="login-password" type="password" name="password" placeholder="Password" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded pr-10" />
                <button type="button" onclick="togglePassword('login-password', this)" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">👁️</button>
              </div>
              <button type="submit" name="login" class="w-full bg-sky-600 hover:bg-sky-700 text-white p-2 rounded">Login</button>
              <p class="text-center text-sm mt-2">
                Already a patient but don't have an online account yet?<br>
                <a href="#" onclick="showForm('register')" class="text-sky-600 hover:underline font-medium">Link your record now</a>
              </p>
            </form>
          </div>

          <div id="register-form" class="hidden">
            <form method="POST" class="space-y-4">
              <input type="hidden" name="register" value="1">
              <h2 class="text-2xl font-bold text-center">Create Your Account</h2>

              <label>Email</label>
              <input type="email" name="username" placeholder="Enter your registered email" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded" value="<?= htmlspecialchars($userEmail) ?>">

              <label>Username</label>
              <input type="text" name="user_display" placeholder="Choose a username" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded" value="<?= htmlspecialchars($userDisplay) ?>">

              <label>Password</label>
              <input type="password" name="password" placeholder="Password" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded">

              <label>Confirm Password</label>
              <input type="password" name="confirm" placeholder="Confirm Password" required class="w-full p-2 bg-gray-100 border border-gray-300 rounded">

              <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white p-2 rounded">Create Account</button>
            </form>
            <button onclick="showForm('login')" class="text-sm text-gray-500 hover:underline block text-center mt-6">← Back</button>
          </div>

        </div>
        <button onclick="hideSection('auth')" class="text-sm text-gray-500 hover:underline block text-center">
          ← Back to Home
        </button>
      </div>
    </div>

    <div class="hidden md:block md:w-2/3">
      <img src="Assets/BioBridge_Medical_Center_Info.png" alt="Clinic Info" class="w-full h-full object-cover" />
    </div>
  </div>
</div>

<script>
function showSection(id) { document.getElementById(id).classList.remove('hidden'); }
function hideSection(id) { document.getElementById(id).classList.add('hidden'); }
function goToAuth(form = 'login') { showSection('auth'); showForm(form); }
function showForm(formName) {
  document.getElementById('login-form').classList.toggle('hidden', formName !== 'login');
  document.getElementById('register-form').classList.toggle('hidden', formName !== 'register');
}
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  input.type = input.type === 'password' ? 'text' : 'password';
  btn.textContent = btn.textContent === '👁️' ? '🙈' : '👁️';
}

// WHO Search
function searchWHO(e) {
  e.preventDefault();
  const topic = document.getElementById('who-topic').value.trim();
  if(topic) { window.open(`https://www.who.int/search?q=${encodeURIComponent(topic)}`, '_blank'); }
  else { alert('Please enter a disease or topic to search.'); }
}

// Show modal if PHP sets a message
<?php if ($showRegister && $modalMessage): ?>
showSection('auth');
showForm('register');
alert("<?= addslashes($modalMessage) ?>");
<?php endif; ?>
</script>

</body>
</html>
