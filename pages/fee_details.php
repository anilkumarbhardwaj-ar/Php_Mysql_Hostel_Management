<div class="content">
    <h2>My Fee Details</h2>
    
    <?php
    // Check if user is student or guest
    if ($_SESSION['user_role'] != 'student' && $_SESSION['user_role'] != 'guest') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Get payment details
    $sql = "
        SELECT 
            p.id,
            p.total_amount,
            p.paid_amount,
            (p.total_amount - p.paid_amount) as due_amount,
            p.status
        FROM 
            payments p
        WHERE 
            p.user_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $payment = $result->fetch_assoc();
        ?>
        
        <div class="fee-details-container">
            <div class="fee-summary">
                <div class="fee-card">
                    <h3>Total Fee</h3>
                    <div class="fee-amount">₹<?php echo number_format($payment['total_amount']); ?></div>
                </div>
                
                <div class="fee-card">
                    <h3>Paid Amount</h3>
                    <div class="fee-amount">₹<?php echo number_format($payment['paid_amount']); ?></div>
                </div>
                
                <div class="fee-card">
                    <h3>Due Amount</h3>
                    <div class="fee-amount">₹<?php echo number_format($payment['due_amount']); ?></div>
                </div>
                
                <div class="fee-card">
                    <h3>Status</h3>
                    <div class="fee-status status-<?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></div>
                </div>
            </div>
            
            <div class="payment-history">
                <h3>Payment History</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get payment transactions
                        $transactionSql = "
                            SELECT 
                                pt.payment_date,
                                pt.amount,
                                pt.payment_method,
                                pt.reference_number,
                                pt.notes
                            FROM 
                                payment_transactions pt
                            WHERE 
                                pt.payment_id = ?
                            ORDER BY 
                                pt.payment_date DESC
                        ";
                        
                        $transactionStmt = $conn->prepare($transactionSql);
                        $transactionStmt->bind_param("i", $payment['id']);
                        $transactionStmt->execute();
                        $transactionResult = $transactionStmt->get_result();
                        
                        if ($transactionResult->num_rows > 0) {
                            while ($transaction = $transactionResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . formatDate($transaction['payment_date']) . "</td>";
                                echo "<td>₹" . number_format($transaction['amount']) . "</td>";
                                echo "<td>" . ucfirst($transaction['payment_method']) . "</td>";
                                echo "<td>" . ($transaction['reference_number'] ?? '-') . "</td>";
                                echo "<td>" . ($transaction['notes'] ?? '-') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No payment transactions found</td></tr>";
                        }
                        
                        $transactionStmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($payment['due_amount'] > 0) { ?>
            <div class="payment-options">
                <h3>Payment Options</h3>
                
                <div class="payment-methods">
                    <div class="payment-method-card">
                        <h4>Bank Transfer</h4>
                        <p><strong>Account Name:</strong> Hostel Management</p>
                        <p><strong>Account Number:</strong> 1234567890</p>
                        <p><strong>IFSC Code:</strong> ABCD0001234</p>
                        <p><strong>Bank:</strong> Example Bank</p>
                    </div>
                    
                    <div class="payment-method-card">
                        <h4>UPI Payment</h4>
                        <p><strong>UPI ID:</strong> hostel@upi</p>
                        <p>Scan the QR code below:</p>
                        <div class="qr-code">
                            <img src="assets/images/qr-code.png" alt="UPI QR Code">
                        </div>
                    </div>
                    
                    <div class="payment-method-card">
                        <h4>Cash Payment</h4>
                        <p>Visit the hostel office during working hours:</p>
                        <p><strong>Timing:</strong> 9:00 AM - 5:00 PM</p>
                        <p><strong>Days:</strong> Monday - Saturday</p>
                    </div>
                </div>
                
                <div class="payment-note">
                    <p>After making payment, please update your payment details by clicking the button below:</p>
                    <button class="btn btn-primary" id="updatePaymentBtn">Update Payment Details</button>
                </div>
            </div>
            
            <!-- Update Payment Modal -->
            <div id="updatePaymentModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Update Payment Details</h3>
                    
                    <form id="updatePaymentForm" method="POST" action="ajax/update_payment.php">
                        <div class="form-group">
                            <label for="paymentAmount">Payment Amount</label>
                            <input type="number" id="paymentAmount" name="paymentAmount" min="1" max="<?php echo $payment['due_amount']; ?>" required>
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
                            <label for="referenceNumber">Reference/Transaction ID</label>
                            <input type="text" id="referenceNumber" name="referenceNumber">
                        </div>
                        
                        <div class="form-group">
                            <label for="paymentNotes">Notes</label>
                            <textarea id="paymentNotes" name="paymentNotes" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Submit Payment Details</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                // Show/hide update payment modal
                const updatePaymentBtn = document.getElementById('updatePaymentBtn');
                const updatePaymentModal = document.getElementById('updatePaymentModal');
                const closeBtn = updatePaymentModal.querySelector('.close');
                
                updatePaymentBtn.addEventListener('click', function() {
                    updatePaymentModal.style.display = 'block';
                });
                
                closeBtn.addEventListener('click', function() {
                    updatePaymentModal.style.display = 'none';
                });
                
                window.addEventListener('click', function(event) {
                    if (event.target == updatePaymentModal) {
                        updatePaymentModal.style.display = 'none';
                    }
                });
                
                // Form submission via AJAX
                const updatePaymentForm = document.getElementById('updatePaymentForm');
                
                updatePaymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('ajax/update_payment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Payment details submitted successfully! Your payment will be verified by the administrator.');
                            updatePaymentModal.style.display = 'none';
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
            </script>
            <?php } ?>
        </div>
        
        <?php
    } else {
        echo '<div class="alert alert-info">No fee details found. Please contact the administrator.</div>';
    }
    
    $stmt->close();
    closeDbConnection($conn);
    ?>
</div>
