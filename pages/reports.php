<div class="content">
    <h2>Reports</h2>
    
    <div class="report-filters">
        <div class="form-group">
            <label for="reportType">Report Type</label>
            <select id="reportType" name="reportType">
                <option value="occupancy">Room Occupancy</option>
                <option value="student">Student Report</option>
                <option value="course">Course-wise Report</option>
                <option value="fee">Fee Collection Report</option>
                <option value="staff">Staff Report</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="dateRange">Date Range</label>
            <select id="dateRange" name="dateRange">
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month" selected>This Month</option>
                <option value="year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>
        
        <div id="customDateRange" class="form-group custom-date-range" style="display: none;">
            <div>
                <label for="startDate">Start Date</label>
                <input type="date" id="startDate" name="startDate">
            </div>
            <div>
                <label for="endDate">End Date</label>
                <input type="date" id="endDate" name="endDate">
            </div>
        </div>
        
        <div class="form-group">
            <button id="generateBtn" class="btn btn-primary">Generate Report</button>
            <button id="exportPdfBtn" class="btn btn-secondary">Export as PDF</button>
            <button id="exportCsvBtn" class="btn btn-secondary">Export as CSV</button>
        </div>
    </div>
    
    <div id="loadingSpinner" class="loading-spinner"></div>
    
    <div id="reportContainer" class="report-container">
        <div id="occupancyReport" class="report-section">
            <h3>Room Occupancy Report</h3>
            
            <div class="report-summary">
                <div class="summary-card">
                    <h4>Total Rooms</h4>
                    <p class="summary-value" id="totalRooms">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Occupied Rooms</h4>
                    <p class="summary-value" id="occupiedRooms">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Vacant Rooms</h4>
                    <p class="summary-value" id="vacantRooms">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Occupancy Rate</h4>
                    <p class="summary-value" id="occupancyRate">0%</p>
                </div>
            </div>
            
            <div class="report-chart">
                <canvas id="occupancyChart"></canvas>
            </div>
            
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Floor</th>
                            <th>Room Number</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Occupied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="occupancyTableBody">
                        <!-- Data will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="studentReport" class="report-section" style="display: none;">
            <h3>Student Report</h3>
            
            <div class="report-summary">
                <div class="summary-card">
                    <h4>Total Students</h4>
                    <p class="summary-value" id="totalStudents">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Hostel Residents</h4>
                    <p class="summary-value" id="hostelResidents">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Fee Paid</h4>
                    <p class="summary-value" id="feePaid">0</p>
                </div>
                
                <div class="summary-card">
                    <h4>Fee Pending</h4>
                    <p class="summary-value" id="feePending">0</p>
                </div>
            </div>
            
            <div class="report-chart">
                <canvas id="studentChart"></canvas>
            </div>
            
            <div class="report-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Room</th>
                            <th>Fee Status</th>
                            <th>Join Date</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- Data will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Other report sections will be similar -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/reports.js"></script>
