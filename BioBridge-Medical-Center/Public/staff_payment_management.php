<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/payment.php";
require_once __DIR__ . "/../Class/payment_method.php";
require_once __DIR__ . "/../Class/payment_status.php";

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Only staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

$payment = new Payment();
$method = new PaymentMethod();
$status = new PaymentStatus();

// ===== Pagination =====
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Payments
$allPayments = $payment->getAllPayments();
$totalPayments = count($allPayments);
$totalPages = ceil($totalPayments / $limit);
$payments = array_slice($allPayments, $offset, $limit);

// Payment Methods
$allMethods = $method->getAllPaymentMethods()->fetchAll(PDO::FETCH_ASSOC);
$totalMethods = count($allMethods);
$totalPagesMethods = ceil($totalMethods / $limit);
$methods = array_slice($allMethods, $offset, $limit);

// Payment Status
$allStatuses = $status->getAllPaymentStatus()->fetchAll(PDO::FETCH_ASSOC);
$totalStatuses = count($allStatuses);
$totalPagesStatuses = ceil($totalStatuses / $limit);
$statuses = array_slice($allStatuses, $offset, $limit);

$activeTab = $_GET['tab'] ?? $_POST['tab'] ?? 'payments';

// ===== Handle form submissions =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add/Update Payment
    if (isset($_POST['add_payment']) || isset($_POST['update_payment'])) {
        $data = [
            'pymt_amount_paid' => $_POST['pymt_amount_paid'],
            'pymt_meth_id' => $_POST['pymt_meth_id'],
            'pymt_stat_id' => $_POST['pymt_stat_id'],
            'appt_id' => $_POST['appt_id']
        ];
        if (isset($_POST['add_payment'])) {
            $payment->addPaymentRecord($data);
            header("Location: staff_payment_management.php?tab=payments&added=1");
        } else {
            $payment->updatePayment($_POST['pymt_id'], $data);
            header("Location: staff_payment_management.php?tab=payments&updated=1");
        }
        exit;
    }

    // Add/Update Payment Method
    if (isset($_POST['add_method']) || isset($_POST['update_method'])) {
        $id = $_POST['pymt_meth_id'] ?? null;
        $data = ['pymt_meth_name' => $_POST['pymt_meth_name']];
        if (isset($_POST['add_method'])) {
            $method->addPaymentMethod($data);
            header("Location: staff_payment_management.php?tab=methods&added=1");
        } else {
            $method->updatePaymentMethod($id, $data);
            header("Location: staff_payment_management.php?tab=methods&updated=1");
        }
        exit;
    }

    // Add/Update Payment Status
    if (isset($_POST['add_status']) || isset($_POST['update_status'])) {
        $id = $_POST['pymt_stat_id'] ?? null;
        $data = ['pymt_stat_name' => $_POST['pymt_stat_name']];
        if (isset($_POST['add_status'])) {
            $status->addPaymentStatus($data);
            header("Location: staff_payment_management.php?tab=statuses&added=1");
        } else {
            $status->updatePaymentStatus($id, $data);
            header("Location: staff_payment_management.php?tab=statuses&updated=1");
        }
        exit;
    }
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_dashboard.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6 flex flex-col items-center">
<h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">💰 Payment Management</h1>

<!-- Alerts -->
<?php if (isset($_GET['added'])): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center w-full max-w-3xl">✅ Record added successfully!</div>
<?php elseif (isset($_GET['updated'])): ?>
<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6 text-center w-full max-w-3xl">✏️ Record updated successfully!</div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex justify-center mb-8 space-x-4">
<button class="tab-btn <?= $activeTab === 'payments' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('payments')">Payments</button>
<button class="tab-btn <?= $activeTab === 'methods' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('methods')">Payment Methods</button>
<button class="tab-btn <?= $activeTab === 'statuses' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('statuses')">Payment Status</button>
</div>

<!-- =================== PAYMENTS TAB =================== -->
<section id="payments" class="<?= $activeTab === 'payments' ? '' : 'hidden' ?> w-full max-w-5xl">
<div class="bg-white p-6 rounded-2xl shadow mb-10 w-full">
<h2 class="text-2xl font-semibold text-sky-700 mb-3 text-center">📋 All Payment Records</h2>

<!-- Add Payment -->
<button onclick="openAddPaymentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4">➕ Add Payment</button>

