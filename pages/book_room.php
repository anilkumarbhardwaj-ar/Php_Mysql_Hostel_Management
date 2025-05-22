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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $roomId = sanitizeInput($_POST['room']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    
    // Validate dates
    if (strtotime($endDate) <= strtotime($startDate)) {
        $error = "End date must be after start date";
    } else {
        // Check room availability
        if (isRoomAvailable($roomId, $startDate, $endDate)) {
            $conn = getDbConnection();
            
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Insert booking
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iiss", $userId, $roomId, $startDate, $endDate);
                
                if ($stmt->execute()) {
                    $bookingId = $conn->insert_id;
                    
                    // Calculate fee
                    $fee = calculateFee($roomId, $startDate, $endDate);
                    
                    // Create payment record
                    $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, status) VALUES (?, ?, 'pending')");
                    $stmt->bind_param("id", $bookingId, $fee);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = "Room booking request submitted successfully. Please complete the payment to confirm your booking.";
                } else {
                    throw new Exception("Error creating booking");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
            }
            
            $stmt->close();
            closeDbConnection($conn);
        } else {
            $error = "Selected room is not available for the chosen dates";
        }
    }
}
?>

<div class="content">
    <h2>Book a Room</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="booking-form">
        <form method="POST" action="">
            <div class="form-group">
                <label for="room">Select Room</label>
                <select id="room" name="room" required>
                    <option value="">Choose a room...</option>
                    <?php
                    $conn = getDbConnection();
                    $sql = "
                        SELECT 
                            r.id,
                            r.room_number,
                            r.type,
                            r.capacity,
                            f.name as floor_name,
                            b.name as building_name,
                            rt.rate
                        FROM 
                            rooms r
                        JOIN 
                            floors f ON r.floor_id = f.id
                        JOIN 
                            buildings b ON f.building_id = b.id
                        JOIN 
                            room_types rt ON r.type = rt.type
                        WHERE 
                            r.status = 'available'
                        ORDER BY 
                            b.name, f.name, r.room_number
                    ";
                    
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "' data-rate='" . $row['rate'] . "'>";
                            echo $row['building_name'] . " - " . $row['floor_name'] . " - Room " . $row['room_number'];
                            echo " (" . ucfirst($row['type']) . " - $" . $row['rate'] . "/day)";
                            echo "</option>";
                        }
                    }
                    
                    closeDbConnection($conn);
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="start_date">Check-in Date</label>
                <input type="date" id="start_date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">Check-out Date</label>
                <input type="date" id="end_date" name="end_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            
            <div class="form-group">
                <label>Estimated Total</label>
                <div id="total_amount">$0.00</div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Book Room</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalAmountDiv = document.getElementById('total_amount');
    
    function calculateTotal() {
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const rate = selectedOption.dataset.rate || 0;
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && !isNaN(startDate) && !isNaN(endDate)) {
            const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            if (days > 0) {
                const total = days * rate;
                totalAmountDiv.textContent = '$' + total.toFixed(2);
            } else {
                totalAmountDiv.textContent = '$0.00';
            }
        } else {
            totalAmountDiv.textContent = '$0.00';
        }
    }
    
    roomSelect.addEventListener('change', calculateTotal);
    startDateInput.addEventListener('change', calculateTotal);
    endDateInput.addEventListener('change', calculateTotal);
});
</script>

<style>
.booking-form {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

#total_amount {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background: #0056b3;
}
</style> 