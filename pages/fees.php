<div class="content">
    <h2>Fee Management</h2>
    
    <div class="actions">
        <button class="btn btn-primary" id="addFeeBtn">Add Fee Payment</button>
        
        <div class="search-filter">
            <input type="text" id="searchFee" placeholder="Search by name or ID...">
            <select id="filterStatus">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
    </div>
    
    <div class="data-table">
        <table id="feesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Room</th>
                    <th>Total Fee</th>
                    <th>Paid Amount</th>
                    <th>Due Amount</th>
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
                        u.role,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
                        p.total_amount,
                        p.paid_amount,
                        (p.total_amount - p.paid_amount) as due_amount,
                        p.status
                    FROM 
                        users u
                    LEFT JOIN 
                        allocations a ON u.id = a.user_id AND a.end_date >= CURDATE()
                    LEFT JOIN 
                        rooms r ON a.room_id = r.id
                    LEFT JOIN 
                        floors f ON r.floor_id = f.id
                    LEFT JOIN 
                        buildings b ON f.building_id = b.id
                    LEFT JOIN 
                        payments p ON u.id = p.user_id
                    WHERE 
                        u.role IN ('student', 'staff', 'guest')
                    ORDER BY 
                        u.name
                ";
                
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr data-status='" . $row['status'] . "'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . ucfirst($row['role']) . "</td>";
                        echo "<td>" . $row['room'] . "</td>";
                        echo "<td>₹" . number_format($row['total_amount']) . "</td>";
                        echo "<td>₹" . number_format($row['paid_amount']) . "</td>";
                        echo "<td>₹" . number_format($row['due_amount']) . "</td>";
                        
                        $statusClass = '';
                        switch ($row['status']) {
                            case 'paid':
                                $statusClass = 'status-paid';
                                break;
                            case 'partial':
                                $statusClass = 'status-partial';
                                break;
                            case 'unpaid':
                                $statusClass = 'status-unpaid';
                                break;
                        }
                        
                        echo "<td><span class='" . $statusClass . "'>" . ucfirst($row['status']) . "</span></td>";
                        
                        echo "<td class='actions'>";
                        echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Update</button>";
                        echo "<button class='btn-view' data-id='" . $row['id'] . "'>History</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No fee records found</td></tr>";
                }
                
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Fee Payment Modal -->
    <div id="addFeeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add Fee Payment</h3>
            
            <form id="addFeeForm" method="POST" action="ajax/add_fee_payment.php">
                <div class="form-group">
                    <label for="user">Select User</label>
                    <select id="user" name="user" required>
                        <option value="">Select User</option>
                        <?php
                        $conn = getDbConnection();
                        $sql = "
                            SELECT 
                                u.id,
                                u.name,
                                u.role
                            FROM 
                                users u
                            WHERE 
                                u.role IN ('student', 'staff', 'guest')
                            ORDER BY 
                                u.name
                        ";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['name'] . " (" . ucfirst($row['role']) . ")</option>";
                            }
                        }
                        
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Payment Amount</label>
                    <input type="number" id="amount" name="amount" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="paymentDate">Payment Date</label>
                    <input type="date" id="paymentDate" name="paymentDate" value="<?php echo date('Y-m-d'); ?>" required>
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
                    <label for="reference">Reference/Transaction ID</label>
                    <input type="text" id="reference" name="reference">
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Payment History Modal -->
    <div id="viewHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Payment History for <span id="userName"></span></h3>
            
            <div class="data-table">
                <table id="paymentHistoryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistoryBody">
                        <!-- Payment history will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Show/hide modals
        const addFeeBtn = document.getElementById('addFeeBtn');
        const addFeeModal = document.getElementById('addFeeModal');
        const viewHistoryModal = document.getElementById('viewHistoryModal');
        const closeBtns = document.querySelectorAll('.modal .close');
        
        // Show add fee modal
        addFeeBtn.addEventListener('click', function() {
            addFeeModal.style.display = 'block';
        });
        
        // Close modals
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });
        
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
        
        // Search and filter functionality
        const searchInput = document.getElementById('searchFee');
        const filterStatus = document.getElementById('filterStatus');
        const table = document.getElementById('feesTable');
        const rows = table.getElementsByTagName('tr');
        
        function filterTable() {
            const searchValue = searchInput.value.toLowerCase();
            const statusValue = filterStatus.value;
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const name = row.cells[1].textContent.toLowerCase();
                const id = row.cells[0].textContent.toLowerCase();
                const status = row.dataset.status;
                
                const nameMatch = name.includes(searchValue) || id.includes(searchValue);
                const statusMatch = statusValue === '' || status === statusValue;
                
                if (nameMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
        
        searchInput.addEventListener('keyup', filterTable);
        filterStatus.addEventListener('change', filterTable);
        
        // Add Fee Payment Form Submission
        const addFeeForm = document.getElementById('addFeeForm');
        
        addFeeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/add_fee_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment added successfully!');
                    addFeeModal.style.display = 'none';
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
        
        // Update Fee Payment
        const updateButtons = document.querySelectorAll('.btn-edit');
        
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const button = this;
                button.disabled = true;
                button.textContent = 'Loading...';
                
                // Fetch user's current payment details
                fetch('ajax/get_payment_details.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    button.disabled = false;
                    button.textContent = 'Update';
                    
                    if (data.success) {
                        // Create and show update modal
                        const updateModal = document.createElement('div');
                        updateModal.className = 'modal';
                        updateModal.innerHTML = `
                            <div class="modal-content">
                                <span class="close">&times;</span>
                                <h3>Update Fee Payment</h3>
                                <form id="updateFeeForm">
                                    <input type="hidden" name="user_id" value="${userId}">
                                    <div class="form-group">
                                        <label>Total Amount</label>
                                        <input type="number" name="total_amount" value="${data.payment.total_amount}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Paid Amount</label>
                                        <input type="number" name="paid_amount" value="${data.payment.paid_amount}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Due Amount</label>
                                        <input type="number" name="due_amount" value="${data.payment.due_amount}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>New Payment Amount</label>
                                        <input type="number" name="amount" min="1" max="${data.payment.due_amount}" required 
                                               oninput="validateAmount(this, ${data.payment.due_amount})">
                                        <small class="error-message" style="color: red; display: none;"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Date</label>
                                        <input type="date" name="payment_date" value="${new Date().toISOString().split('T')[0]}" required
                                               max="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="upi">UPI</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Reference/Transaction ID</label>
                                        <input type="text" name="reference_number" pattern="[A-Za-z0-9-]+" 
                                               title="Only letters, numbers, and hyphens are allowed">
                                    </div>
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" rows="3" maxlength="500"></textarea>
                                        <small class="char-count">0/500 characters</small>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Update Payment</button>
                                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        `;
                        
                        document.body.appendChild(updateModal);
                        updateModal.style.display = 'block';
                        
                        // Add character count for notes
                        const notesTextarea = updateModal.querySelector('textarea[name="notes"]');
                        const charCount = updateModal.querySelector('.char-count');
                        
                        notesTextarea.addEventListener('input', function() {
                            const remaining = 500 - this.value.length;
                            charCount.textContent = `${this.value.length}/500 characters`;
                            if (remaining < 50) {
                                charCount.style.color = 'red';
                            } else {
                                charCount.style.color = 'inherit';
                            }
                        });
                        
                        // Handle form submission
                        const updateForm = document.getElementById('updateFeeForm');
                        const submitButton = updateForm.querySelector('button[type="submit"]');
                        
                        updateForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            // Validate form
                            const amount = parseFloat(this.querySelector('input[name="amount"]').value);
                            const dueAmount = parseFloat(this.querySelector('input[name="due_amount"]').value);
                            
                            if (amount <= 0 || amount > dueAmount) {
                                alert('Please enter a valid payment amount');
                                return;
                            }
                            
                            // Disable submit button and show loading state
                            submitButton.disabled = true;
                            submitButton.textContent = 'Updating...';
                            
                            const formData = new FormData(this);
                            
                            fetch('ajax/update_payment.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Show success message
                                    const successMessage = document.createElement('div');
                                    successMessage.className = 'alert alert-success';
                                    successMessage.textContent = 'Payment updated successfully!';
                                    updateForm.insertBefore(successMessage, updateForm.firstChild);
                                    
                                    // Reload page after 1.5 seconds
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1500);
                                } else {
                                    // Show error message
                                    const errorMessage = document.createElement('div');
                                    errorMessage.className = 'alert alert-danger';
                                    errorMessage.textContent = 'Error: ' + data.message;
                                    updateForm.insertBefore(errorMessage, updateForm.firstChild);
                                    
                                    // Re-enable submit button
                                    submitButton.disabled = false;
                                    submitButton.textContent = 'Update Payment';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred. Please try again.');
                                submitButton.disabled = false;
                                submitButton.textContent = 'Update Payment';
                            });
                        });
                        
                        // Handle modal close
                        const closeBtn = updateModal.querySelector('.close');
                        closeBtn.addEventListener('click', function() {
                            updateModal.remove();
                        });
                        
                        window.addEventListener('click', function(event) {
                            if (event.target === updateModal) {
                                updateModal.remove();
                            }
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    button.disabled = false;
                    button.textContent = 'Update';
                });
            });
        });
        
        // Function to validate payment amount
        function validateAmount(input, maxAmount) {
            const value = parseFloat(input.value);
            const errorMessage = input.nextElementSibling;
            
            if (isNaN(value) || value <= 0) {
                errorMessage.textContent = 'Please enter a valid amount';
                errorMessage.style.display = 'block';
                input.setCustomValidity('Please enter a valid amount');
            } else if (value > maxAmount) {
                errorMessage.textContent = `Amount cannot exceed ₹${maxAmount}`;
                errorMessage.style.display = 'block';
                input.setCustomValidity(`Amount cannot exceed ₹${maxAmount}`);
            } else {
                errorMessage.style.display = 'none';
                input.setCustomValidity('');
            }
        }
        
        // View Payment History
        const viewButtons = document.querySelectorAll('.btn-view');
        const userName = document.getElementById('userName');
        const paymentHistoryBody = document.getElementById('paymentHistoryBody');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const name = this.closest('tr').cells[1].textContent;
                
                userName.textContent = name;
                
                // Fetch payment history
                fetch('ajax/get_payment_history.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    paymentHistoryBody.innerHTML = '';
                    
                    if (data.success && data.payments.length > 0) {
                        data.payments.forEach(payment => {
                            const row = document.createElement('tr');
                            
                            row.innerHTML = `
                                <td>${payment.date}</td>
                                <td>₹${payment.amount}</td>
                                <td>${payment.method}</td>
                                <td>${payment.reference || '-'}</td>
                                <td>${payment.notes || '-'}</td>
                            `;
                            
                            paymentHistoryBody.appendChild(row);
                        });
                    } else {
                        paymentHistoryBody.innerHTML = '<tr><td colspan="5">No payment history found</td></tr>';
                    }
                    
                    viewHistoryModal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    </script>
</div>