<table class="w-full border-collapse border border-gray-300 text-sm text-center">
<thead class="bg-sky-700 text-white">
<tr>
<th>ID</th>
<th>Amount</th>
<th>Method</th>
<th>Status</th>
<th>Appointment</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($payments as $p): ?>
<tr class="hover:bg-gray-100">
<td class="p-2 border"><?= $p['pymt_id'] ?></td>
<td class="p-2 border"><?= htmlspecialchars($p['pymt_amount_paid']) ?></td>
<td class="p-2 border"><?= htmlspecialchars($p['pymt_meth_name']) ?></td>
<td class="p-2 border"><?= htmlspecialchars($p['pymt_stat_name']) ?></td>
<td class="p-2 border"><?= htmlspecialchars($p['appt_id']) ?></td>
<td class="p-2 border">
<button onclick='openPaymentModal(<?= json_encode($p) ?>)' class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">✏️ Edit</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Pagination -->
<div class="mt-4 flex justify-center space-x-2">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?tab=payments&page=<?= $i ?>" class="px-3 py-1 border rounded <?= $i==$page?'bg-sky-700 text-white':'bg-white' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
</div>
</section>

<!-- =================== METHODS TAB =================== -->
<section id="methods" class="<?= $activeTab === 'methods' ? '' : 'hidden' ?> w-full max-w-5xl">
<div class="bg-white p-6 rounded-2xl shadow mb-10 w-full">
<h2 class="text-2xl font-semibold text-sky-700 mb-3 text-center">📋 Payment Methods</h2>

<button onclick="openAddMethodModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4">➕ Add Method</button>

<table class="w-full border-collapse border border-gray-300 text-sm text-center">
<thead class="bg-sky-700 text-white"><tr><th>ID</th><th>Method</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($methods as $m): ?>
<tr class="hover:bg-gray-100">
<td class="p-2 border"><?= $m['pymt_meth_id'] ?></td>
<td class="p-2 border"><?= htmlspecialchars($m['pymt_meth_name']) ?></td>
<td class="p-2 border">
<button onclick='openMethodModal(<?= json_encode($m) ?>)' class="bg-green-600 px-2 py-1 text-white rounded text-xs">✏️ Edit</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="mt-4 flex justify-center space-x-2">
<?php for($i=1;$i<=$totalPagesMethods;$i++): ?>
<a href="?tab=methods&page=<?= $i ?>" class="px-3 py-1 border rounded <?= $i==$page?'bg-sky-700 text-white':'bg-white' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
</div>
</section>

<!-- =================== STATUS TAB =================== -->
<section id="statuses" class="<?= $activeTab === 'statuses' ? '' : 'hidden' ?> w-full max-w-5xl">
<div class="bg-white p-6 rounded-2xl shadow mb-10 w-full">
<h2 class="text-2xl font-semibold text-sky-700 mb-3 text-center">📋 Payment Status</h2>

<button onclick="openAddStatusModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-4">➕ Add Status</button>

<table class="w-full border-collapse border border-gray-300 text-sm text-center">
<thead class="bg-sky-700 text-white"><tr><th>ID</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($statuses as $s): ?>
<tr class="hover:bg-gray-100">
<td class="p-2 border"><?= $s['pymt_stat_id'] ?></td>
<td class="p-2 border"><?= htmlspecialchars($s['pymt_stat_name']) ?></td>
<td class="p-2 border">
<button onclick='openStatusModal(<?= json_encode($s) ?>)' class="bg-green-600 px-2 py-1 text-white rounded text-xs">✏️ Edit</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="mt-4 flex justify-center space-x-2">
<?php for($i=1;$i<=$totalPagesStatuses;$i++): ?>
<a href="?tab=statuses&page=<?= $i ?>" class="px-3 py-1 border rounded <?= $i==$page?'bg-sky-700 text-white':'bg-white' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
</div>
</section>

<!-- =================== MODALS =================== -->
<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">✏️ Update Payment</h2>
<form method="POST">
<input type="hidden" name="tab" value="payments">
<input type="hidden" id="modal_pymt_id" name="pymt_id">
<input type="number" id="modal_pymt_amount_paid" name="pymt_amount_paid" placeholder="Amount Paid" required class="border p-2 rounded w-full mb-2">
<input type="text" id="modal_appt_id" name="appt_id" placeholder="Appointment ID" required class="border p-2 rounded w-full mb-2">

<select id="modal_pymt_meth_id" name="pymt_meth_id" required class="border p-2 rounded w-full mb-2">
<option value="">Select Payment Method</option>
<?php foreach ($allMethods as $m): ?>
<option value="<?= $m['pymt_meth_id'] ?>"><?= htmlspecialchars($m['pymt_meth_name']) ?></option>
<?php endforeach; ?>
</select>

<select id="modal_pymt_stat_id" name="pymt_stat_id" required class="border p-2 rounded w-full mb-2">
<option value="">Select Payment Status</option>
<?php foreach ($allStatuses as $s): ?>
<option value="<?= $s['pymt_stat_id'] ?>"><?= htmlspecialchars($s['pymt_stat_name']) ?></option>
<?php endforeach; ?>
</select>

