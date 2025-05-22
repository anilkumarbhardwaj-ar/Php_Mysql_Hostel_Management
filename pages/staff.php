<div class="content">
    <h2>Staff Management</h2>

    <?php
    // Check if user is admin
    if ($_SESSION['user_role'] != 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    ?>

    <div class="actions">
        <button class="btn btn-primary" id="addStaffBtn">Add New Staff</button>

        <div class="search-filter">
            <input type="text" id="searchStaff" placeholder="Search staff...">
            <select id="filterRole">
                <option value="">All Roles</option>
                <option value="trainer">Trainer</option>
                <option value="intern">Intern</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
    </div>

    <div class="data-table">
        <table id="staffTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Room</th>
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
                        sd.role as staff_role,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
                        CASE 
                            WHEN sa.check_in IS NOT NULL AND sa.check_out IS NULL THEN 'Present'
                            ELSE 'Absent'
                        END as status
                    FROM 
                        users u
                    LEFT JOIN 
                        staff_details sd ON u.id = sd.user_id
                    LEFT JOIN 
                        allocations a ON u.id = a.user_id AND a.end_date >= CURDATE()
                    LEFT JOIN 
                        rooms r ON a.room_id = r.id
                    LEFT JOIN 
                        floors f ON r.floor_id = f.id
                    LEFT JOIN 
                        buildings b ON f.building_id = b.id
                    LEFT JOIN 
                        staff_attendance sa ON u.id = sa.staff_id AND DATE(sa.check_in) = CURDATE()
                    WHERE 
                        u.role = 'staff'
                    ORDER BY 
                        u.name
                ";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr data-role='" . $row['staff_role'] . "'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . ucfirst($row['staff_role'] ?? 'Staff') . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . ($row['room'] ?? 'Not assigned') . "</td>";
                        
                        $statusClass = $row['status'] == 'Present' ? 'status-paid' : 'status-unpaid';
                        echo "<td><span class='" . $statusClass . "'>" . $row['status'] . "</span></td>";
                        
                        echo "<td class='actions'>";
                        echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Edit</button>";
                        echo "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>";
                        echo "<button class='btn-view' data-id='" . $row['id'] . "'>View</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No staff found</td></tr>";
                }
                
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Staff</h3>

            <form id="addStaffForm" method="POST" action="ajax/add_staff.php">
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
                    <input type="text" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="staffRole">Staff Role</label>
                    <select id="staffRole" name="staffRole" required>
                        <option value="">Select Role</option>
                        <option value="trainer">Trainer</option>
                        <option value="intern">Intern</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department">
                </div>

                <div class="form-group">
                    <label for="joiningDate">Joining Date</label>
                    <input type="date" id="joiningDate" name="joiningDate" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="assignRoom">Assign Room?</label>
                    <select id="assignRoom" name="assignRoom">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div id="roomDetails" style="display: none;">
                    <div class="form-group">
                        <label for="room">Room</label>
                        <select id="room" name="room">
                            <option value="">Select Room</option>
                            <?php
                            $conn = getDbConnection();
                            $sql = "
                                SELECT 
                                    r.id,
                                    CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room_name
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
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>" . $row['room_name'] . "</option>";
                                }
                            }
                            
                            closeDbConnection($conn);
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" name="startDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="endDate">End Date (Optional)</label>
                        <input type="date" id="endDate" name="endDate">
                        <small>Leave blank if end date is unknown</small>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Staff</h3>

            <form id="editStaffForm" method="POST" action="ajax/update_staff.php">
                <input type="hidden" id="editStaffId" name="staffId">

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
                    <input type="text" id="editPhone" name="phone">
                </div>

                <div class="form-group">
                    <label for="editStaffRole">Staff Role</label>
                    <select id="editStaffRole" name="staffRole" required>
                        <option value="">Select Role</option>
                        <option value="trainer">Trainer</option>
                        <option value="intern">Intern</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editDepartment">Department</label>
                    <input type="text" id="editDepartment" name="department">
                </div>

                <div class="form-group">
                    <label for="editAssignRoom">Update Room Assignment?</label>
                    <select id="editAssignRoom" name="updateRoom">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>
                </div>

                <div id="editRoomDetails" style="display: none;">
                    <div class="form-group">
                        <label for="editRoom">Room</label>
                        <select id="editRoom" name="room">
                            <option value="">Select Room</option>
                            <?php
                            $conn = getDbConnection();
                            $sql = "
                                SELECT 
                                    r.id,
                                    CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room_name
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
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['id'] . "'>" . $row['room_name'] . "</option>";
                                }
                            }
                            
                            closeDbConnection($conn);
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editStartDate">Start Date</label>
                        <input type="date" id="editStartDate" name="startDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="editEndDate">End Date (Optional)</label>
                        <input type="date" id="editEndDate" name="endDate">
                        <small>Leave blank if end date is unknown</small>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Staff Modal -->
    <div id="viewStaffModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Staff Details</h3>

            <div id="staffDetails" class="view-details">
                <!-- Staff details will be loaded here via AJAX -->
            </div>
        </div>
    </div>

    <script>
    // Show/hide add staff modal
    const addStaffBtn = document.getElementById('addStaffBtn');
    const addStaffModal = document.getElementById('addStaffModal');
    const addCloseBtn = addStaffModal.querySelector('.close');

    addStaffBtn.addEventListener('click', function() {
        addStaffModal.style.display = 'block';
    });

    addCloseBtn.addEventListener('click', function() {
        addStaffModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addStaffModal) {
            addStaffModal.style.display = 'none';
        }
    });

    // Show/hide edit staff modal
    const editStaffModal = document.getElementById('editStaffModal');
    const editCloseBtn = editStaffModal.querySelector('.close');

    editCloseBtn.addEventListener('click', function() {
        editStaffModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editStaffModal) {
            editStaffModal.style.display = 'none';
        }
    });

    // Show/hide view staff modal
    const viewStaffModal = document.getElementById('viewStaffModal');
    const viewCloseBtn = viewStaffModal.querySelector('.close');

    viewCloseBtn.addEventListener('click', function() {
        viewStaffModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == viewStaffModal) {
            viewStaffModal.style.display = 'none';
        }
    });

    // Show/hide room details based on selection in add form
    const assignRoomSelect = document.getElementById('assignRoom');
    const roomDetails = document.getElementById('roomDetails');

    assignRoomSelect.addEventListener('change', function() {
        if (this.value === 'yes') {
            roomDetails.style.display = 'block';
        } else {
            roomDetails.style.display = 'none';
        }
    });

    // Show/hide room details based on selection in edit form
    const editAssignRoomSelect = document.getElementById('editAssignRoom');
    const editRoomDetails = document.getElementById('editRoomDetails');

    editAssignRoomSelect.addEventListener('change', function() {
        if (this.value === 'yes') {
            editRoomDetails.style.display = 'block';
        } else {
            editRoomDetails.style.display = 'none';
        }
    });

    // Search and filter functionality
    const searchInput = document.getElementById('searchStaff');
    const filterRole = document.getElementById('filterRole');
    const table = document.getElementById('staffTable');
    const rows = table.getElementsByTagName('tr');

    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const roleValue = filterRole.value;

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.dataset.role;

            const nameMatch = name.includes(searchValue);
            const emailMatch = email.includes(searchValue);
            const roleMatch = roleValue === '' || role === roleValue;

            if ((nameMatch || emailMatch) && roleMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    searchInput.addEventListener('keyup', filterTable);
    filterRole.addEventListener('change', filterTable);

    // Form submission via AJAX - Add Staff
    const addStaffForm = document.getElementById('addStaffForm');

    addStaffForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/add_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Staff added successfully!');
                    addStaffModal.style.display = 'none';
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

    // Edit staff button click handler
    const editButtons = document.querySelectorAll('.btn-edit');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const staffId = this.dataset.id;

            // Fetch staff details
            fetch('ajax/get_staff.php?id=' + staffId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        document.getElementById('editStaffId').value = data.staff.id;
                        document.getElementById('editName').value = data.staff.name;
                        document.getElementById('editEmail').value = data.staff.email;
                        document.getElementById('editPhone').value = data.staff.phone || '';
                        document.getElementById('editStaffRole').value = data.staff.staff_role ||
                        '';
                        document.getElementById('editDepartment').value = data.staff.department ||
                            '';

                        // Show modal
                        editStaffModal.style.display = 'block';
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

    // Form submission via AJAX - Edit Staff
    const editStaffForm = document.getElementById('editStaffForm');

    editStaffForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/update_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Staff updated successfully!');
                    editStaffModal.style.display = 'none';
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

    // View staff button click handler
    const viewButtons = document.querySelectorAll('.btn-view');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const staffId = this.dataset.id;

            // Fetch staff details
            fetch('ajax/get_staff.php?id=' + staffId + '&detailed=true')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate staff details
                        let html = `
                            <div class="detail-item">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value">${data.staff.name}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value">${data.staff.email}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value">${data.staff.phone || 'Not provided'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Role:</span>
                                <span class="detail-value">${data.staff.staff_role ? data.staff.staff_role.charAt(0).toUpperCase() + data.staff.staff_role.slice(1) : 'Not assigned'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Department:</span>
                                <span class="detail-value">${data.staff.department || 'Not assigned'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Joining Date:</span>
                                <span class="detail-value">${data.staff.joining_date || 'Not provided'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Room:</span>
                                <span class="detail-value">${data.staff.room || 'Not assigned'}</span>
                            </div>
                        `;

                        // Add attendance history if available
                        if (data.attendance && data.attendance.length > 0) {
                            html += `
                                <div class="detail-section">
                                    <h4>Recent Attendance</h4>
                                    <table class="detail-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Check In</th>
                                                <th>Check Out</th>
                                                <th>Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            data.attendance.forEach(record => {
                                const checkIn = new Date(record.check_in);
                                let checkOut = record.check_out ? new Date(record
                                    .check_out) : null;
                                let hours = '-';

                                if (checkOut) {
                                    const diff = (checkOut - checkIn) / (1000 * 60 * 60);
                                    hours = diff.toFixed(2);
                                }

                                html += `
                                    <tr>
                                        <td>${checkIn.toLocaleDateString()}</td>
                                        <td>${checkIn.toLocaleTimeString()}</td>
                                        <td>${checkOut ? checkOut.toLocaleTimeString() : 'Not checked out'}</td>
                                        <td>${hours}</td>
                                    </tr>
                                `;
                            });

                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }

                        document.getElementById('staffDetails').innerHTML = html;

                        // Show modal
                        viewStaffModal.style.display = 'block';
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

    // Delete staff button click handler
    const deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const staffId = this.dataset.id;

            if (confirm(
                    'Are you sure you want to delete this staff member? This action cannot be undone.'
                    )) {
                fetch('ajax/delete_staff.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: staffId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Staff deleted successfully!');
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
</div>