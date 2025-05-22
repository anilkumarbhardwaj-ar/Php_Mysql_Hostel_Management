<div class="content">
    <h2>Guest Management</h2>

    <?php
    // Check if user is admin
    if ($_SESSION['user_role'] != 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    ?>

    <div class="actions">
        <button class="btn btn-primary" id="addGuestBtn">Add New Guest</button>

        <div class="search-filter">
            <input type="text" id="searchGuest" placeholder="Search guests...">
            <select id="filterStatus">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="data-table">
        <table id="guestsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = getDbConnection();
                
                $sql = "
                    SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.phone,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
                        a.start_date as check_in,
                        a.end_date as check_out,
                        a.status
                    FROM 
                        users u
                    JOIN 
                        allocations a ON u.id = a.user_id
                    JOIN 
                        rooms r ON a.room_id = r.id
                    JOIN 
                        floors f ON r.floor_id = f.id
                    JOIN 
                        buildings b ON f.building_id = b.id
                    WHERE 
                        u.role = 'guest'
                    ORDER BY 
                        a.start_date DESC
                ";
                
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr data-status='" . $row['status'] . "'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . $row['room'] . "</td>";
                        echo "<td>" . formatDate($row['check_in']) . "</td>";
                        echo "<td>" . formatDate($row['check_out']) . "</td>";
                        
                        $statusClass = '';
                        switch ($row['status']) {
                            case 'active':
                                $statusClass = 'status-paid';
                                break;
                            case 'completed':
                                $statusClass = 'status-partial';
                                break;
                            case 'cancelled':
                                $statusClass = 'status-unpaid';
                                break;
                        }
                        
                        echo "<td><span class='" . $statusClass . "'>" . ucfirst($row['status']) . "</span></td>";
                        
                        echo "<td class='actions'>";
                        echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Edit</button>";
                        echo "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>";
                        echo "<button class='btn-view' data-id='" . $row['id'] . "'>View</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No guests found</td></tr>";
                }
                
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Guest Modal -->
    <div id="addGuestModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Guest</h3>

            <form id="addGuestForm" method="POST" action="ajax/add_guest.php">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="room">Room</label>
                    <select id="room" name="room" required>
                        <option value="">Select Room</option>
                        <?php
                        $conn = getDbConnection();
                        $sql = "
                            SELECT 
                                r.id,
                                CONCAT(b.name, ' - ', f.name, ' - ', r.room_number, ' (', r.type, ')') as room_name
                            FROM 
                                rooms r
                            JOIN 
                                floors f ON r.floor_id = f.id
                            JOIN 
                                buildings b ON f.building_id = b.id
                            WHERE 
                                (SELECT COUNT(*) FROM allocations WHERE room_id = r.id AND end_date >= CURDATE()) < r.capacity
                            ORDER BY 
                                b.name, f.name, r.room_number
                        ";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['room_name'] . "</option>";
                            }
                        }
                        
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="checkIn">Check-in Date</label>
                    <input type="date" id="checkIn" name="checkIn" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="checkOut">Check-out Date</label>
                    <input type="date" id="checkOut" name="checkOut"
                        value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="paymentMethod">Payment Method</label>
                    <select id="paymentMethod" name="paymentMethod" required>
                        <option value="">Select Method</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Guest</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Guest Modal -->
    <div id="editGuestModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Guest</h3>

            <form id="editGuestForm" method="POST" action="ajax/update_guest.php">
                <input type="hidden" id="editGuestId" name="guestId">

                <div class="form-group">
                    <label for="editName">Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" required>
                </div>

                <div class="form-group">
                    <label for="editPhone">Phone</label>
                    <input type="text" id="editPhone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="editAddress">Address</label>
                    <textarea id="editAddress" name="address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="status" required>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editCheckOut">Check-out Date</label>
                    <input type="date" id="editCheckOut" name="checkOut">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Guest</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Guest Modal -->
    <div id="viewGuestModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Guest Details</h3>

            <div id="guestDetails" class="view-details">
                <!-- Guest details will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <script>
    // Show/hide add guest modal
    const addGuestBtn = document.getElementById('addGuestBtn');
    const addGuestModal = document.getElementById('addGuestModal');
    const addCloseBtn = addGuestModal.querySelector('.close');

    addGuestBtn.addEventListener('click', function() {
        addGuestModal.style.display = 'block';
    });

    addCloseBtn.addEventListener('click', function() {
        addGuestModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addGuestModal) {
            addGuestModal.style.display = 'none';
        }
    });

    // Show/hide edit guest modal
    const editGuestModal = document.getElementById('editGuestModal');
    const editCloseBtn = editGuestModal.querySelector('.close');

    editCloseBtn.addEventListener('click', function() {
        editGuestModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editGuestModal) {
            editGuestModal.style.display = 'none';
        }
    });

    // Show/hide view guest modal
    const viewGuestModal = document.getElementById('viewGuestModal');
    const viewCloseBtn = viewGuestModal.querySelector('.close');

    viewCloseBtn.addEventListener('click', function() {
        viewGuestModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == viewGuestModal) {
            viewGuestModal.style.display = 'none';
        }
    });

    // Search and filter functionality
    const searchInput = document.getElementById('searchGuest');
    const filterStatus = document.getElementById('filterStatus');
    const table = document.getElementById('guestsTable');
    const rows = table.getElementsByTagName('tr');

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const statusValue = filterStatus.value;

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const status = row.dataset.status;

            const nameMatch = name.includes(searchValue);
            const emailMatch = email.includes(searchValue);
            const statusMatch = statusValue === '' || status === statusValue;

            if ((nameMatch || emailMatch) && statusMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    searchInput.addEventListener('keyup', filterTable);
    filterStatus.addEventListener('change', filterTable);

    // Form submission via AJAX - Add Guest
    const addGuestForm = document.getElementById('addGuestForm');

    addGuestForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/add_guest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Guest added successfully!');
                    addGuestModal.style.display = 'none';
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

    // Edit guest button click handler
    const editButtons = document.querySelectorAll('.btn-edit');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;

            // Fetch guest details
            fetch('ajax/get_guest.php?id=' + guestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('editGuestId').value = data.guest.id;
                        document.getElementById('editName').value = data.guest.name;
                        document.getElementById('editEmail').value = data.guest.email;
                        document.getElementById('editPhone').value = data.guest.phone || '';
                        document.getElementById('editAddress').value = data.guest.address || '';
                        document.getElementById('editStatus').value = data.guest.status || 'active';
                        document.getElementById('editCheckOut').value = data.guest.check_out || '';

                        // Show modal
                        editGuestModal.style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });
    });

    // Form submission via AJAX - Edit Guest
    const editGuestForm = document.getElementById('editGuestForm');

    editGuestForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/update_guest.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Guest updated successfully!');
                    editGuestModal.style.display = 'none';
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

    // View guest button click handler
    const viewButtons = document.querySelectorAll('.btn-view');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;

            // Fetch guest details
            fetch('ajax/get_guest.php?id=' + guestId + '&detailed=true')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate guest details
                        let html = `
                            <div class="detail-item">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value">${data.guest.name}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value">${data.guest.email}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value">${data.guest.phone || 'Not provided'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Address:</span>
                                <span class="detail-value">${data.guest.address || 'Not provided'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Room:</span>
                                <span class="detail-value">${data.guest.room}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-in:</span>
                                <span class="detail-value">${formatDate(data.guest.check_in)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-out:</span>
                                <span class="detail-value">${formatDate(data.guest.check_out)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value">${data.guest.status.charAt(0).toUpperCase() + data.guest.status.slice(1)}</span>
                            </div>
                        `;

                        // Add payment information if available
                        if (data.payments && data.payments.length > 0) {
                            html += `
                                <div class="detail-section">
                                    <h4>Payment Information</h4>
                                    <table class="detail-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            data.payments.forEach(payment => {
                                html += `
                                    <tr>
                                        <td>${formatDate(payment.payment_date)}</td>
                                        <td>₹${payment.total_amount} (Paid: ₹${payment.paid_amount})</td>
                                        <td>${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}</td>
                                    </tr>
                                `;
                            });

                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }

                        document.getElementById('guestDetails').innerHTML = html;

                        // Show modal
                        viewGuestModal.style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });
    });

    // Delete guest button click handler
    const deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const guestId = this.dataset.id;

            if (confirm('Are you sure you want to delete this guest? This action cannot be undone.')) {
                fetch('ajax/delete_guest.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: guestId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Guest deleted successfully!');
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

    // Helper function to format dates
    function formatDate(dateString) {
        if (!dateString) return 'Not specified';

        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Validate check-out date is after check-in date
    const checkInInput = document.getElementById('checkIn');
    const checkOutInput = document.getElementById('checkOut');

    checkInInput.addEventListener('change', function() {
        const checkInDate = new Date(this.value);
        const checkOutDate = new Date(checkOutInput.value);

        if (checkOutDate <= checkInDate) {
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.valueAsDate = nextDay;
        }
    });

    checkOutInput.addEventListener('change', function() {
        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(this.value);

        if (checkOutDate <= checkInDate) {
            alert('Check-out date must be after check-in date');
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            this.valueAsDate = nextDay;
        }
    });
    </script>
</div>