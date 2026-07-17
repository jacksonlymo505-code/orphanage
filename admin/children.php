<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $date_of_birth = $_POST['date_of_birth'];
                $gender = $_POST['gender'];
                $health_status = $_POST['health_status'];
                $guardian_id = !empty($_POST['guardian_id']) ? $_POST['guardian_id'] : null;
                
                $sql = "INSERT INTO children (first_name, last_name, date_of_birth, gender, health_status, guardian_id) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $first_name, $last_name, $date_of_birth, $gender, $health_status, $guardian_id);
                $stmt->execute();
                break;

            case 'edit':
                $id = $_POST['id'];
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $date_of_birth = $_POST['date_of_birth'];
                $gender = $_POST['gender'];
                $health_status = $_POST['health_status'];
                $guardian_id = !empty($_POST['guardian_id']) ? $_POST['guardian_id'] : null;
                
                $sql = "UPDATE children SET first_name=?, last_name=?, date_of_birth=?, gender=?, health_status=?, guardian_id=? 
                        WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssii", $first_name, $last_name, $date_of_birth, $gender, $health_status, $guardian_id, $id);
                $stmt->execute();
                break;

            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM children WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
        }
    }
}

// Fetch all children
$sql = "SELECT c.*, CONCAT(g.first_name, ' ', g.last_name) as guardian_name 
        FROM children c 
        LEFT JOIN guardians g ON c.guardian_id = g.id 
        ORDER BY c.first_name, c.last_name";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Children Management - Orphanage Management System</title>
    
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #d185ab;
            --danger-color: #ebe8f0;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .menu-item.active {
            background: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px; /* Same as sidebar width */
            padding: 20px;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .children-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .add-child-btn {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .add-child-btn:hover {
            background: #2980b9;
        }

        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }

        .child-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .child-header {
            padding: 15px;
            background: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .child-body {
            padding: 15px;
        }

        .child-info {
            margin-bottom: 10px;
        }

        .child-info label {
            font-weight: 500;
            color: #666;
        }

        .child-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }

        .edit-btn { background: var(--danger-color); }
        .delete-btn { background: var(--warning-color); }
        .view-btn { background: var(--success-color); }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            border-radius: 10px;
            padding: 20px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .submit-btn {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-container {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        #searchInput {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        #searchFilter {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- ... existing sidebar ... -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>OMS Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="children.php" class="menu-item">
                <i class="fas fa-child"></i> Children
            </a>
            <a href="guardians.php" class="menu-item">
                <i class="fas fa-user-shield"></i> Guardians
            </a>
            <a href="donors.php" class="menu-item">
                <i class="fas fa-hand-holding-heart"></i> Donors
            </a>
            <a href="adoptions.php" class="menu-item">
                <i class="fas fa-baby"></i> Adoptions
            </a>
            <a href="donations_history.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Donations
            </a>
            <a href="manage_opportunities.php" class="menu-item">
                <i class="fas fa-handshake"></i> Opportunities
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="children-container">
            <div class="page-header">
                <h1>Children Management</h1>
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search by name, age, gender, or health status...">
                    <select id="searchFilter">
                        <option value="all">All Fields</option>
                        <option value="name">Name</option>
                        <option value="age">Age</option>
                        <option value="gender">Gender</option>
                        <option value="health">Health Status</option>
                    </select>
                </div>
                <button class="add-child-btn" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Add New Child
                </button>
            </div>

            <div class="children-grid">
                <?php while ($child = $result->fetch_assoc()): ?>
                    <div class="child-card">
                        <div class="child-header">
                            <h3><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h3>
                            <div class="child-actions">
                                <button class="action-btn edit-btn" onclick="openModal('edit', <?php echo $child['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteChild(<?php echo $child['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="child-body">
                            <div class="child-info">
                                <label>Age:</label>
                                <span><?php 
                                    $dob = new DateTime($child['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $dob->diff($today)->y;
                                    echo $age;
                                ?></span>
                            </div>
                            <div class="child-info">
                                <label>Gender:</label>
                                <span><?php echo $child['gender']; ?></span>
                            </div>
                            <div class="child-info">
                                <label>Health Status:</label>
                                <span><?php echo htmlspecialchars($child['health_status']); ?></span>
                            </div>
                            <div class="child-info">
                                <label>Guardian:</label>
                                <span><?php echo htmlspecialchars(isset($child['guardian_name']) ? $child['guardian_name'] : 'None'); ?></span>
                            </div>
                            <a href="child_details.php?id=<?php echo $child['id']; ?>" class="action-btn view-btn">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Child Modal -->
    <div id="childModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Child</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="childForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="childId">
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="health_status">Health Status</label>
                    <textarea id="health_status" name="health_status" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="guardian_id">Guardian</label>
                    <select id="guardian_id" name="guardian_id">
                        <option value="">Select Guardian</option>
                        <?php
                        $guardians = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM guardians ORDER BY first_name, last_name");
                        while ($guardian = $guardians->fetch_assoc()) {
                            echo "<option value='{$guardian['id']}'>{$guardian['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">Save Child</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, id = null) {
            const modal = document.getElementById('childModal');
            const form = document.getElementById('childForm');
            const title = document.getElementById('modalTitle');
            
            form.reset();
            document.getElementById('formAction').value = action;
            
            if (action === 'edit' && id) {
                title.textContent = 'Edit Child';
                document.getElementById('childId').value = id;
                
                // Fetch child data and populate form
                fetch(`get_child.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('first_name').value = data.first_name;
                        document.getElementById('last_name').value = data.last_name;
                        document.getElementById('date_of_birth').value = data.date_of_birth;
                        document.getElementById('gender').value = data.gender;
                        document.getElementById('health_status').value = data.health_status || '';
                        document.getElementById('guardian_id').value = data.guardian_id || '';
                    })
                    .catch(error => {
                        console.error('Error fetching child data:', error);
                        alert('Error loading child data. Please try again.');
                    });
            } else {
                title.textContent = 'Add New Child';
                document.getElementById('childId').value = '';
            }
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('childModal').style.display = 'none';
        }

        function deleteChild(id) {
            if (confirm('Are you sure you want to delete this child?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('childModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Add form submission handling
        document.getElementById('childForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving child data. Please try again.');
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterChildren);
        document.getElementById('searchFilter').addEventListener('change', filterChildren);

        function filterChildren() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterType = document.getElementById('searchFilter').value;
            const childCards = document.querySelectorAll('.child-card');

            childCards.forEach(card => {
                const name = card.querySelector('.child-header h3').textContent.toLowerCase();
                const age = card.querySelector('.child-info:nth-child(1) span').textContent;
                const gender = card.querySelector('.child-info:nth-child(2) span').textContent.toLowerCase();
                const health = card.querySelector('.child-info:nth-child(3) span').textContent.toLowerCase();

                let show = false;

                switch(filterType) {
                    case 'name':
                        show = name.includes(searchTerm);
                        break;
                    case 'age':
                        show = age.includes(searchTerm);
                        break;
                    case 'gender':
                        show = gender.includes(searchTerm);
                        break;
                    case 'health':
                        show = health.includes(searchTerm);
                        break;
                    default:
                        show = name.includes(searchTerm) || 
                               age.includes(searchTerm) || 
                               gender.includes(searchTerm) || 
                               health.includes(searchTerm);
                }

                card.style.display = show ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
