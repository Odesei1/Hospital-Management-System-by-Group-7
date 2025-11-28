<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/schedule.php";

$db = new Database();
$conn = $db->connect();
$scheduleClass = new Schedule($conn);

$doctor_id = $_SESSION['doc_id'];
$success = $error = "";

// Pagination setup
$limit = 10; // schedules per page (UPDATED)
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Add schedule
if (isset($_POST['add'])) {
    try {
        $scheduleClass->add($doctor_id, $_POST['days'], $_POST['start'], $_POST['end']);
        $success = "✅ Schedule added!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Delete schedule
if (isset($_GET['delete'])) {
    try {
        $scheduleClass->delete($_GET['delete']);
        $success = "⚠️ Schedule deleted.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set active schedule
if (isset($_GET['set_active'])) {
    $activeSchedFile = __DIR__ . "/DoctorSchedule_Files/active_schedule_{$doctor_id}.txt";
    file_put_contents($activeSchedFile, $_GET['set_active']);
    $success = "✅ Active schedule updated!";
}

// Read active schedule
$activeSchedFile = __DIR__ . "/DoctorSchedule_Files/active_schedule_{$doctor_id}.txt";
$activeSchedule = file_exists($activeSchedFile) ? (int) trim(file_get_contents($activeSchedFile)) : null;

// Count total schedules
$stmtCount = $conn->prepare("SELECT COUNT(*) FROM schedule WHERE doc_id = :doc_id");
$stmtCount->execute([':doc_id' => $doctor_id]);
$totalSchedules = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalSchedules / $limit);

// Fetch schedules (paginated)
$stmt = $conn->prepare("SELECT * FROM schedule 
                        WHERE doc_id = :doc_id 
                        ORDER BY sched_created_at DESC 
                        LIMIT :limit OFFSET :offset");
$stmt->bindValue(':doc_id', $doctor_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_dashboard.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow p-8 max-w-5xl mx-auto">
    <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Doctor Schedule Management</h1>

    <?php if ($success): ?>
        <p class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></p>
    <?php endif; ?>

    <!-- Add Schedule -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-3">➕ Add New Schedule</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="days" placeholder="Days (e.g. Mon-Fri)" required class="border p-2 rounded">
            <input type="time" name="start" required class="border p-2 rounded">
            <input type="time" name="end" required class="border p-2 rounded">
            <button name="add" class="col-span-3 bg-sky-700 text-white py-2 rounded hover:bg-sky-800">Add Schedule</button>
        </form>
    </div>

    <!-- Schedule Table -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-3">🕒 My Schedules</h2>

        <?php if ($schedules): ?>
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead class="bg-sky-700 text-white">
                <tr>
                    <th class="p-2 border">#</th>
                    <th class="p-2 border">ID</th>
                    <th class="p-2 border text-left">Days</th>
                    <th class="p-2 border text-left">Start Time</th>
                    <th class="p-2 border text-left">End Time</th>
                    <th class="p-2 border text-center">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($schedules as $i => $row): ?>
                <tr class="hover:bg-gray-100">
                    <td class="p-2 border text-center"><?= $offset + $i + 1 ?></td>
                    <td class="p-2 border text-center"><?= $row['sched_id'] ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($row['sched_days']) ?></td>
                    <td class="p-2 border"><?= date("g:i A", strtotime($row['sched_start_time'])) ?></td>
                    <td class="p-2 border"><?= date("g:i A", strtotime($row['sched_end_time'])) ?></td>
                    <td class="p-2 border text-center space-x-2">

                        <!-- ACTIVE BUTTON -->
                        <?php if ($activeSchedule === (int)$row['sched_id']): ?>
                            <span class="text-green-700 font-semibold">✅ Active</span>
                        <?php else: ?>
                            <a href="?set_active=<?= $row['sched_id'] ?>&page=<?= $page ?>"
                               class="text-blue-600 hover:underline">Set Active</a>
                        <?php endif; ?>

                        <!-- DELETE BUTTON -->
                        <a href="?delete=<?= $row['sched_id'] ?>&page=<?= $page ?>"
                           onclick="return confirm('Delete this schedule?')"
                           class="text-red-600 hover:underline">Delete</a>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-4 gap-2">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>"
                   class="px-3 py-1 border rounded-lg 
                   <?= $p == $page ? 'bg-sky-700 text-white' : 'hover:bg-gray-100 text-sky-700' ?>">
                   <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <p class="text-gray-600 italic">No schedules found.</p>
        <?php endif; ?>
    </div>

</main>

<?php include "../Includes/footer.html"; ?>
