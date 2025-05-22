<div class="content">
    <h2>Course Management</h2>

    <div class="actions">
        <button class="btn btn-primary" id="addCourseBtn">Add New Course</button>

        <div class="search-filter">
            <input type="text" id="searchCourse" placeholder="Search courses...">
        </div>
    </div>

    <div class="data-table">
        <table id="coursesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = getDbConnection();
                
                $sql = "
                    SELECT 
                        c.id,
                        c.name,
                        c.duration,
                        COUNT(sc.student_id) as student_count
                    FROM 
                        courses c
                    LEFT JOIN 
                        student_courses sc ON c.id = sc.course_id
                    GROUP BY 
                        c.id
                    ORDER BY 
                        c.name
                ";
                
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['duration'] . " months</td>";
                        echo "<td>" . $row['student_count'] . "</td>";
                        echo "<td class='actions'>";
                        echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Edit</button>";
                        echo "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>";
                        echo "<button class='btn-view' data-id='" . $row['id'] . "'>View Students</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No courses found</td></tr>";
                }
                
                closeDbConnection($conn);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Course</h3>

            <form id="addCourseForm" method="POST" action="ajax/add_course.php">
                <div class="form-group">
                    <label for="name">Course Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (months)</label>
                    <input type="number" id="duration" name="duration" min="1" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Course</h3>

            <form id="editCourseForm" method="POST" action="ajax/edit_course.php">
                <input type="hidden" id="edit_id" name="id">

                <div class="form-group">
                    <label for="edit_name">Course Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_duration">Duration (months)</label>
                    <input type="number" id="edit_duration" name="duration" min="1" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Students Modal -->
    <div id="viewStudentsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Students Enrolled in <span id="courseName"></span></h3>

            <div class="data-table">
                <table id="courseStudentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody id="courseStudentsBody">
                        <!-- Students will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Show/hide add course modal
    const addCourseBtn = document.getElementById('addCourseBtn');
    const addCourseModal = document.getElementById('addCourseModal');
    const closeBtn = addCourseModal.querySelector('.close');

    addCourseBtn.addEventListener('click', function() {
        addCourseModal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        addCourseModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addCourseModal) {
            addCourseModal.style.display = 'none';
        }
    });

    // Search functionality
    const searchInput = document.getElementById('searchCourse');
    const table = document.getElementById('coursesTable');
    const rows = table.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const name = row.cells[1].textContent.toLowerCase();

            if (name.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Form submission via AJAX
    const addCourseForm = document.getElementById('addCourseForm');

    addCourseForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/add_course.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Course added successfully!');
                    addCourseModal.style.display = 'none';
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

    // Edit course functionality
    const editButtons = document.querySelectorAll('.btn-edit');
    const editCourseModal = document.getElementById('editCourseModal');
    const editCloseBtn = editCourseModal.querySelector('.close');
    const editCourseForm = document.getElementById('editCourseForm');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.dataset.id;

            // Fetch course details
            fetch('ajax/get_course.php?id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.course.id;
                        document.getElementById('edit_name').value = data.course.name;
                        document.getElementById('edit_duration').value = data.course.duration;
                        document.getElementById('edit_description').value = data.course.description;

                        editCourseModal.style.display = 'block';
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

    editCloseBtn.addEventListener('click', function() {
        editCourseModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editCourseModal) {
            editCourseModal.style.display = 'none';
        }
    });

    editCourseForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/edit_course.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Course updated successfully!');
                    editCourseModal.style.display = 'none';
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

    // Delete course functionality
    const deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.dataset.id;
            const courseName = this.closest('tr').cells[1].textContent;

            if (confirm(`Are you sure you want to delete the course "${courseName}"?`)) {
                const formData = new FormData();
                formData.append('id', courseId);

                fetch('ajax/delete_course.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Course deleted successfully!');
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

    // View students in course
    const viewButtons = document.querySelectorAll('.btn-view');
    const viewStudentsModal = document.getElementById('viewStudentsModal');
    const viewCloseBtn = viewStudentsModal.querySelector('.close');
    const courseName = document.getElementById('courseName');
    const courseStudentsBody = document.getElementById('courseStudentsBody');

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.dataset.id;
            const courseName = this.closest('tr').cells[1].textContent;

            document.getElementById('courseName').textContent = courseName;

            // Fetch students for this course
            fetch('ajax/get_course_students.php?course_id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    courseStudentsBody.innerHTML = '';

                    if (data.success && data.students.length > 0) {
                        data.students.forEach(student => {
                            const row = document.createElement('tr');

                            row.innerHTML = `
                                <td>${student.id}</td>
                                <td>${student.name}</td>
                                <td>${student.email}</td>
                                <td>${student.room || 'Not assigned'}</td>
                            `;

                            courseStudentsBody.appendChild(row);
                        });
                    } else {
                        courseStudentsBody.innerHTML =
                            '<tr><td colspan="4">No students enrolled in this course</td></tr>';
                    }

                    viewStudentsModal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });
    });

    viewCloseBtn.addEventListener('click', function() {
        viewStudentsModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == viewStudentsModal) {
            viewStudentsModal.style.display = 'none';
        }
    });
    </script>
</div>