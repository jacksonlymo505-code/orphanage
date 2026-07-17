<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get currency
$currency = get_currency();

// Function to handle image uploads
function uploadImage($file) {
    $target_dir = "../images/";
    
    // Create the directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return [
            'success' => false,
            'message' => "File is not an image."
        ];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return [
            'success' => false,
            'message' => "Sorry, your file is too large. Max size is 5MB."
        ];
    }
    
    // Allow certain file formats
    if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" && $file_extension != "jfif") {
        return [
            'success' => false,
            'message' => "Sorry, only JPG, JPEG, PNG, JFIF & GIF files are allowed."
        ];
    }
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'message' => "Sorry, there was an error uploading your file."
        ];
    }
}

// Handle form submission for adding new opportunity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $category = $_POST['category'];
            $target_amount = $_POST['target_amount'];
            $deadline = $_POST['deadline'];
            $status = $_POST['status'];
            $orphanage_id = 1; // Default orphanage ID
            $image_url = null;
            
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $upload_result = uploadImage($_FILES['image']);
                if ($upload_result['success']) {
                    $image_url = "../images/" . $upload_result['filename'];
                } else {
                    $error_message = $upload_result['message'];
                }
            }
            
            if (!isset($error_message)) {
                $sql = "INSERT INTO opportunities (orphanage_id, title, description, category, target_amount, deadline, status, image_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssdsss", $orphanage_id, $title, $description, $category, $target_amount, $deadline, $status, $image_url);
                
                if ($stmt->execute()) {
                    $success_message = "Opportunity added successfully!";
                } else {
                    $error_message = "Error adding opportunity: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $category = $_POST['category'];
            $target_amount = $_POST['target_amount'];
            $deadline = $_POST['deadline'];
            $status = $_POST['status'];
            
            // Handle image upload if present
            if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
                $upload_result = uploadImage($_FILES['edit_image']);
                if ($upload_result['success']) {
                    $image_url = "../images/" . $upload_result['filename'];
                    
                    $sql = "UPDATE opportunities SET 
                            title = ?, 
                            description = ?, 
                            category = ?, 
                            target_amount = ?, 
                            deadline = ?, 
                            status = ?,
                            image_url = ?
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssdssis", $title, $description, $category, $target_amount, $deadline, $status, $image_url, $id);
                } else {
                    $error_message = $upload_result['message'];
                }
            } else {
                $sql = "UPDATE opportunities SET 
                        title = ?, 
                        description = ?, 
                        category = ?, 
                        target_amount = ?, 
                        deadline = ?, 
                        status = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdsi", $title, $description, $category, $target_amount, $deadline, $status, $id);
            }
            
            if (!isset($error_message) && $stmt->execute()) {
                $success_message = "Opportunity updated successfully!";
            } else if (!isset($error_message)) {
                $error_message = "Error updating opportunity: " . $conn->error;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            $sql = "DELETE FROM opportunities WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Opportunity deleted successfully!";
            } else {
                $error_message = "Error deleting opportunity: " . $conn->error;
            }
        }
    }
}

// Fetch all opportunities
$opportunities = [];
$sql = "SELECT * FROM opportunities ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = $row;
    }
}

