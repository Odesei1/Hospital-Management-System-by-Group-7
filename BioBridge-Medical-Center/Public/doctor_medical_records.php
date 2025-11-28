<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/medical_record.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// 🔐 Role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

$medical_records = new MedicalRecords();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['ajax_action'] ?? null;

    if ($action === "create") {

        $data = [
            "med_rec_diagnosis" => $_POST["diagnosis"],
            "med_rec_prescription" => $_POST["prescription"],
            "med_rec_visit_date" => $_POST["visit_date"],
            "appt_id" => $_POST["appt_id"]
        ];

        $success = $medical_records->addRecord($data);

        echo json_encode(["success" => $success]);
        exit();
    }

    if ($action === "update") {

        $id = $_POST["id"];

        $data = [
            "med_rec_diagnosis" => $_POST["diagnosis"],
            "med_rec_prescription" => $_POST["prescription"],
            "med_rec_visit_date" => $_POST["visit_date"],
            "appt_id" => $_POST["appt_id"]
        ];

        $success = $medical_records->updateRecord($id, $data);

        echo json_encode(["success" => $success]);
        exit();
    }

    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit();
}

$perPage = 10;
$page = isset($_GET["page"]) && is_numeric($_GET["page"]) ? (int)$_GET["page"] : 1;
$start = ($page - 1) * $perPage;

$allRecords = $medical_records->getAllRecords();
$totalRecords = count($allRecords);
$records = array_slice($allRecords, $start, $perPage);
$totalPages = ceil($totalRecords / $perPage);

?>

<?php include "../Includes/header.html"; ?> 
<?php include "../Includes/navbar_ doctor_medical_records.html"; ?> 
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">🧠 Medical Records</h1>
  <p class="text-center text-gray-600 mb-10">
    Manage all patient medical records linked to appointments.
  </p>

  <div class="flex justify-end mb-4">
    <button onclick="openCreateModal()" class="bg-sky-700 text-white px-4 py-2 rounded-lg hover:bg-sky-800 transition">
      ➕ Create New Record
    </button>
  </div>

  <div class="bg-white shadow-md rounded-2xl p-6 overflow-x-auto">
    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">Record ID</th>
          <th class="p-2 border text-left">Diagnosis</th>
          <th class="p-2 border text-left">Prescription</th>
          <th class="p-2 border text-center">Visit Date</th>
          <th class="p-2 border text-center">Patient</th>
          <th class="p-2 border text-center">Doctor</th>
          <th class="p-2 border text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($records): ?>
          <?php foreach ($records as $rec): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center"><?= htmlspecialchars($rec["med_rec_id"]) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($rec["med_rec_diagnosis"]) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($rec["med_rec_prescription"]) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars($rec["med_rec_visit_date"]) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars(($rec["pat_first_name"] ?? "") . " " . ($rec["pat_last_name"] ?? "")) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars(($rec["doc_first_name"] ?? "") . " " . ($rec["doc_last_name"] ?? "")) ?></td>
              <td class="p-2 border text-center">
                <button onclick='viewRecord(<?= json_encode($rec) ?>)' class="text-blue-600 hover:underline mr-2">👁 View</button>
                <button onclick='editRecord(<?= json_encode($rec) ?>)' class="text-green-600 hover:underline mr-2">✏️ Edit</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center p-4 text-gray-500">No medical records found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
      <div class="flex justify-center mt-4 space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>"
             class="px-3 py-1 rounded border <?= $i == $page ? 'bg-sky-700 text-white' : 'bg-white text-sky-700 hover:bg-sky-100' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include "../Includes/footer.html"; ?>

<!-- Modal -->
<div id="recordModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-lg transform scale-95 opacity-0 transition-all duration-300" id="modalBox">

    <h2 id="modalTitle" class="text-2xl font-bold text-sky-700 mb-4 text-center"></h2>

    <form id="recordForm" class="space-y-4">

      <input type="hidden" name="ajax_action" id="ajax_action">
      <input type="hidden" name="id" id="record_id">

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Diagnosis</label>
        <textarea name="diagnosis" id="diagnosis" class="w-full border rounded-lg px-4 py-2" required></textarea>
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Prescription</label>
        <textarea name="prescription" id="prescription" class="w-full border rounded-lg px-4 py-2" required></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Visit Date</label>
          <input type="date" name="visit_date" id="visit_date" class="w-full border rounded-lg px-4 py-2" required>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">Appointment ID</label>
          <input type="text" name="appt_id" id="appt_id" class="w-full border rounded-lg px-4 py-2">
        </div>
      </div>

      <!-- Buttons container -->
      <div id="modalButtons" class="flex justify-end gap-4 mt-6">
        <button type="button" id="btnGoBack" onclick="closeModal()" class="bg-gray-300 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-400">Go Back</button>
        <button type="submit" id="btnSave" class="bg-sky-700 text-white px-5 py-2 rounded-lg hover:bg-sky-800">Save</button>
      </div>

    </form>
  </div>
</div>

<script>
function openCreateModal() {
  openModal("Create Medical Record", "create");
  document.getElementById("recordForm").reset();
  disableForm(false);
  toggleButtons("create"); // Show Save + hide Go Back
}

function viewRecord(record) {
  openModal("View Medical Record");
  fillForm(record);
  disableForm(true);
  toggleButtons("view"); // Show Go Back only
}

function editRecord(record) {
  openModal("Update Medical Record", "update");
  fillForm(record);
  disableForm(false);
  toggleButtons("edit"); // Show Save + Go Back
}

function fillForm(record) {
  document.getElementById("record_id").value = record.med_rec_id || "";
  document.getElementById("diagnosis").value = record.med_rec_diagnosis || "";
  document.getElementById("prescription").value = record.med_rec_prescription || "";
  document.getElementById("visit_date").value = record.med_rec_visit_date || "";
  document.getElementById("appt_id").value = record.appt_id || "";
}

function disableForm(state) {
  document.querySelectorAll("#recordForm input, #recordForm textarea").forEach(i => i.disabled = state);
}

// Toggle buttons visibility based on modal type
function toggleButtons(type) {
  const btnSave = document.getElementById("btnSave");
  const btnGoBack = document.getElementById("btnGoBack");

  if(type === "view") {
    btnSave.style.display = "none";
    btnGoBack.style.display = "inline-flex";
  } else if(type === "edit") {
    btnSave.style.display = "inline-flex";
    btnGoBack.style.display = "inline-flex";
  } else { // create
    btnSave.style.display = "inline-flex";
    btnGoBack.style.display = "none";
  }
}

function openModal(title, action = null) {
  document.getElementById("modalTitle").innerText = title;
  document.getElementById("ajax_action").value = action;

  const modal = document.getElementById("recordModal");
  const box = document.getElementById("modalBox");

  modal.classList.remove("hidden");
  modal.classList.add("flex");

  setTimeout(() => box.classList.remove("opacity-0", "scale-95"), 50);
}

function closeModal() {
  const modal = document.getElementById("recordModal");
  const box = document.getElementById("modalBox");

  box.classList.add("opacity-0", "scale-95");
  setTimeout(() => { modal.classList.add("hidden"); modal.classList.remove("flex"); }, 200);
}

document.getElementById("recordForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  const res = await fetch("", { method: "POST", body: formData });
  const data = await res.json();

  if (data.success) {
    alert("✅ Record saved successfully!");
    location.reload();
  } else {
    alert("❌ Error saving record.");
  }
});
</script>

</body>
</html>