<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('paymentModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="update_payment" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Update</button>
</div>
</form>
</div>
</div>

<!-- Add Payment Modal -->
<div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">➕ Add Payment</h2>
<form method="POST">
<input type="hidden" name="tab" value="payments">
<input type="number" name="pymt_amount_paid" placeholder="Amount Paid" required class="border p-2 rounded w-full mb-2">
<input type="text" name="appt_id" placeholder="Appointment ID" required class="border p-2 rounded w-full mb-2">

<select name="pymt_meth_id" required class="border p-2 rounded w-full mb-2">
<option value="">Select Payment Method</option>
<?php foreach ($allMethods as $m): ?>
<option value="<?= $m['pymt_meth_id'] ?>"><?= htmlspecialchars($m['pymt_meth_name']) ?></option>
<?php endforeach; ?>
</select>

<select name="pymt_stat_id" required class="border p-2 rounded w-full mb-2">
<option value="">Select Payment Status</option>
<?php foreach ($allStatuses as $s): ?>
<option value="<?= $s['pymt_stat_id'] ?>"><?= htmlspecialchars($s['pymt_stat_name']) ?></option>
<?php endforeach; ?>
</select>

<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('addPaymentModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="add_payment" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Add</button>
</div>
</form>
</div>
</div>

<!-- Method Modal -->
<div id="methodModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">✏️ Update Method</h2>
<form method="POST">
<input type="hidden" name="tab" value="methods">
<input type="hidden" id="modal_pymt_meth_id" name="pymt_meth_id">
<input type="text" id="modal_pymt_meth_name" name="pymt_meth_name" placeholder="Method Name" required class="border p-2 rounded w-full mb-2">
<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('methodModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="update_method" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Update</button>
</div>
</form>
</div>
</div>

<!-- Add Method Modal -->
<div id="addMethodModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">➕ Add Method</h2>
<form method="POST">
<input type="hidden" name="tab" value="methods">
<input type="text" name="pymt_meth_name" placeholder="Method Name" required class="border p-2 rounded w-full mb-2">
<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('addMethodModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="add_method" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Add</button>
</div>
</form>
</div>
</div>

<!-- Status Modal -->
<div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">✏️ Update Status</h2>
<form method="POST">
<input type="hidden" name="tab" value="statuses">
<input type="hidden" id="modal_pymt_stat_id" name="pymt_stat_id">
<input type="text" id="modal_pymt_stat_name" name="pymt_stat_name" placeholder="Status Name" required class="border p-2 rounded w-full mb-2">
<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('statusModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="update_status" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Update</button>
</div>
</form>
</div>
</div>

<!-- Add Status Modal -->
<div id="addStatusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
<div class="bg-white p-6 rounded-2xl shadow w-96 relative">
<h2 class="text-xl font-semibold text-sky-700 mb-4">➕ Add Status</h2>
<form method="POST">
<input type="hidden" name="tab" value="statuses">
<input type="text" name="pymt_stat_name" placeholder="Status Name" required class="border p-2 rounded w-full mb-2">
<div class="flex justify-end space-x-2">
<button type="button" onclick="closeModal('addStatusModal')" class="px-4 py-2 rounded bg-gray-400 hover:bg-gray-500 text-white">Cancel</button>
<button type="submit" name="add_status" class="px-4 py-2 rounded bg-sky-700 hover:bg-sky-800 text-white">Add</button>
</div>
</form>
</div>
</div>

</main>

<script>
// Tab switching
function switchTab(tab) {
    document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
    document.getElementById(tab).classList.remove('hidden');
}

// Open/Close modals
function openPaymentModal(data) {
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('modal_pymt_id').value = data.pymt_id;
    document.getElementById('modal_pymt_amount_paid').value = data.pymt_amount_paid;
    document.getElementById('modal_appt_id').value = data.appt_id;
    document.getElementById('modal_pymt_meth_id').value = data.pymt_meth_id;
    document.getElementById('modal_pymt_stat_id').value = data.pymt_stat_id;
}
function openAddPaymentModal(){document.getElementById('addPaymentModal').classList.remove('hidden');}

function openMethodModal(data){
    document.getElementById('methodModal').classList.remove('hidden');
    document.getElementById('modal_pymt_meth_id').value = data.pymt_meth_id;
    document.getElementById('modal_pymt_meth_name').value = data.pymt_meth_name;
}
function openAddMethodModal(){document.getElementById('addMethodModal').classList.remove('hidden');}

function openStatusModal(data){
    document.getElementById('statusModal').classList.remove('hidden');
    document.getElementById('modal_pymt_stat_id').value = data.pymt_stat_id;
    document.getElementById('modal_pymt_stat_name').value = data.pymt_stat_name;
}
function openAddStatusModal(){document.getElementById('addStatusModal').classList.remove('hidden');}

function closeModal(id){document.getElementById(id).classList.add('hidden');}
</script>

<?php include "../Includes/footer.html"; ?>
