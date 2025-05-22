<div class="dashboard">
    <h2>Dashboard</h2>

    <div class="dashboard-stats">
        <?php
        $conn = getDbConnection();
        
        // Get total students
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
        $studentCount = $result->fetch_assoc()['count'];
        
        // Get total staff
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
        $staffCount = $result->fetch_assoc()['count'];
        
        // Get total rooms
        $result = $conn->query("SELECT COUNT(*) as count FROM rooms");
        $roomCount = $result->fetch_assoc()['count'];
        
        // Get occupied rooms
        $result = $conn->query("
            SELECT COUNT(DISTINCT room_id) as count 
            FROM allocations 
            WHERE end_date >= CURDATE()
        ");
        $occupiedRoomCount = $result->fetch_assoc()['count'];
        
        // Get vacant rooms
        $vacantRoomCount = $roomCount - $occupiedRoomCount;
        
        // Get total courses
        $result = $conn->query("SELECT COUNT(*) as count FROM courses");
        $courseCount = $result->fetch_assoc()['count'];
        
        // Get total fee collection
        $result = $conn->query("SELECT SUM(paid_amount) as total FROM payments");
        $feeCollection = $result->fetch_assoc()['total'] ?? 0;
        
        // Get pending fee
        $result = $conn->query("SELECT SUM(total_amount - paid_amount) as total FROM payments");
        $pendingFee = $result->fetch_assoc()['total'] ?? 0;
        
        // Get student distribution by course
        $sql = "
            SELECT 
                c.name,
                COUNT(sc.student_id) as student_count
            FROM 
                courses c
            LEFT JOIN 
                student_courses sc ON c.id = sc.course_id
            GROUP BY 
                c.id
            ORDER BY 
                student_count DESC
            LIMIT 5
        ";
        
        $result = $conn->query($sql);
        
        $courseLabels = [];
        $studentCounts = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courseLabels[] = $row['name'];
                $studentCounts[] = $row['student_count'];
            }
        }
        
        closeDbConnection($conn);
        ?>

        <div class="stat-card">
            <h3>Students</h3>
            <p class="stat-value"><?php echo $studentCount; ?></p>
            <p class="stat-label">Total Enrolled</p>
        </div>

        <div class="stat-card">
            <h3>Staff</h3>
            <p class="stat-value"><?php echo $staffCount; ?></p>
            <p class="stat-label">Total Staff</p>
        </div>

        <div class="stat-card">
            <h3>Rooms</h3>
            <p class="stat-value"><?php echo $roomCount; ?></p>
            <p class="stat-label">Total Rooms</p>
        </div>

        <div class="stat-card">
            <h3>Occupancy</h3>
            <p class="stat-value"><?php echo $occupiedRoomCount; ?></p>
            <p class="stat-label">Occupied Rooms</p>
        </div>

        <div class="stat-card">
            <h3>Vacancy</h3>
            <p class="stat-value"><?php echo $vacantRoomCount; ?></p>
            <p class="stat-label">Available Rooms</p>
        </div>

        <div class="stat-card">
            <h3>Courses</h3>
            <p class="stat-value"><?php echo $courseCount; ?></p>
            <p class="stat-label">Active Courses</p>
        </div>

        <div class="stat-card">
            <h3>Fee Collection</h3>
            <p class="stat-value">₹<?php echo number_format($feeCollection); ?></p>
            <p class="stat-label">Total Collected</p>
        </div>

        <div class="stat-card">
            <h3>Pending Fee</h3>
            <p class="stat-value">₹<?php echo number_format($pendingFee); ?></p>
            <p class="stat-label">To Be Collected</p>
        </div>
    </div>

    <div class="dashboard-charts">
        <div class="chart-container">
            <h3>Room Occupancy</h3>
            <div class="chart-wrapper">
                <canvas id="occupancyChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h3>Student Distribution</h3>
            <div class="chart-wrapper">
                <canvas id="studentDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="recent-activities">
            <h3>Recent Activities</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conn = getDbConnection();
                        
                        $sql = "
                            SELECT 
                                u.name, 
                                l.action, 
                                l.created_at 
                            FROM 
                                user_logs l
                            JOIN 
                                users u ON l.user_id = u.id
                            ORDER BY 
                                l.created_at DESC
                            LIMIT 10
                        ";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['action'] . "</td>";
                                echo "<td>" . formatDate($row['created_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No recent activities</td></tr>";
                        }
                        
                        closeDbConnection($conn);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="upcoming-events">
            <h3>Upcoming Events</h3>
            <div class="event-list">
                <?php
                $conn = getDbConnection();
                
                // Get current date
                $currentDate = date('Y-m-d');
                
                // Get upcoming check-ins
                $sql = "
                    SELECT 
                        u.name,
                        'Check-in' as event_type,
                        a.start_date as event_date,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as location
                    FROM 
                        allocations a
                    JOIN 
                        users u ON a.user_id = u.id
                    JOIN 
                        rooms r ON a.room_id = r.id
                    JOIN 
                        floors f ON r.floor_id = f.id
                    JOIN 
                        buildings b ON f.building_id = b.id
                    WHERE 
                        a.start_date >= '$currentDate'
                    
                    UNION
                    
                    SELECT 
                        u.name,
                        'Check-out' as event_type,
                        a.end_date as event_date,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as location
                    FROM 
                        allocations a
                    JOIN 
                        users u ON a.user_id = u.id
                    JOIN 
                        rooms r ON a.room_id = r.id
                    JOIN 
                        floors f ON r.floor_id = f.id
                    JOIN 
                        buildings b ON f.building_id = b.id
                    WHERE 
                        a.end_date >= '$currentDate'
                    
                    ORDER BY 
                        event_date
                    LIMIT 5
                ";
                
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $eventClass = $row['event_type'] == 'Check-in' ? 'event-checkin' : 'event-checkout';
                        
                        echo "<div class='event-item $eventClass'>";
                        echo "<div class='event-date'>" . formatDate($row['event_date']) . "</div>";
                        echo "<div class='event-details'>";
                        echo "<div class='event-title'>" . $row['event_type'] . ": " . $row['name'] . "</div>";
                        echo "<div class='event-location'>" . $row['location'] . "</div>";
                        echo "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='no-events'>No upcoming events</div>";
                }
                
                closeDbConnection($conn);
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Room Occupancy Chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    const occupancyChart = new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Vacant'],
            datasets: [{
                data: [<?php echo $occupiedRoomCount; ?>, <?php echo $vacantRoomCount; ?>],
                backgroundColor: ['#3b82f6', '#10b981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: window.innerWidth < 768 ? 11 : 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    bodyFont: {
                        size: window.innerWidth < 768 ? 12 : 13
                    },
                    titleFont: {
                        size: window.innerWidth < 768 ? 13 : 14,
                        weight: 'bold'
                    },
                    cornerRadius: 6
                }
            },
            cutout: window.innerWidth < 768 ? '60%' : '70%',
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Student Distribution Chart
    const studentDistributionCtx = document.getElementById('studentDistributionChart').getContext('2d');
    const studentDistributionChart = new Chart(studentDistributionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($courseLabels); ?>,
            datasets: [{
                label: 'Students',
                data: <?php echo json_encode($studentCounts); ?>,
                backgroundColor: '#3b82f6',
                borderWidth: 0,
                borderRadius: 6,
                barThickness: window.innerWidth < 768 ? 15 : 20,
                maxBarThickness: window.innerWidth < 768 ? 20 : 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    bodyFont: {
                        size: window.innerWidth < 768 ? 12 : 13
                    },
                    titleFont: {
                        size: window.innerWidth < 768 ? 13 : 14,
                        weight: 'bold'
                    },
                    cornerRadius: 6
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e5e7eb',
                        drawBorder: false
                    },
                    ticks: {
                        precision: 0,
                        font: {
                            size: window.innerWidth < 768 ? 10 : 11,
                            weight: '500'
                        },
                        padding: window.innerWidth < 768 ? 5 : 10
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: window.innerWidth < 768 ? 10 : 11,
                            weight: '500'
                        },
                        maxRotation: 45,
                        minRotation: 45,
                        padding: window.innerWidth < 768 ? 5 : 10
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Function to handle chart resizing
    function resizeCharts() {
        if (occupancyChart) {
            occupancyChart.options.plugins.legend.labels.font.size = window.innerWidth < 768 ? 11 : 12;
            occupancyChart.options.plugins.tooltip.bodyFont.size = window.innerWidth < 768 ? 12 : 13;
            occupancyChart.options.plugins.tooltip.titleFont.size = window.innerWidth < 768 ? 13 : 14;
            occupancyChart.options.cutout = window.innerWidth < 768 ? '60%' : '70%';
            occupancyChart.resize();
        }
        
        if (studentDistributionChart) {
            studentDistributionChart.data.datasets[0].barThickness = window.innerWidth < 768 ? 15 : 20;
            studentDistributionChart.data.datasets[0].maxBarThickness = window.innerWidth < 768 ? 20 : 30;
            studentDistributionChart.options.scales.y.ticks.font.size = window.innerWidth < 768 ? 10 : 11;
            studentDistributionChart.options.scales.x.ticks.font.size = window.innerWidth < 768 ? 10 : 11;
            studentDistributionChart.options.scales.y.ticks.padding = window.innerWidth < 768 ? 5 : 10;
            studentDistributionChart.options.scales.x.ticks.padding = window.innerWidth < 768 ? 5 : 10;
            studentDistributionChart.resize();
        }
    }

    // Add resize event listener
    window.addEventListener('resize', resizeCharts);
    </script>

    <style>
    .dashboard {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        padding: 20px;
        background-color: #f8fafc;
    }

    .dashboard h2 {
        color: #1e293b;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    /* Dashboard Stats */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.1), 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    }

    .stat-card h3 {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.75rem;
    }

    .stat-value {
        color: #1e40af;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0.5rem 0;
    }

    .stat-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Chart Styles */
    .dashboard-charts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .chart-container {
        background: #ffffff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        min-height: 350px;
    }

    .chart-container h3 {
        color: #1e293b;
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .chart-wrapper {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Recent Activities and Upcoming Events */
    .dashboard-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .recent-activities,
    .upcoming-events {
        background: #ffffff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .recent-activities h3,
    .upcoming-events h3 {
        color: #1e293b;
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .table-responsive {
        overflow-x: auto;
        border-radius: 8px;
    }

    .recent-activities table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .recent-activities th,
    .recent-activities td {
        padding: 0.875rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .recent-activities th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #475569;
    }

    .recent-activities tr:last-child td {
        border-bottom: none;
    }

    .event-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .event-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #3b82f6;
        transition: transform 0.2s ease;
    }

    .event-item:hover {
        transform: translateX(4px);
    }

    .event-checkin {
        border-left-color: #10b981;
    }

    .event-checkout {
        border-left-color: #f59e0b;
    }

    .event-date {
        color: #475569;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .event-title {
        color: #1e40af;
        font-weight: 600;
        margin: 0.25rem 0;
    }

    .event-location {
        color: #64748b;
        font-size: 0.875rem;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .dashboard-charts {
            grid-template-columns: 1fr;
        }
        
        .chart-wrapper {
            height: 350px;
        }
    }

    @media (max-width: 768px) {
        .dashboard {
            padding: 15px;
        }

        .dashboard-stats {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
        }

        .chart-container {
            padding: 1rem;
            min-height: 300px;
        }

        .chart-wrapper {
            height: 250px;
        }

        .chart-container h3 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }

        .dashboard-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .dashboard-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-card {
            padding: 0.875rem;
        }

        .stat-value {
            font-size: 1.25rem;
        }

        .chart-container {
            padding: 0.875rem;
            min-height: 250px;
        }

        .chart-wrapper {
            height: 200px;
        }

        .chart-container h3 {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
    }
    </style>
</div>