<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if room ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: rooms.php');
    exit;
}

$roomId = sanitizeInput($_GET['id']);

// Get room details
$conn = getDbConnection();
$sql = "
    SELECT 
        r.id,
        r.room_number,
        r.capacity,
        r.type,
        r.description,
        f.id as floor_id,
        f.name as floor_name,
        b.id as building_id,
        b.name as building_name,
        COUNT(DISTINCT a.user_id) as occupied
    FROM 
        rooms r
    JOIN 
        floors f ON r.floor_id = f.id
    JOIN 
        buildings b ON f.building_id = b.id
    LEFT JOIN 
        allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
    WHERE 
        r.id = ?
    GROUP BY 
        r.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: rooms.php');
    exit;
}

$room = $result->fetch_assoc();

// Get current allocations
$sql = "
    SELECT 
        a.id,
        a.start_date,
        a.end_date,
        s.id as student_id,
        s.name as student_name,
        s.roll_number
    FROM 
        allocations a
    JOIN 
        students s ON a.user_id = s.id
    WHERE 
        a.room_id = ? AND a.end_date >= CURDATE()
    ORDER BY 
        a.start_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $roomId);
$stmt->execute();
$allocationsResult = $stmt->get_result();

$allocations = [];
while ($row = $allocationsResult->fetch_assoc()) {
    $allocations[] = $row;
}

$stmt->close();
closeDbConnection($conn);

// Set status
$status = 'vacant';
if ($room['occupied'] >= $room['capacity']) {
    $status = 'full';
} else if ($room['occupied'] > 0) {
    $status = 'occupied';
}

// Include header
include 'includes/header.php';
?>

<div class="content">
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a> &gt;
        <a href="rooms.php">Rooms</a> &gt;
        <span>View Room</span>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Room Details: <?php echo $room['room_number']; ?></h2>
            <div class="actions">
                <a href="rooms.php" class="btn btn-secondary">Back to Rooms</a>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <a href="#" class="btn btn-primary edit-room-btn" data-id="<?php echo $room['id']; ?>">Edit Room</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="room-details-grid">
                <div class="detail-item">
                    <span class="label">Room Number:</span>
                    <span class="value"><?php echo $room['room_number']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Building:</span>
                    <span class="value"><?php echo $room['building_name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Floor:</span>
                    <span class="value"><?php echo $room['floor_name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Type:</span>
                    <span class="value"><?php echo $room['type']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Capacity:</span>
                    <span class="value"><?php echo $room['occupied']; ?>/<?php echo $room['capacity']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Status:</span>
                    <span class="value status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                </div>
                <?php if (!empty($room['description'])): ?>
                <div class="detail-item full-width">
                    <span class="label">Description:</span>
                    <span class="value"><?php echo $room['description']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3>Current Allocations</h3>
            <?php if ($_SESSION['user_role'] == 'admin' && $room['occupied'] < $room['capacity']): ?>
            <a href="add_allocation.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary">Add Allocation</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($allocations)): ?>
            <p>No current allocations for this room.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Roll Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocations as $allocation): ?>
                        <tr>
                            <td><?php echo $allocation['student_name']; ?></td>
                            <td><?php echo $allocation['roll_number']; ?></td>
                            <td><?php echo date('d M Y', strtotime($allocation['start_date'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($allocation['end_date'])); ?></td>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <td>
                                <a href="edit_allocation.php?id=<?php echo $allocation['id']; ?>"
                                    class="btn-sm btn-edit">Edit</a>
                                <a href="#" class="btn-sm btn-delete delete-allocation"
                                    data-id="<?php echo $allocation['id']; ?>">Delete</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Edit room button functionality
document.querySelector('.edit-room-btn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const roomId = this.dataset.id;

    // Fetch room details
    fetch('ajax/get_room.php?id=' + roomId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const room = data.room;

                // Populate edit form
                document.getElementById('editRoomId').value = room.id;
                document.getElementById('editRoomNumber').value = room.room_number;
                document.getElementById('editBuilding').value = room.building_id;
                document.getElementById('editType').value = room.type;
                document.getElementById('editCapacity').value = room.capacity;
                document.getElementById('editDescription').value = room.description || '';

                // Load floors for the selected building and set the current floor
                loadFloors(room.building_id, document.getElementById('editFloor'), room.floor_id);

                // Show edit modal
                document.getElementById('editRoomModal').style.display = 'block';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
});

// Delete allocation functionality
document.querySelectorAll('.delete-allocation')?.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const allocationId = this.dataset.id;

        if (confirm('Are you sure you want to delete this allocation? This action cannot be undone.')) {
            fetch('ajax/delete_allocation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: allocationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Allocation deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>