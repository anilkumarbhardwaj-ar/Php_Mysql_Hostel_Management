<div class="content">
    <h2>System Settings</h2>
    
    <?php
    // Check if user is admin
    if ($_SESSION['user_role'] != 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $conn = getDbConnection();
        $success = true;
        $message = '';
        
        // Update settings
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8); // Remove 'setting_' prefix
                $settingValue = sanitizeInput($value);
                
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->bind_param("ss", $settingValue, $settingKey);
                
                if (!$stmt->execute()) {
                    $success = false;
                    $message = "Failed to update setting: " . $settingKey;
                    break;
                }
                
                $stmt->close();
            }
        }
        
        closeDbConnection($conn);
        
        if ($success) {
            echo '<div class="alert alert-success">Settings updated successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $message . '</div>';
        }
    }
    
    // Get current settings
    $conn = getDbConnection();
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    
    $settings = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    closeDbConnection($conn);
    ?>
    
    <form method="POST" action="">
        <div class="settings-container">
            <div class="settings-section">
                <h3>General Settings</h3>
                
                <div class="form-group">
                    <label for="setting_site_name">Site Name</label>
                    <input type="text" id="setting_site_name" name="setting_site_name" value="<?php echo $settings['site_name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="setting_site_email">Site Email</label>
                    <input type="email" id="setting_site_email" name="setting_site_email" value="<?php echo $settings['site_email'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="setting_site_phone">Site Phone</label>
                    <input type="text" id="setting_site_phone" name="setting_site_phone" value="<?php echo $settings['site_phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="setting_site_address">Site Address</label>
                    <textarea id="setting_site_address" name="setting_site_address" rows="3"><?php echo $settings['site_address'] ?? ''; ?></textarea>
                </div>
            </div>
            
            <div class="settings-section">
                <h3>Financial Settings</h3>
                
                <div class="form-group">
                    <label for="setting_currency">Currency</label>
                    <input type="text" id="setting_currency" name="setting_currency" value="<?php echo $settings['currency'] ?? 'INR'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="setting_tax_rate">Tax Rate (%)</label>
                    <input type="number" id="setting_tax_rate" name="setting_tax_rate" value="<?php echo $settings['tax_rate'] ?? '0'; ?>" min="0" max="100" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="setting_ac_room_rate">AC Room Rate (per day)</label>
                    <input type="number" id="setting_ac_room_rate" name="setting_ac_room_rate" value="<?php echo $settings['ac_room_rate'] ?? '1000'; ?>" min="0" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="setting_non_ac_room_rate">Non-AC Room Rate (per day)</label>
                    <input type="number" id="setting_non_ac_room_rate" name="setting_non_ac_room_rate" value="<?php echo $settings['non_ac_room_rate'] ?? '750'; ?>" min="0" step="0.01">
                </div>
            </div>
            
            <div class="settings-section">
                <h3>System Settings</h3>
                
                <div class="form-group">
                    <label for="setting_maintenance_mode">Maintenance Mode</label>
                    <select id="setting_maintenance_mode" name="setting_maintenance_mode">
                        <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Off</option>
                        <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>On</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="setting_default_pagination">Default Pagination</label>
                    <input type="number" id="setting_default_pagination" name="setting_default_pagination" value="<?php echo $settings['default_pagination'] ?? '10'; ?>" min="5" max="100">
                </div>
                
                <div class="form-group">
                    <label for="setting_date_format">Date Format</label>
                    <select id="setting_date_format" name="setting_date_format">
                        <option value="d/m/Y" <?php echo ($settings['date_format'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                        <option value="m/d/Y" <?php echo ($settings['date_format'] ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                        <option value="Y-m-d" <?php echo ($settings['date_format'] ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>
