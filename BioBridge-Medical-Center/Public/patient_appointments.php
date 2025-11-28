<?php
session_start();

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/appointment.php";
require_once __DIR__ . "/../Class/status.php";
require_once __DIR__ . "/../Class/service.php";

// Only allow patients
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}

$pat_id = $_SESSION['pat_id'];
$selected_doc_id = $_GET['doc_id'] ?? null;

$database = new Database();
$conn = $database->connect();

$appointment = new Appointment($conn);
$status = new Status($conn);
$serviceObj = new Service();

$errorMsg = '';
$successMsg = '';

// Fetch doctors + services + statuses
$doctors = $conn->query("SELECT d.doc_id, d.doc_first_name, d.doc_last_name, sp.spec_name 
                         FROM doctor d 
                         LEFT JOIN specialization sp ON d.spec_id = sp.spec_id 
                         ORDER BY d.doc_last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$allServices = $serviceObj->all();
$statuses = $status->all();

// Optional: get the specialization of the selected doctor
$selected_spec = null;
if($selected_doc_id){
    foreach($doctors as $d){
        if($d['doc_id'] == $selected_doc_id){
            $selected_spec = $d['spec_name'] ?? 'General Practitioner';
            break;
        }
    }
}

// Define mapping of services by specialization (adjust IDs as needed)
$servicesBySpec = [
    "Family Medicine" => [1,7,10],
    "Pediatrics" => [7,10,17],
    "Internal Medicine" => [1,3,6],
    "Cardiology" => [6,14,15],
    "Dermatology" => [12,18],
    "Obstetrics and Gynecology" => [16],
    "Ophthalmology" => [8],
    "Orthopedics" => [9,20],
    "Otolaryngology (ENT)" => [13],
    "Psychiatry" => [19],
    "Neurology" => [14],
    "Urology" => [11],
    "Dentistry" => [2],
    "General Surgery" => [11],
    "Emergency Medicine" => [11,14,15]
];

$cancel_id = null;
foreach ($statuses as $st) {
    if (strtolower($st['stat_name']) === 'cancelled') {
        $cancel_id = $st['stat_id'];
        break;
    }
}

// Handle booking an appointment
if (isset($_POST['add'])) {
    try {
        $appt_id = $appointment->create(
            $pat_id,
            $_POST['doc_id'],
            $_POST['serv_id'],
            $_POST['appt_date'],
            $_POST['appt_time']
        );
        $successMsg = "Appointment booked successfully! ID: " . $appt_id;
    } catch (Exception $e) {
        $errorMsg = "Failed to create appointment: " . $e->getMessage();
    }
}

// Handle status update
if (isset($_POST['update_status'], $_POST['appt_id'], $_POST['status'])) {
    try {
        $appointment->updateStatus($_POST['appt_id'], $_POST['status']);
        $successMsg = "Appointment status updated successfully!";
    } catch (Exception $e) {
        $errorMsg = "Failed to update status: " . $e->getMessage();
    }
}

// Fetch all patient appointments
$allAppointments = $appointment->findByPatient($pat_id);

// PAGINATION — 5 per page
$perPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAppts = count($allAppointments);
$totalPages = ceil($totalAppts / $perPage);
$start = ($page - 1) * $perPage;
$appointments = array_slice($allAppointments, $start, $perPage);

?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_appointments.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">My Appointments</h1>

  <!-- Messages -->
  <?php if ($successMsg): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $successMsg ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $errorMsg ?></div>
  <?php endif; ?>

  <!-- Book Appointment -->
  <div class="bg-white shadow-md rounded-lg p-6 mb-8">
    <a href="patient_findDoctor.php" class="inline-block mb-4 bg-gray-200 text-sky-700 px-4 py-2 rounded hover:bg-gray-300">
      Find a Doctor
    </a>
    <h2 class="text-2xl font-semibold mb-4">Book New Appointment</h2>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="date" name="appt_date" required class="border p-2 rounded">
      <input type="time" name="appt_time" required class="border p-2 rounded">

      <select name="doc_id" id="doctorSelect" required class="border p-2 rounded">
        <option value="">Select Doctor</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['doc_id'] ?>" data-spec="<?= htmlspecialchars($doc['spec_name'] ?? 'General Practitioner') ?>"
            <?= $selected_doc_id == $doc['doc_id'] ? 'selected' : '' ?>>
            Dr. <?= htmlspecialchars($doc['doc_first_name'] . " " . $doc['doc_last_name']) ?>
            <?= $doc['spec_name'] ? " - " . htmlspecialchars($doc['spec_name']) : "" ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="serv_id" id="serviceSelect" required class="border p-2 rounded">
        <option value="">Select a doctor first</option>
      </select>

      <button type="submit" name="add" class="bg-sky-700 hover:bg-sky-800 text-white py-2 rounded col-span-2">
        Book Appointment
      </button>
    </form>
  </div>

  <!-- Appointment List -->
  <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
    <h2 class="text-2xl font-semibold mb-4">Appointments List</h2>

    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">Appointment ID</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($appointments): ?>
          <?php foreach ($appointments as $appt): ?>
            <tr class="hover:bg-gray-100 cursor-pointer" onclick='openViewModal(<?= json_encode($appt) ?>)'>
              <td class="p-3 border text-center text-sky-700 font-semibold hover:underline">
                <?= htmlspecialchars($appt['appt_id']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td class="text-center p-4 text-gray-500">No appointments found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <div class="flex justify-center items-center mt-6 space-x-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">Previous</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i == $page ? 'bg-sky-700 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
           <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">Next</a>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include "../Includes/footer.html"; ?>

<!-- APPOINTMENT DETAILS MODAL -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[420px]">
    <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Appointment Details</h3>

    <div class="space-y-2 text-sm">
      <p><strong>ID:</strong> <span id="view_appt_id"></span></p>
      <p><strong>Doctor:</strong> <span id="view_doctor"></span></p>
      <p><strong>Service:</strong> <span id="view_service"></span></p>
      <p><strong>Date:</strong> <span id="view_date"></span></p>
      <p><strong>Time:</strong> <span id="view_time"></span></p>
      <p><strong>Status:</strong> <span id="view_status"></span></p>
    </div>

    <form id="updateStatusForm" method="POST" class="mt-4">
      <input type="hidden" name="appt_id" id="modal_appt_id">
      <input type="hidden" name="update_status" value="1">

      <label class="block mb-1 font-semibold text-sm">Change Status</label>
      <select name="status" id="modal_status" class="w-full border rounded p-2 mb-4">
        <option value="">-- Select Status --</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?= $st['stat_id'] ?>"><?= htmlspecialchars($st['stat_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="flex justify-between mt-6">
        <button type="button" onclick="cancelAppointment()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          Cancel Appointment
        </button>

        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
          Save Changes
        </button>

        <button type="button" onclick="closeViewModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
          Close
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const cancelId = <?= json_encode($cancel_id) ?>;
const allServices = <?= json_encode($allServices) ?>;
const servicesBySpec = <?= json_encode($servicesBySpec) ?>;

const doctorSelect = document.getElementById('doctorSelect');
const serviceSelect = document.getElementById('serviceSelect');

// Auto-filter services based on selected doctor
function updateServices() {
    const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
    const specName = selectedOption.getAttribute('data-spec');

    serviceSelect.innerHTML = '<option value="">Select Service</option>';

    if(specName && servicesBySpec[specName]){
        servicesBySpec[specName].forEach((id, index) => {
            const service = allServices.find(s => s.serv_id == id);
            if(service){
                const option = document.createElement('option');
                option.value = service.serv_id;
                option.text = service.serv_name;
                serviceSelect.appendChild(option);

                if(index === 0) option.selected = true; // auto-select first service
            }
        });
    }
}

// Update on page load & when doctor changes
doctorSelect.addEventListener('change', updateServices);
updateServices();

// --- MODAL & CANCEL FUNCTIONS ---
function openViewModal(appt) {
  const modal = document.getElementById('viewModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');

  document.getElementById('view_appt_id').textContent = appt.appt_id;
  document.getElementById('view_doctor').textContent = "Dr. " + appt.doc_first_name + " " + appt.doc_last_name
      + (appt.spec_name ? " (" + appt.spec_name + ")" : "");
  document.getElementById('view_service').textContent = appt.serv_name;
  document.getElementById('view_date').textContent = appt.appt_date;
  document.getElementById('view_time').textContent =
      new Date("1970-01-01T" + appt.appt_time).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
  document.getElementById('view_status').textContent = appt.stat_name;

  document.getElementById('modal_appt_id').value = appt.appt_id;
  document.getElementById('modal_status').value = appt.stat_id;

  const locked = ["cancelled", "completed"];
  const statusName = appt.stat_name.toLowerCase();

  const statusSelect = document.getElementById('modal_status');
  const saveBtn = document.querySelector('#updateStatusForm button[type="submit"]');
  const cancelBtn = document.querySelector('#updateStatusForm button[onclick="cancelAppointment()"]');

  if (locked.includes(statusName)) {
    statusSelect.disabled = true;
    saveBtn.disabled = true;
    cancelBtn.disabled = true;
    saveBtn.classList.add("bg-gray-300", "cursor-not-allowed");
    cancelBtn.classList.add("bg-gray-300", "cursor-not-allowed");
    saveBtn.classList.remove("bg-green-600", "hover:bg-green-700");
    cancelBtn.classList.remove("bg-red-600", "hover:bg-red-700");
  } else {
    statusSelect.disabled = false;
    saveBtn.disabled = false;
    cancelBtn.disabled = false;
    saveBtn.classList.remove("bg-gray-300", "cursor-not-allowed");
    cancelBtn.classList.remove("bg-gray-300", "cursor-not-allowed");
    saveBtn.classList.add("bg-green-600", "hover:bg-green-700");
    cancelBtn.classList.add("bg-red-600", "hover:bg-red-700");
  }
}

function closeViewModal() {
  document.getElementById('viewModal').classList.add('hidden');
  document.getElementById('viewModal').classList.remove('flex');
}

function cancelAppointment() {
  if (!cancelId) {
    alert("Cancelled status is not defined!");
    return;
  }
  if (confirm("Are you sure you want to cancel this appointment?")) {
    document.getElementById('modal_status').value = cancelId;
    document.getElementById('updateStatusForm').submit();
  }
}
</script>
