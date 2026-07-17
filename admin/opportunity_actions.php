<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Handle different actions
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'add':
        addOpportunity();
        break;
    case 'edit':
        editOpportunity();
        break;
    case 'delete':
        deleteOpportunity();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        exit();
}

function addOpportunity() {
    global $conn;
    
    // Validate required fields
    $required_fields = ['title', 'description', 'category', 'target_amount'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['error' => "Missing required field: $field"]);
            exit();
        }
    }

    // Prepare data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $target_amount = (float)$_POST['target_amount'];
    $status = 'open';
    $image_url = isset($_POST['image_url']) ? $_POST['image_url'] : null;

    // Insert new opportunity
    $query = "INSERT INTO opportunities (title, description, category, target_amount, status, image_url) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssds', $title, $description, $category, $target_amount, $status, $image_url);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed to add opportunity']);
    }
}

function editOpportunity() {
    global $conn;
    
    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'Opportunity ID is required']);
        exit();
    }

    $id = (int)$_POST['id'];
    
    // Check if opportunity exists and is not completed
    $check_query = "SELECT status FROM opportunities WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($opportunity = $result->fetch_assoc()) {
        if ($opportunity['status'] === 'completed') {
            echo json_encode(['error' => 'Cannot edit completed opportunity']);
            exit();
        }
    } else {
        echo json_encode(['error' => 'Opportunity not found']);
        exit();
    }

    // Update opportunity
    $query = "UPDATE opportunities SET 
              title = ?, 
              description = ?, 
              category = ?, 
              target_amount = ?, 
              status = ?,
              image_url = ?
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssdsi', 
        $_POST['title'],
        $_POST['description'],
        $_POST['category'],
        $_POST['target_amount'],
        $_POST['status'],
        $_POST['image_url'],
        $id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update opportunity']);
    }
}

function deleteOpportunity() {
    global $conn;
    
    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'Opportunity ID is required']);
        exit();
    }

    $id = (int)$_POST['id'];
    
    // Check if opportunity exists and is not completed
    $check_query = "SELECT status FROM opportunities WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($opportunity = $result->fetch_assoc()) {
        if ($opportunity['status'] === 'completed') {
            echo json_encode(['error' => 'Cannot delete completed opportunity']);
            exit();
        }
    } else {
        echo json_encode(['error' => 'Opportunity not found']);
        exit();
    }

    // Delete opportunity
    $query = "DELETE FROM opportunities WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete opportunity']);
    }
}
?>
