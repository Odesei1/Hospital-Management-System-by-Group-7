<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/specialization.php";

$database = new Database();
$conn = $database->connect();

$doctor_id = $_SESSION['doc_id'] ?? null;

$doctor = null;
$active_schedule = null;

// 🩺 Fetch doctor details with specialization name
if ($doctor_id) {
    $stmt = $conn->prepare("
        SELECT d.doc_id, d.doc_first_name, d.doc_last_name, d.doc_email, d.doc_contact_num,
               d.doc_created_at, d.doc_updated_at, s.spec_name
        FROM doctor d
        LEFT JOIN specialization s ON d.spec_id = s.spec_id
        WHERE d.doc_id = :id
    ");
    $stmt->execute([':id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch active schedule from schedule table
    $stmtSched = $conn->prepare("
        SELECT sched_id, sched_days, sched_start_time, sched_end_time 
        FROM schedule 
        WHERE doc_id = :id AND sched_days LIKE '%(ACTIVE)%'
        ORDER BY sched_created_at DESC LIMIT 1
    ");
    $stmtSched->execute([':id' => $doctor_id]);
    $active_schedule = $stmtSched->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_dashboard.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow p-8 max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">👨‍⚕️ Doctor Profile</h1>

  <?php if ($doctor): ?>
    <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">
      <div class="flex flex-col items-center text-center">
        <div class="w-28 h-28 bg-sky-100 text-sky-700 flex items-center justify-center rounded-full text-5xl font-bold mb-4 shadow-inner">
          <?= strtoupper(substr($doctor['doc_first_name'], 0, 1)) . strtoupper(substr($doctor['doc_last_name'], 0, 1)) ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1">
          Dr. <?= htmlspecialchars($doctor['doc_first_name'] . ' ' . $doctor['doc_last_name']) ?>
        </h2>
        <p class="text-gray-600 italic mb-4"><?= htmlspecialchars($doctor['spec_name'] ?? 'No specialization assigned') ?></p>
      </div>

      <div class="border-t border-gray-300 my-6"></div>

      <div class="grid sm:grid-cols-2 gap-6 text-gray-700">
        <div>
          <p class="font-semibold text-gray-600">📧 Email</p>
          <p><?= htmlspecialchars($doctor['doc_email']) ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">📞 Contact Number</p>
          <p><?= htmlspecialchars($doctor['doc_contact_num'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🩺 Specialization</p>
          <p><?= htmlspecialchars($doctor['spec_name'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🕒 Joined</p>
          <p><?= date("F j, Y", strtotime($doctor['doc_created_at'])) ?></p>
        </div>
      </div>

      <div class="mt-10">
        <h3 class="text-xl font-semibold text-sky-700 mb-4">📅 Active Schedule</h3>
        <?php if ($active_schedule): ?>
          <table class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-sky-700 text-white">
              <tr>
                <th class="p-3 border text-left">Days</th>
                <th class="p-3 border text-left">Start Time</th>
                <th class="p-3 border text-left">End Time</th>
              </tr>
            </thead>
            <tbody>
              <tr class="bg-green-50">
                <td class="p-3 border font-semibold text-sky-700">
                  <?= htmlspecialchars(str_replace('(ACTIVE)', '', $active_schedule['sched_days'])) ?>
                </td>
                <td class="p-3 border"><?= date("g:i A", strtotime($active_schedule['sched_start_time'])) ?></td>
                <td class="p-3 border"><?= date("g:i A", strtotime($active_schedule['sched_end_time'])) ?></td>
              </tr>
            </tbody>
          </table>
          <p class="text-xs text-green-700 italic mt-2">✔ Currently Active Schedule</p>
        <?php else: ?>
          <p class="text-gray-600 italic">No active schedule set. Please go to your <a href="doctor_schedule.php" class="text-sky-700 hover:underline">Schedule Management</a> page to set one.</p>
        <?php endif; ?>
      </div>

      <div class="flex justify-center mt-8">
        <a href="doctor_schedule.php" class="bg-sky-700 text-white px-6 py-2 rounded-lg hover:bg-sky-800 transition">
          ⚙ Manage All Schedules
        </a>
      </div>
    </div>

    <div class="mt-10 text-center text-gray-600">
      <p class="italic">“At <span class='text-sky-700 font-semibold'>BioBridge Medical Center</span>, we connect care, compassion, and innovation — one patient at a time.” 💙</p>
    </div>
  <?php endif; ?>
</main>

<?php include "../Includes/footer.html"; ?>
</body>
</html>
