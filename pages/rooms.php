<div class="content">
    <h2>Room Management</h2>

    <div class="actions">
        <button class="btn btn-primary" id="addRoomBtn">Add New Room</button>

        <div class="search-filter">
            <input type="text" id="searchRoom" placeholder="Search rooms...">
            <select id="filterBuilding">
                <option value="">All Buildings</option>
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
            <select id="filterStatus">
                <option value="">All Status</option>
                <option value="vacant">Vacant</option>
                <option value="occupied">Occupied</option>
                <option value="full">Full</option>
            </select>
        </div>
    </div>

    <div class="room-grid">
        <?php
        $conn = getDbConnection();
        
        $sql = "
            SELECT 
                r.id,
                r.room_number,
                r.capacity,
                r.type,
                r.description,
                f.id as floor_id,
                b.id as building_id,
                b.name as building_name,
                f.name as floor_name,
                COUNT(DISTINCT a.user_id) as occupied
            FROM 
                rooms r
            JOIN 
                floors f ON r.floor_id = f.id
            JOIN 
                buildings b ON f.building_id = b.id
            LEFT JOIN 
                allocations a ON r.id = a.room_id AND a.end_date >= CURDATE()
            GROUP BY 
                r.id, r.room_number, r.capacity, r.type, r.description, f.id, b.id, b.name, f.name
            ORDER BY 
                b.name, f.name, r.room_number
        ";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $status = 'vacant';
                if ($row['occupied'] >= $row['capacity']) {
                    $status = 'full';
                } else if ($row['occupied'] > 0) {
                    $status = 'occupied';
                }
                
                echo "<div class='room-card' data-building='" . $row['building_id'] . "' data-status='" . $status . "'>";
                echo "<div class='room-header'>";
                echo "<h3>" . $row['room_number'] . "</h3>";
                echo "<span class='room-type'>" . $row['type'] . "</span>";
                echo "</div>";
                echo "<div class='room-details'>";
                echo "<p><strong>Building:</strong> " . $row['building_name'] . "</p>";
                echo "<p><strong>Floor:</strong> " . $row['floor_name'] . "</p>";
                echo "<p><strong>Capacity:</strong> " . $row['occupied'] . "/" . $row['capacity'] . "</p>";
                echo "<p><strong>Status:</strong> <span class='status-" . $status . "'>" . ucfirst($status) . "</span></p>";
                if (!empty($row['description'])) {
                    echo "<p><strong>Description:</strong> " . $row['description'] . "</p>";
                }
                echo "</div>";
                echo "<div class='room-actions'>";
                echo "<button class='btn-edit' data-id='" . $row['id'] . "'>Edit</button>";
                echo "<button class='btn-delete' data-id='" . $row['id'] . "'>Delete</button>";
                echo "<a href='view_room.php?id=" . $row['id'] . "' class='btn-view'>View</a>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p>No rooms found</p>";
        }
        
        closeDbConnection($conn);
        ?>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Add New Room</h3>

            <form id="addRoomForm" method="POST" action="ajax/add_room.php">
                <div class="form-group">
                    <label for="roomNumber">Room Number</label>
                    <input type="text" id="roomNumber" name="roomNumber" required>
                </div>

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
                    <label for="floor">Floor</label>
                    <select id="floor" name="floor" required>
                        <option value="">Select Floor</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Room Type</label>
                    <select id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="AC">AC</option>
                        <option value="Non-AC">Non-AC</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" min="1" required>
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Room</h3>

            <form id="editRoomForm" method="POST" action="ajax/update_room.php">
                <input type="hidden" id="editRoomId" name="roomId">

                <div class="form-group">
                    <label for="editRoomNumber">Room Number</label>
                    <input type="text" id="editRoomNumber" name="roomNumber" required>
                </div>

                <div class="form-group">
                    <label for="editBuilding">Building</label>
                    <select id="editBuilding" name="building" required>
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
                    <label for="editFloor">Floor</label>
                    <select id="editFloor" name="floor" required>
                        <option value="">Select Floor</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editType">Room Type</label>
                    <select id="editType" name="type" required>
                        <option value="">Select Type</option>
                        <option value="AC">AC</option>
                        <option value="Non-AC">Non-AC</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editCapacity">Capacity</label>
                    <input type="number" id="editCapacity" name="capacity" min="1" required>
                </div>

                <div class="form-group">
                    <label for="editDescription">Description (Optional)</label>
                    <textarea id="editDescription" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Show/hide add room modal
    const addRoomBtn = document.getElementById('addRoomBtn');
    const addRoomModal = document.getElementById('addRoomModal');
    const closeBtn = addRoomModal.querySelector('.close');

    addRoomBtn.addEventListener('click', function() {
        addRoomModal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        addRoomModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == addRoomModal) {
            addRoomModal.style.display = 'none';
        }
    });

    // Show/hide edit room modal
    const editRoomModal = document.getElementById('editRoomModal');
    const editCloseBtn = editRoomModal.querySelector('.close');

    editCloseBtn.addEventListener('click', function() {
        editRoomModal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == editRoomModal) {
            editRoomModal.style.display = 'none';
        }
    });

    // Search and filter functionality
    const searchInput = document.getElementById('searchRoom');
    const filterBuilding = document.getElementById('filterBuilding');
    const filterStatus = document.getElementById('filterStatus');
    const roomCards = document.querySelectorAll('.room-card');

    function filterRooms() {
        const searchValue = searchInput.value.toLowerCase();
        const buildingValue = filterBuilding.value;
        const statusValue = filterStatus.value;

        roomCards.forEach(card => {
            const roomNumber = card.querySelector('h3').textContent.toLowerCase();
            const building = card.dataset.building;
            const status = card.dataset.status;

            const numberMatch = roomNumber.includes(searchValue);
            const buildingMatch = buildingValue === '' || building === buildingValue;
            const statusMatch = statusValue === '' || status === statusValue;

            if (numberMatch && buildingMatch && statusMatch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('keyup', filterRooms);
    filterBuilding.addEventListener('change', filterRooms);
    filterStatus.addEventListener('change', filterRooms);

    // Dynamic floor loading based on building selection for add room
    const buildingSelect = document.getElementById('building');
    const floorSelect = document.getElementById('floor');

    // Function to load floors for a building
    function loadFloors(buildingId, targetSelect, selectedFloorId = null) {
        // Clear floor select
        targetSelect.innerHTML = '<option value="">Select Floor</option>';

        if (!buildingId) return;

        // Show loading indicator
        const loadingOption = document.createElement('option');
        loadingOption.textContent = 'Loading floors...';
        loadingOption.disabled = true;
        targetSelect.appendChild(loadingOption);

        // Fetch floors for selected building
        fetch('ajax/get_floors.php?building_id=' + buildingId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // Remove loading indicator
                if (targetSelect.contains(loadingOption)) {
                    targetSelect.removeChild(loadingOption);
                }

                if (data.success) {
                    if (data.floors.length === 0) {
                        const noFloorsOption = document.createElement('option');
                        noFloorsOption.textContent = 'No floors available';
                        noFloorsOption.disabled = true;
                        targetSelect.appendChild(noFloorsOption);
                    } else {
                        data.floors.forEach(floor => {
                            const option = document.createElement('option');
                            option.value = floor.id;
                            option.textContent = floor.name;
                            targetSelect.appendChild(option);
                        });

                        // Set selected floor if provided
                        if (selectedFloorId) {
                            targetSelect.value = selectedFloorId;
                        }
                    }
                } else {
                    console.error('Error loading floors:', data.message);
                    alert('Error loading floors: ' + data.message);
                }
            })
            .catch(error => {
                // Remove loading indicator
                if (targetSelect.contains(loadingOption)) {
                    targetSelect.removeChild(loadingOption);
                }

                console.error('Error fetching floors:', error);
                const errorOption = document.createElement('option');
                errorOption.textContent = 'Error loading floors';
                errorOption.disabled = true;
                targetSelect.appendChild(errorOption);
            });
    }

    // Add event listener for building select change
    buildingSelect.addEventListener('change', function() {
        loadFloors(this.value, floorSelect);
    });

    // Dynamic floor loading based on building selection for edit room
    const editBuildingSelect = document.getElementById('editBuilding');
    const editFloorSelect = document.getElementById('editFloor');

    // Add event listener for edit building select change
    editBuildingSelect.addEventListener('change', function() {
        loadFloors(this.value, editFloorSelect);
    });

    // Form submission via AJAX - Add Room
    const addRoomForm = document.getElementById('addRoomForm');

    addRoomForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate that a floor is selected
        if (!floorSelect.value) {
            alert('Please select a floor');
            return;
        }

        const formData = new FormData(this);

        fetch('ajax/add_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Room added successfully!');
                    addRoomModal.style.display = 'none';
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

    // Edit room functionality
    const editRoomButtons = document.querySelectorAll('.btn-edit');
    const editRoomForm = document.getElementById('editRoomForm');

    editRoomButtons.forEach(button => {
        button.addEventListener('click', function() {
            const roomId = this.dataset.id;

            // Fetch room details
            fetch('ajax/get_room.php?id=' + roomId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const room = data.room;

                        document.getElementById('editRoomId').value = room.id;
                        document.getElementById('editRoomNumber').value = room.room_number;
                        document.getElementById('editBuilding').value = room.building_id;
                        document.getElementById('editType').value = room.type;
                        document.getElementById('editCapacity').value = room.capacity;
                        document.getElementById('editDescription').value = room.description || '';

                        // Load floors for the selected building and set the current floor
                        loadFloors(room.building_id, editFloorSelect, room.floor_id);

                        editRoomModal.style.display = 'block';
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

    // Form submission via AJAX - Edit Room
    editRoomForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate that a floor is selected
        if (!editFloorSelect.value) {
            alert('Please select a floor');
            return;
        }

        const formData = new FormData(this);

        fetch('ajax/update_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Room updated successfully!');
                    editRoomModal.style.display = 'none';
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

    // Delete room functionality
    const deleteRoomButtons = document.querySelectorAll('.btn-delete');

    deleteRoomButtons.forEach(button => {
        button.addEventListener('click', function() {
            const roomId = this.dataset.id;
            const confirmMessage =
                'Are you sure you want to delete this room? This action cannot be undone.';

            if (confirm(confirmMessage)) {
                fetch('ajax/delete_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: roomId
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Room deleted successfully!');
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

<?php include 'includes/footer.php'; ?>