// Add this function to fetch opportunity details for editing
function getOpportunityById($conn, $id) {
    $sql = "SELECT * FROM opportunities WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Opportunities - Orphanage Management System</title>
   
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
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

        /* Add specific styles for opportunities page */
        .opportunities-container {
            padding: 20px;
        }

        .add-opportunity-btn {
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .opportunities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .opportunity-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .opportunity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .opportunity-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .opportunity-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .status-active {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-closed {
            background: #ffebee;
            color: #f44336;
        }

        .opportunity-details {
            margin-bottom: 15px;
        }

        .opportunity-details p {
            margin: 5px 0;
            color: #666;
        }

        .opportunity-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .edit-btn {
            background: var(--secondary-color);
            color: white;
        }

        .delete-btn {
            background: var(--danger-color);
            color: white;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            overflow-y: auto; /* Enable vertical scrolling */
            padding: 20px 0; /* Add padding for better spacing */
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 20px auto; /* Changed from 50px to 20px for better spacing */
            padding: 20px;
            border-radius: 10px;
            position: relative; /* Ensure proper stacking context */
            max-height: calc(100vh - 40px); /* Limit height to viewport minus padding */
            overflow-y: auto; /* Enable scrolling within modal content */
            scroll-behavior: smooth;
        }

        .modal-header {
            position: sticky; /* Keep header visible while scrolling */
            top: 0;
            background: white;
            padding: 10px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            z-index: 1;
        }

        .close-btn {
            cursor: pointer;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px; /* Increased spacing between form groups */
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group textarea {
            min-height: 100px; /* Minimum height for textarea */
            max-height: 200px; /* Maximum height before scrolling */
            resize: vertical;
        }

        .submit-btn {
            position: sticky; /* Keep submit button visible while scrolling */
            bottom: 0;
            background: var(--success-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            z-index: 1;
        }

        .submit-btn:hover {
            background: #27ae60;
        }

        .opportunity-card img {
            margin-top: 10px;
            border-radius: 5px;
        }

        .form-group input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background: #f5f6fa;
        }

        .opportunities-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .opportunities-container h1 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .opportunities-container h1::before {
            content: '\f0c0';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: var(--secondary-color);
        }

        .add-opportunity-btn {
            background: var(--success-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-opportunity-btn:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

        .add-opportunity-btn i {
            font-size: 16px;
        }

        .opportunities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .opportunity-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .opportunity-card:hover {
            transform: translateY(-5px);
        }

        .opportunity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .opportunity-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .opportunity-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-completed {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .opportunity-details {
            margin-bottom: 20px;
        }

        .opportunity-details p {
            margin: 10px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .opportunity-details p strong {
            color: var(--dark-color);
            min-width: 120px;
        }

        .opportunity-details p i {
            color: var(--secondary-color);
            width: 16px;
        }

        .opportunity-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: var(--secondary-color);
            color: white;
        }

        .edit-btn:hover {
            background: #2980b9;
        }

        .delete-btn {
            background: var(--danger-color);
            color: white;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .alert i {
            font-size: 18px;
        }

        /* Improve form field focus states */
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
        <div class="opportunities-container">
            <h1>Manage Opportunities</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <button class="add-opportunity-btn" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Opportunity
            </button>

            <div class="opportunities-grid">
                <?php foreach ($opportunities as $opportunity): ?>
                    <div class="opportunity-card">
                        <div class="opportunity-header">
                            <h3 class="opportunity-title"><?php echo htmlspecialchars($opportunity['title']); ?></h3>
                            <span class="opportunity-status status-<?php echo strtolower($opportunity['status']); ?>">
                                <?php echo htmlspecialchars($opportunity['status']); ?>
                            </span>
                        </div>
                        <div class="opportunity-details">
                            <p>
                                <strong>Category:</strong>
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($opportunity['category']); ?>
                            </p>
                            <p>
                                <strong>Target Amount:</strong>
                                <i class="fas fa-coins"></i>
                                TSh <?php echo number_format($opportunity['target_amount'], 2); ?>
                            </p>
                            <p>
                                <strong>Current Amount:</strong>
                                <i class="fas fa-coins"></i>
                                TSh <?php echo number_format($opportunity['current_amount'], 2); ?>
                            </p>
                            <p>
                                <strong>Deadline:</strong>
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M d, Y', strtotime($opportunity['deadline'])); ?>
                            </p>
                            <?php if (!empty($opportunity['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($opportunity['image_url']); ?>" alt="Opportunity Image" style="max-width: 100%; height: auto; border-radius: 5px; margin-top: 10px;">
                            <?php endif; ?>
                        </div>
                        <div class="opportunity-actions">
                            <button class="action-btn edit-btn" onclick="editOpportunity(<?php echo $opportunity['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteOpportunity(<?php echo $opportunity['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Opportunity Modal -->
    <div id="opportunityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Opportunity</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select category</option>
                        <option value="Education">Education</option>
                        <option value="Healthcare">Healthcare</option>
                        <option value="Food">Food</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                                            <label for="target_amount">Target Amount (TSh)</label>
                    <input type="number" id="target_amount" name="target_amount" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image (Optional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <button type="submit" class="submit-btn">Add Opportunity</button>
            </form>
        </div>
    </div>

    <!-- Add Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Opportunity</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <option value="Education">Education</option>
                        <option value="Healthcare">Healthcare</option>
                        <option value="Food">Food</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                                            <label for="edit_target_amount">Target Amount (TSh)</label>
                    <input type="number" id="edit_target_amount" name="target_amount" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="edit_deadline">Deadline</label>
                    <input type="date" id="edit_deadline" name="deadline" required>
                </div>

                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_image">Update Image (Optional)</label>
                    <input type="file" id="edit_image" name="edit_image" accept="image/*">
                    <div id="current_image_container" style="margin-top: 10px; display: none;">
                        <p>Current Image:</p>
                        <img id="current_image" src="" alt="Current Image" style="max-width: 100%; max-height: 200px;">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Update Opportunity</button>
            </form>
        </div>
    </div>

    <!-- Add Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to delete this opportunity?</p>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="delete-btn">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("opportunityModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("opportunityModal").style.display = "none";
        }

        function editOpportunity(id) {
            // Fetch opportunity details using AJAX
            fetch(`get_opportunity.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_title').value = data.title;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_category').value = data.category;
                    document.getElementById('edit_target_amount').value = data.target_amount;
                    document.getElementById('edit_deadline').value = data.deadline;
                    document.getElementById('edit_status').value = data.status;
                    
                    // Show current image if available
                    if (data.image_url) {
                        document.getElementById('current_image').src = data.image_url;
                        document.getElementById('current_image_container').style.display = 'block';
                    } else {
                        document.getElementById('current_image_container').style.display = 'none';
                    }
                    
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteOpportunity(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>

    <style>
        /* Add these new styles */
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .cancel-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background: #f5f5f5;
        }

        #deleteModal .modal-content {
            max-width: 400px;
        }

        #deleteModal p {
            margin: 20px 0;
            color: #666;
        }
    </style>
</body>
</html> 