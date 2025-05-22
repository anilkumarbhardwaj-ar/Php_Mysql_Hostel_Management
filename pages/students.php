<div class="content">
    <h2>Student Management</h2>

    <div class="actions">
        <button class="btn btn-primary" id="addStudentBtn">Add New Student</button>

        <div class="search-filter">
            <input type="text" id="searchStudent" placeholder="Search students...">
            <select id="filterCourse">
                <option value="">All Courses</option>
                <?php
                $conn = getDbConnection();
                $result = $conn->query("SELECT id, name FROM courses ORDER BY name");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['name'] . "'>" . $row['name'] . "</option>";
                }
                closeDbConnection($conn);
                ?>
            </select>
        </div>
    </div>

    <div class="data-table">
        <table id="studentsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Room</th>
                    <th>Fee Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = getDbConnection();
                $sql = "
                    SELECT 
                        u.id, u.name, u.email,
                        c.name as course_name,
                        CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room,
                        CASE 
                            WHEN p.status = 'paid' THEN 'Paid'
                            WHEN p.status = 'partial' THEN 'Partial'
                            ELSE 'Unpaid'
                        END as fee_status
                    FROM users u
                    LEFT JOIN student_courses sc ON u.id = sc.student_id
                    LEFT JOIN courses c ON sc.course_id = c.id
                    LEFT JOIN allocations a ON u.id = a.user_id AND a.end_date >= CURDATE()
                    LEFT JOIN rooms r ON a.room_id = r.id
                    LEFT JOIN floors f ON r.floor_id = f.id
                    LEFT JOIN buildings b ON f.building_id = b.id
                    LEFT JOIN payments p ON u.id = p.user_id
                    WHERE u.role = 'student'
                    ORDER BY u.name
                ";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td>{$row['email']}</td>";
                        echo "<td>{$row['course_name']}</td>";
                        echo "<td>{$row['room']}</td>";
                        echo "<td>{$row['fee_status']}</td>";
                        echo "<td class='actions'>
                                <button class='btn-edit' data-id='{$row['id']}'>Edit</button>
                                <button class='btn-delete' data-id='{$row['id']}'>Delete</button>
                                <button class='btn-view' data-id='{$row['id']}'>View</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No students found</td></tr>";
                }
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Student</h3>
            <form id="addStudentForm" method="POST" action="ajax/add_student.php">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" required>
                        <option value="">Select Course</option>
                        <?php
                        $conn = getDbConnection();
                        $result = $conn->query("SELECT id, name FROM courses ORDER BY name");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select name="room" required>
                        <option value="">Select Room</option>
                        <?php
                        $conn = getDbConnection();
                        $sql = "
                            SELECT r.id, CONCAT(b.name, ' - ', f.name, ' - ', r.room_number) as room_name
                            FROM rooms r
                            JOIN floors f ON r.floor_id = f.id
                            JOIN buildings b ON f.building_id = b.id
                            WHERE (SELECT COUNT(*) FROM allocations WHERE room_id = r.id AND end_date >= CURDATE()) < r.capacity
                            ORDER BY b.name, f.name, r.room_number
                        ";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['room_name']}</option>";
                        }
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="startDate" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="endDate" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Student</h3>
            <form id="editStudentForm" method="POST" action="ajax/update_student.php">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" id="edit_course" required>
                        <?php
                        $conn = getDbConnection();
                        $result = $conn->query("SELECT id, name FROM courses ORDER BY name");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Student Modal -->
    <div id="viewStudentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Student Details</h3>
            <div id="studentDetails">
                <!-- Student details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addStudentBtn').onclick = () => {
    document.getElementById('addStudentModal').style.display = 'block';
};
document.querySelectorAll('.modal .close').forEach(btn => {
    btn.onclick = () => btn.closest('.modal').style.display = 'none';
});
window.onclick = (event) => {
    document.querySelectorAll('.modal').forEach(modal => {
        if (event.target == modal) modal.style.display = 'none';
    });
};

document.getElementById('addStudentForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Student added successfully');
                form.reset();
                document.getElementById('addStudentModal').style.display = 'none';
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
};

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.onclick = () => {
        const id = btn.dataset.id;
        fetch(`ajax/get_student.php?id=${id}`, {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.text();
            })
            .then(html => {
                document.querySelector('#editStudentForm').innerHTML = html;
                document.getElementById('editStudentModal').style.display = 'block';
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
    };
});

document.getElementById('editStudentForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('ajax/update_student.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Student updated successfully');
                document.getElementById('editStudentModal').style.display = 'none';
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => alert('Error: ' + err.message));
};

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.onclick = () => {
        if (confirm("Are you sure you want to delete this student?")) {
            fetch('ajax/delete_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${btn.dataset.id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Student deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    alert('Error: ' + err.message);
                });
        }
    };
});

// Implement View Student functionality
document.querySelectorAll('.btn-view').forEach(btn => {
    btn.onclick = () => {
        const id = btn.dataset.id;
        fetch(`ajax/view_student.php?id=${id}`, {
                method: 'GET',
                credentials: 'same-origin'
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.text();
            })
            .then(html => {
                document.getElementById('studentDetails').innerHTML = html;
                document.getElementById('viewStudentModal').style.display = 'block';
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
    };
});

document.getElementById('searchStudent').addEventListener('input', filterTable);
document.getElementById('filterCourse').addEventListener('change', filterTable);

function filterTable() {
    const search = document.getElementById('searchStudent').value.toLowerCase();
    const course = document.getElementById('filterCourse').value;
    const rows = document.querySelectorAll('#studentsTable tbody tr');
    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        const courseName = row.cells[3].textContent;
        const matchesSearch = name.includes(search) || email.includes(search);
        const matchesCourse = course === "" || courseName === course;
        row.style.display = (matchesSearch && matchesCourse) ? '' : 'none';
    });
}
</script>

<style>
/* Add some styling for the student details view */
.student-details {
    padding: 15px;
}

.detail-row {
    display: flex;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.detail-label {
    font-weight: bold;
    width: 120px;
}

.detail-value {
    flex: 1;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.85em;
}

.status-paid {
    background-color: #d4edda;
    color: #155724;
}

.status-partial {
    background-color: #fff3cd;
    color: #856404;
}

.status-unpaid {
    background-color: #f8d7da;
    color: #721c24;
}
</style>