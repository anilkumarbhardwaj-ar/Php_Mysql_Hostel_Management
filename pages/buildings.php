<div class="content">
    <h2>Building Management</h2>

    <?php
    // Check if user is admin
    if ($_SESSION['user_role'] != 'admin') {
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        exit;
    }
    ?>

    <div class="actions">
        <button class="btn btn-primary" id="addBuildingBtn">Add New Building</button>
        <button class="btn btn-secondary" id="addFloorBtn">Add New Floor</button>
    </div>

    <div class="buildings-container">
        <?php
        $conn = getDbConnection();
        
        $sql = "SELECT id, name, location FROM buildings ORDER BY name";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($building = $result->fetch_assoc()) {
                echo '<div class="building-card">';
                echo '<div class="building-header">';
                echo '<h3>' . $building['name'] . '</h3>';
                echo '<div class="building-actions">';
                echo '<button class="btn-edit" data-id="' . $building['id'] . '" data-type="building">Edit</button>';
                echo '<button class="btn-delete" data-id="' . $building['id'] . '" data-type="building">Delete</button>';
                echo '</div>';
                echo '</div>';
                
                echo '<p><strong>Location:</strong> ' . $building['location'] . '</p>';
                
                // Get floors for this building
                $floorSql = "SELECT id, name FROM floors WHERE building_id = ? ORDER BY name";
                $floorStmt = $conn->prepare($floorSql);
                $floorStmt->bind_param("i", $building['id']);
                $floorStmt->execute();
                $floorResult = $floorStmt->get_result();
                
                echo '<div class="floors-list">';
                echo '<h4>Floors</h4>';
                
                if ($floorResult->num_rows > 0) {
                    echo '<ul>';
                    while ($floor = $floorResult->fetch_assoc()) {
                        echo '<li>';
                        echo $floor['name'];
                        echo '<div class="floor-actions">';
                        echo '<button class="btn-edit-small" data-id="' . $floor['id'] . '" data-type="floor">Edit</button>';
                        echo '<button class="btn-delete-small" data-id="' . $floor['id'] . '" data-type="floor">Delete</button>';
                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No floors added yet.</p>';
                }
                
                echo '</div>'; // End floors-list
                echo '</div>'; // End building-card
                
                $floorStmt->close();
            }
        } else {
            echo '<p>No buildings found. Add a building to get started.</p>';
        }
        
        closeDbConnection($conn);
        ?>
    </div>

    <!-- Add Building Modal -->
    <div id="addBuildingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Building</h3>

            <form id="addBuildingForm" method="POST">
                <div class="form-group">
                    <label for="buildingName">Building Name</label>
                    <input type="text" id="buildingName" name="buildingName" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Building</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Building Modal -->
    <div id="editBuildingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Building</h3>

            <form id="editBuildingForm" method="POST">
                <input type="hidden" id="editBuildingId" name="buildingId">

                <div class="form-group">
                    <label for="editBuildingName">Building Name</label>
                    <input type="text" id="editBuildingName" name="buildingName" required>
                </div>

                <div class="form-group">
                    <label for="editLocation">Location</label>
                    <input type="text" id="editLocation" name="location" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Building</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Floor Modal -->
    <div id="addFloorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Floor</h3>

            <form id="addFloorForm" method="POST" action="ajax/add_floor.php">
                <div class="form-group">
                    <label for="building">Building</label>
                    <select id="building" name="building" required>
                        <option value="">Select Building</option>
                        <?php
                        $conn = getDbConnection();
                        $result = $conn->query("SELECT id, name FROM buildings ORDER BY name");
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                            }
                        }
                        
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="floorName">Floor Name</label>
                    <input type="text" id="floorName" name="floorName" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Floor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Floor Modal -->
    <div id="editFloorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Floor</h3>

            <form id="editFloorForm" method="POST" action="ajax/edit_floor.php">
                <input type="hidden" id="editFloorId" name="floorId">

                <div class="form-group">
                    <label for="editFloorBuilding">Building</label>
                    <select id="editFloorBuilding" name="building" required>
                        <option value="">Select Building</option>
                        <?php
                        $conn = getDbConnection();
                        $result = $conn->query("SELECT id, name FROM buildings ORDER BY name");
                        
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                            }
                        }
                        
                        closeDbConnection($conn);
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editFloorName">Floor Name</label>
                    <input type="text" id="editFloorName" name="floorName" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Floor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Show/hide add building modal
    const addBuildingBtn = document.getElementById('addBuildingBtn');
    const addBuildingModal = document.getElementById('addBuildingModal');
    const buildingCloseBtn = addBuildingModal.querySelector('.close');

    addBuildingBtn.addEventListener('click', function() {
        addBuildingModal.style.display = 'block';
    });

    buildingCloseBtn.addEventListener('click', function() {
        addBuildingModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addBuildingModal) {
            addBuildingModal.style.display = 'none';
        }
    });

    // Show/hide add floor modal
    const addFloorBtn = document.getElementById('addFloorBtn');
    const addFloorModal = document.getElementById('addFloorModal');
    const floorCloseBtn = addFloorModal.querySelector('.close');

    addFloorBtn.addEventListener('click', function() {
        addFloorModal.style.display = 'block';
    });

    floorCloseBtn.addEventListener('click', function() {
        addFloorModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addFloorModal) {
            addFloorModal.style.display = 'none';
        }
    });

    // Show/hide edit building modal
    const editBuildingModal = document.getElementById('editBuildingModal');
    const editBuildingCloseBtn = editBuildingModal.querySelector('.close');

    editBuildingCloseBtn.addEventListener('click', function() {
        editBuildingModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editBuildingModal) {
            editBuildingModal.style.display = 'none';
        }
    });

    // Show/hide edit floor modal
    const editFloorModal = document.getElementById('editFloorModal');
    const editFloorCloseBtn = editFloorModal.querySelector('.close');

    editFloorCloseBtn.addEventListener('click', function() {
        editFloorModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editFloorModal) {
            editFloorModal.style.display = 'none';
        }
    });

    // Form submission via AJAX - Building
    const addBuildingForm = document.getElementById('addBuildingForm');

    addBuildingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/add_building.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Building added successfully!');
                    addBuildingModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('An error occurred: ' + error.message);
            });
    });

    // Form submission via AJAX - Floor
    const addFloorForm = document.getElementById('addFloorForm');

    addFloorForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/add_floor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Floor added successfully!');
                    addFloorModal.style.display = 'none';
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

    // Edit building functionality
    const editBuildingButtons = document.querySelectorAll('.btn-edit[data-type="building"]');
    const editBuildingForm = document.getElementById('editBuildingForm');

    editBuildingButtons.forEach(button => {
        button.addEventListener('click', function() {
            const buildingId = this.dataset.id;

            // Fetch building details
            fetch('ajax/get_building.php?id=' + buildingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editBuildingId').value = data.building.id;
                        document.getElementById('editBuildingName').value = data.building.name;
                        document.getElementById('editLocation').value = data.building.location;

                        editBuildingModal.style.display = 'block';
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

    editBuildingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/edit_building.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Building updated successfully!');
                    editBuildingModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('An error occurred: ' + error.message);
            });
    });

    // Edit floor functionality
    const editFloorButtons = document.querySelectorAll('.btn-edit-small[data-type="floor"]');
    const editFloorForm = document.getElementById('editFloorForm');

    editFloorButtons.forEach(button => {
        button.addEventListener('click', function() {
            const floorId = this.dataset.id;

            // Fetch floor details
            fetch('ajax/get_floor.php?id=' + floorId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('editFloorId').value = data.floor.id;
                        document.getElementById('editFloorBuilding').value = data.floor.building_id;
                        document.getElementById('editFloorName').value = data.floor.name;

                        editFloorModal.style.display = 'block';
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('An error occurred: ' + error.message);
                });
        });
    });

    editFloorForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('ajax/edit_floor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Floor updated successfully!');
                    editFloorModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('An error occurred: ' + error.message);
            });
    });

    // Delete building or floor
    const deleteButtons = document.querySelectorAll('.btn-delete, .btn-delete-small');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const type = this.dataset.type;
            const confirmMessage =
                `Are you sure you want to delete this ${type}? This action cannot be undone.`;

            if (confirm(confirmMessage)) {
                fetch(`ajax/delete_${type}.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(
                                `${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`
                                );
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