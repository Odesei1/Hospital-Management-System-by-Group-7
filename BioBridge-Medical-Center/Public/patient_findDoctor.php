<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";

$database = new Database();
$conn = $database->connect();

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = $_GET['search'] ?? '';

// Count total doctors
$countSql = "SELECT COUNT(*) FROM doctor d 
             LEFT JOIN specialization s ON d.spec_id = s.spec_id
             WHERE 1";
$params = [];
if ($search) {
    $countSql .= " AND (d.doc_first_name LIKE :search OR d.doc_last_name LIKE :search)";
    $params[':search'] = "%$search%";
}
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch paginated doctors
$sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name,
               d.doc_contact_num, d.doc_email, s.spec_name
        FROM doctor d
        LEFT JOIN specialization s ON d.spec_id = s.spec_id
        WHERE 1";
if ($search) {
    $sql .= " AND (d.doc_first_name LIKE :search OR d.doc_last_name LIKE :search)";
}
$sql .= " ORDER BY d.doc_last_name ASC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default schedule text
$default_schedule_text = "Mon-Fri 9:00 AM - 5:00 PM";
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_dashboard.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow p-6 max-w-6xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Find a Doctor</h1>

  <!-- Search + Refresh -->
  <form method="GET" class="flex justify-center gap-3 mb-6">
      <input type="text" name="search" placeholder="Search by first or last name..."
             value="<?= htmlspecialchars($search) ?>"
             class="border px-4 py-2 rounded-lg w-64 focus:ring-2 focus:ring-sky-500 outline-none">
      
      <button type="submit" class="bg-sky-700 text-white px-5 py-2 rounded-lg hover:bg-sky-800">
          Search
      </button>

      <a href="patient_findDoctor.php" class="bg-gray-300 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-400 transition">
         Refresh
      </a>
  </form>

  <!-- Doctor List -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($doctors as $doc): ?>
          <?php
              $activeFile = __DIR__ . "/DoctorSchedule_Files/active_schedule_{$doc['doc_id']}.txt";
              $sched_text = $default_schedule_text;
              if (file_exists($activeFile)) {
                  $sched_id = file_get_contents($activeFile);
                  $stmtSched = $conn->prepare("SELECT * FROM schedule WHERE sched_id = :id LIMIT 1");
                  $stmtSched->execute([':id' => $sched_id]);
                  $sched = $stmtSched->fetch(PDO::FETCH_ASSOC);
                  if ($sched) {
                      $sched_text = htmlspecialchars($sched['sched_days']) . " " .
                                    date("g:i A", strtotime($sched['sched_start_time'])) . " - " .
                                    date("g:i A", strtotime($sched['sched_end_time']));
                  }
              }
          ?>
          <div class="bg-white p-5 rounded-2xl shadow hover:shadow-xl hover:-translate-y-1 transition-transform duration-200">
              <h2 class="text-lg font-semibold text-sky-700">
                  Dr. <?= htmlspecialchars($doc['doc_first_name'] . ' ' . ($doc['doc_middle_init'] ? $doc['doc_middle_init'] . '. ' : '') . $doc['doc_last_name']) ?>
              </h2>
              <p class="text-gray-600"><?= htmlspecialchars($doc['spec_name'] ?? 'General Practitioner') ?></p>
              <p class="text-sm text-green-700 mt-1"><?= $sched_text ?></p>
              <p class="text-sm text-gray-500 mt-2">📞 <?= htmlspecialchars($doc['doc_contact_num']) ?></p>
              <p class="text-sm text-gray-500">✉️ <?= htmlspecialchars($doc['doc_email']) ?></p>

              <a href="patient_appointments.php?doc_id=<?= $doc['doc_id'] ?>" 
                 class="mt-4 inline-block bg-sky-700 text-white px-4 py-2 rounded-lg hover:bg-sky-800 transition">
                 Book Appointment
             </a>
          </div>
      <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="flex justify-center mt-6 gap-2">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
             class="px-3 py-1 border rounded-lg <?= $i == $page ? 'bg-sky-700 text-white' : 'hover:bg-gray-100 text-sky-700' ?>">
             <?= $i ?>
          </a>
      <?php endfor; ?>
  </div>
  <?php endif; ?>

</main>

<?php include "../Includes/footer.html"; ?>
