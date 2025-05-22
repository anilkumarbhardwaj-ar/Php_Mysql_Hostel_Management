<div class="content">
    <h2>User Management</h2>
    
    <?php
    // Check if user is admin
    if ($_SESSION['user_role'] != 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    ?>
    
    <div class="actions">
        <button class="btn btn-primary" id="addUserBtn">Add New User</button>
        
        <div class="search-filter">
            <input type="text" id="searchUser" placeholder="Search users...">
            <select id="filterRole">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="student">Student</option>
                <option value="guest">Guest</option>
            </select>
        </div>
    </div>
    
    <div class="data-table">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = getDbConnection();
                
                $sql = "
                    SELECT 
                        id,
                        name,
                        email,
                        role,
                        phone,
                        created_at
                    FROM 
                        users
                    ORDER BY 
                        name
                ";
                
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr data-role='" . $row['role'] . "'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . ucfirst($row['role']) . "</td>";
                        echo "<td>" . ($row['phone'] ?? '-') . "</td>";
                        echo "<td>" . formatDate($row['created_at']) . "</td>";
                        echo "<td class='actions'>";
                        echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Edit</button>";
                        echo "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>";
                        echo "<button class='btn-view' data-id='" . $row['id'] . "'>View</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No users found</td></tr>";
                }
                
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New User</h3>
            
            <form id="addUserForm" method="POST" action="ajax/add_user.php">
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
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="student">Student</option>
                        <option value="guest">Guest</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit User</h3>
            
            <form id="editUserForm" method="POST" action="ajax/update_user.php">
                <input type="hidden" id="editUserId" name="userId">
                
                <div class="form-group">
                    <label for="editName">Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="editRole">Role</label>
                    <select id="editRole" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="student">Student</option>
                        <option value="guest">Guest</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editPhone">Phone</label>
                    <input type="text" id="editPhone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="editAddress">Address</label>
                    <textarea id="editAddress" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>User Details</h3>
            
            <div id="userDetails" class="view-details">
                <!-- User details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
    
    <script>
        // Show/hide add user modal
        const addUserBtn = document.getElementById('addUserBtn');
        const addUserModal = document.getElementById('addUserModal');
        const closeBtn = addUserModal.querySelector('.close');
        
        addUserBtn.addEventListener('click', function() {
            addUserModal.style.display = 'block';
        });
        
        closeBtn.addEventListener('click', function() {
            addUserModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == addUserModal) {
                addUserModal.style.display = 'none';
            }
        });
        
        // Search and filter functionality
        const searchInput = document.getElementById('searchUser');
        const filterRole = document.getElementById('filterRole');
        const table = document.getElementById('usersTable');
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
        
        // Form submission via AJAX
        const addUserForm = document.getElementById('addUserForm');
        
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User added successfully!');
                    addUserModal.style.display = 'none';
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
        
        // Delete user
        const deleteButtons = document.querySelectorAll('.btn-delete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                
                if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                    fetch('ajax/delete_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: userId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('User deleted successfully!');
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
        
        // Show/hide edit user modal
        const editUserModal = document.getElementById('editUserModal');
        const editCloseBtn = editUserModal.querySelector('.close');
        
        editCloseBtn.addEventListener('click', function() {
            editUserModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == editUserModal) {
                editUserModal.style.display = 'none';
            }
        });
        
        // Show/hide view user modal
        const viewUserModal = document.getElementById('viewUserModal');
        const viewCloseBtn = viewUserModal.querySelector('.close');
        
        viewCloseBtn.addEventListener('click', function() {
            viewUserModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == viewUserModal) {
                viewUserModal.style.display = 'none';
            }
        });
        
        // Edit user button click handler
        const editButtons = document.querySelectorAll('.btn-edit');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                
                // Fetch user details
                fetch('ajax/get_user.php?id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Populate form fields
                            document.getElementById('editUserId').value = data.user.id;
                            document.getElementById('editName').value = data.user.name;
                            document.getElementById('editEmail').value = data.user.email;
                            document.getElementById('editRole').value = data.user.role;
                            document.getElementById('editPhone').value = data.user.phone || '';
                            document.getElementById('editAddress').value = data.user.address || '';
                            
                            // Show modal
                            editUserModal.style.display = 'block';
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
        
        // Form submission via AJAX - Edit User
        const editUserForm = document.getElementById('editUserForm');
        
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User updated successfully!');
                    editUserModal.style.display = 'none';
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
        
        // View user button click handler
        const viewButtons = document.querySelectorAll('.btn-view');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                
                // Fetch user details
                fetch('ajax/get_user.php?id=' + userId + '&detailed=true')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Populate user details
                            let html = `
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value">${data.user.name}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${data.user.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Role:</span>
                                    <span class="detail-value">${data.user.role.charAt(0).toUpperCase() + data.user.role.slice(1)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">${data.user.phone || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Address:</span>
                                    <span class="detail-value">${data.user.address || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Created:</span>
                                    <span class="detail-value">${formatDate(data.user.created_at)}</span>
                                </div>
                            `;
                            
                            document.getElementById('userDetails').innerHTML = html;
                            
                            // Show modal
                            viewUserModal.style.display = 'block';
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
        
        // Helper function to format dates
        function formatDate(dateString) {
            if (!dateString) return 'Not specified';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }
    </script>
</div>
