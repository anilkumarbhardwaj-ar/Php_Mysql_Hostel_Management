<?php
// Check if user is logged in and has appropriate role
if (!isLoggedIn() || !in_array($_SESSION['user_role'], ['student', 'guest', 'others'])) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$message = '';
$error = '';

// Get user's bookings
$conn = getDbConnection();
$sql = "
    SELECT 
        b.id,
        b.check_in_date,
        b.check_out_date,
        b.status,
        r.room_number,
        r.type,
        f.name as floor_name,
        bld.name as building_name,
        p.amount,
        p.status as payment_status
    FROM 
        bookings b
    JOIN 
        rooms r ON b.room_id = r.id
    JOIN 
        floors f ON r.floor_id = f.id
    JOIN 
        buildings bld ON f.building_id = bld.id
    LEFT JOIN 
        payments p ON b.id = p.booking_id
    WHERE 
        b.user_id = ?
    ORDER BY 
        b.check_in_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
closeDbConnection($conn);
?>

<div class="content">
    <h2>My Bookings</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="bookings-list">
        <?php if (empty($bookings)): ?>
            <div class="no-bookings">
                <p>You haven't made any bookings yet.</p>
                <a href="index.php?page=book_room" class="btn btn-primary">Book a Room</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo $booking['building_name'] . " - " . 
                                         $booking['floor_name'] . " - Room " . 
                                         $booking['room_number'] . " (" . 
                                         ucfirst($booking['type']) . ")";
                                    ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($booking['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['payment_status']; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] == 'pending' && $booking['payment_status'] == 'pending'): ?>
                                        <a href="index.php?page=make_payment&booking_id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-sm btn-primary">Make Payment</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-danger cancel-booking" 
                                                data-id="<?php echo $booking['id']; ?>">Cancel</button>
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

<style>
.bookings-list {
    margin-top: 20px;
}

.no-bookings {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.table {
    width: 100%;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status-completed {
    background: #cce5ff;
    color: #004085;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
    margin: 0 2px;
}

.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-danger:hover {
    background: #c82333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle booking cancellation
    const cancelButtons = document.querySelectorAll('.cancel-booking');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                const bookingId = this.dataset.id;
                
                fetch('ajax/cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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
});
</script> 