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

// Check if building ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: buildings.php');
    exit;
}

$buildingId = sanitizeInput($_GET['id']);

// Get building details
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT 
        id,
        name,
        location,
        description
    FROM 
        buildings
    WHERE 
        id = ?
");
$stmt->bind_param("i", $buildingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: buildings.php');
    exit;
}

$building = $result->fetch_assoc();

// Get floors in this building
$stmt = $conn->prepare("
    SELECT 
        f.id,
        f.name,
        COUNT(r.id) as room_count
    FROM 
        floors f
    LEFT JOIN 
        rooms r ON f.id = r.floor_id
    WHERE 
        f.building_id = ?
    GROUP BY 
        f.id
    ORDER BY 
        f.name
");
$stmt->bind_param("i", $buildingId);
$stmt->execute();
$floorsResult = $stmt->get_result();

$floors = [];
while ($row = $floorsResult->fetch_assoc()) {
    $floors[] = $row;
}

// Get room statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(r.id) as total_rooms,
        SUM(r.capacity) as total_capacity,
        SUM(CASE WHEN a.id IS NULL THEN 0 ELSE 1 END) as occupied_beds
    FROM 
        rooms r
    JOIN 
        floors f ON r.floor_id = f.id
    LEFT JOIN 
        allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
    WHERE 
        f.building_id = ?
");
$stmt->bind_param("i", $buildingId);
$stmt->execute();
$statsResult = $stmt->get_result();
$stats = $statsResult->fetch_assoc();

$stmt->close();
closeDbConnection($conn);

// Include header
include 'includes/header.php';
?>

<div class="content">
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a> &gt;
        <a href="buildings.php">Buildings</a> &gt;
        <span>View Building</span>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Building Details: <?php echo $building['name']; ?></h2>
            <div class="actions">
                <a href="buildings.php" class="btn btn-secondary">Back to Buildings</a>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <button class="btn btn-primary edit-building-btn" data-id="<?php echo $building['id']; ?>">Edit
                    Building</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="building-details-grid">
                <div class="detail-item">
                    <span class="label">Building Name:</span>
                    <span class="value"><?php echo $building['name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Location:</span>
                    <span class="value"><?php echo $building['location']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Floors:</span>
                    <span class="value"><?php echo count($floors); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Rooms:</span>
                    <span class="value"><?php echo $stats['total_rooms'] ?? 0; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Capacity:</span>
                    <span class="value"><?php echo $stats['total_capacity'] ?? 0; ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Occupancy:</span>
                    <span class="value">
                        <?php 
                        $occupied = $stats['occupied_beds'] ?? 0;
                        $capacity = $stats['total_capacity'] ?? 0;
                        echo $occupied . '/' . $capacity;
                        if ($capacity > 0) {
                            $percentage = round(($occupied / $capacity) * 100);
                            echo ' (' . $percentage . '%)';
                        }
                        ?>
                    </span>
                </div>
                <?php if (!empty($building['description'])): ?>
                <div class="detail-item full-width">
                    <span class="label">Description:</span>
                    <span class="value"><?php echo $building['description']; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h3>Floors</h3>
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <a href="add_floor.php?building_id=<?php echo $building['id']; ?>" class="btn btn-primary">Add Floor</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($floors)): ?>
            <p>No floors added to this building yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Floor Name</th>
                            <th>Room Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($floors as $floor): ?>
                        <tr>
                            <td><?php echo $floor['name']; ?></td>
                            <td><?php echo $floor['room_count']; ?></td>
                            <td>
                                <a href="view_floor.php?id=<?php echo $floor['id']; ?>" class="btn-sm btn-view">View</a>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a href="edit_floor.php?id=<?php echo $floor['id']; ?>" class="btn-sm btn-edit">Edit</a>
                                <a href="#" class="btn-sm btn-delete delete-floor"
                                    data-id="<?php echo $floor['id']; ?>">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Building Modal -->
<div id="editBuildingModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Edit Building</h3>

        <form id="editBuildingForm" method="POST" action="ajax/update_building.php">
            <input type="hidden" method="POST" action="ajax/update_building.php">
            <input type="hidden" id="editBuildingId" name="buildingId" value="<?php echo $building['id']; ?>">

            <div class="form-group">
                <label for="editBuildingName">Building Name</label>
                <input type="text" id="editBuildingName" name="buildingName" required>
            </div>

            <div class="form-group">
                <label for="editLocation">Location</label>
                <input type="text" id="editLocation" name="location" required>
            </div>

            <div class="form-group">
                <label for="editDescription">Description (Optional)</label>
                <textarea id="editDescription" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Building</button>
            </div>
        </form>
    </div>
</div>

<script>
// Show/hide edit building modal
const editBuildingModal = document.getElementById('editBuildingModal');
const editCloseBtn = editBuildingModal.querySelector('.close');

editCloseBtn.addEventListener('click', function() {
    editBuildingModal.style.display = 'none';
});

window.addEventListener('click', function(event) {
    if (event.target == editBuildingModal) {
        editBuildingModal.style.display = 'none';
    }
});

// Edit building button functionality
document.querySelector('.edit-building-btn')?.addEventListener('click', function() {
    const buildingId = this.dataset.id;

    // Fetch building details
    fetch('ajax/get_building.php?id=' + buildingId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const building = data.building;

                document.getElementById('editBuildingId').value = building.id;
                document.getElementById('editBuildingName').value = building.name;
                document.getElementById('editLocation').value = building.location;
                document.getElementById('editDescription').value = building.description || '';

                editBuildingModal.style.display = 'block';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
});

// Form submission via AJAX - Edit Building
document.getElementById('editBuildingForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('ajax/update_building.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Building updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
});

// Delete floor functionality
document.querySelectorAll('.delete-floor')?.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const floorId = this.dataset.id;

        if (confirm(
                'Are you sure you want to delete this floor? This will also delete all rooms on this floor. This action cannot be undone.'
                )) {
            fetch('ajax/delete_floor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: floorId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Floor deleted successfully!');
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