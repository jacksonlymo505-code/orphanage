<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

// Get currency
$currency = get_currency();

// Get opportunity details if opportunity_id is provided
$opportunity = null;
if (isset($_GET['opportunity_id'])) {
    $opportunity_id = intval($_GET['opportunity_id']);
    $query = "SELECT * FROM opportunities WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $opportunity_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $opportunity = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $purpose = $_POST['purpose']; // This will be stored in notes
    $donor_id = $_SESSION['user_id'];
    $status = 'pending';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank_transfer';
    $donation_date = date('Y-m-d H:i:s');
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;

    // Now proceed with the donation insertion
    $query = "INSERT INTO donations (donor_id, project_id, amount, payment_method, status, notes, donation_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        $error_message = "Error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("iidssss", $donor_id, $project_id, $amount, $payment_method, $status, $purpose, $donation_date);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Donation submitted successfully!";
            header("Location: donations.php");
            exit();
        } else {
            $error_message = "Error submitting donation: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Donation - Donor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <link rel="stylesheet" href="../assets/css/all.min.css">
  
</head>
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

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
            background: #f5f6fa;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .donation-form-container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
            transition: transform 0.3s ease;
        }

        .donation-form-container:hover {
            transform: translateY(-5px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header i {
            font-size: 3.5rem;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            background: rgba(52, 152, 219, 0.1);
            padding: 1rem;
            border-radius: 50%;
        }

        .form-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .form-header p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        .amount-input {
            position: relative;
        }

        .amount-input span {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .amount-input input {
            padding-left: 2.5rem;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .form-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
        }

        .btn-submit {
            flex: 1;
            padding: 1.25rem;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        }

        .btn-cancel {
            flex: 1;
            padding: 1.25rem;
            background: white;
            color: var(--primary-color);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            border-left: 4px solid #c62828;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .donation-form-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .form-header i {
                font-size: 3rem;
            }

            .form-header h1 {
                font-size: 1.75rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel {
                width: 100%;
            }
        }
    </style>

<body>
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="donation-form-container">
            <div class="form-header">
                <i class="fas fa-hand-holding-heart"></i>
                <h1>Make a Donation</h1>
                <?php if ($opportunity): ?>
                    <p>You are donating to: <strong><?php echo htmlspecialchars($opportunity['title']); ?></strong></p>
                <?php else: ?>
                    <p>Your generosity can make a real difference in someone's life</p>
                <?php endif; ?>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php if ($opportunity): ?>
                    <input type="hidden" name="project_id" value="<?php echo $opportunity['id']; ?>">
                    <div class="form-group">
                        <label>Opportunity Details</label>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 12px; margin-bottom: 1rem;">
                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($opportunity['title']); ?></h3>
                            <p style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($opportunity['description']); ?></p>
                            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                <span>Target: TSh <?php echo number_format($opportunity['target_amount'], 2); ?></span>
                                <span>Progress: TSh <?php echo number_format($opportunity['current_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- New Lipa Kwa Namba field -->
                <div class="form-group">
                    <label for="lipa_namba">Lipa Kwa Namba</label>
                    <select id="lipa_namba" name="lipa_namba" required>
                        <option value="mpesa">Mpesa</option>
                        <option value="tigopesa">TigoPesa</option>
                        <option value="halopesa">Halopesa</option>
                    </select>
                </div>
                <!-- Payment instructions -->
                <div id="payment-instructions" style="display:none; margin-bottom: 2rem; background: #f8f9fa; border-radius: 12px; padding: 1rem; color: #2c3e50;"></div>
                <!-- End Lipa Kwa Namba field -->
                <div class="form-group">
                    <label for="amount">Donation Amount</label>
                    <div class="amount-input" style="position: relative;">
                        <span style="left: 1.25rem;">TSh&nbsp;</span>
                        <input type="text" id="amount" name="amount" style="padding-left: 3.5rem;" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="purpose">Payment Transaction</label>
                    <textarea id="purpose" name="purpose" placeholder="Enter payment transaction details..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Submit</button>
                    <a href="donations.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Amount input formatting
        const amountInput = document.getElementById('amount');
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value;
            if (value < 0) e.target.value = 0;
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const amount = parseFloat(amountInput.value);
            if (amount <= 0) {
                e.preventDefault();
                alert('Please enter a valid donation amount.');
            }
        });
    </script>
    <script>
        // Payment instructions for each provider
        const instructions = {
            mpesa: `
                <strong>Mpesa Payment Instructions</strong><br>
                1. Dial <b>*150*00#</b> on your phone.<br>
                2. Select <b>4: Pay by M-Pesa</b>.<br>
                3. Select <b>4: Enter Business Number</b>.<br>
                4. Enter <b>Lipa Namba: 123456</b>.<br>
                5. Enter your reference and amount.<br>
                6. Enter your PIN to complete the payment.<br>
            `,
            tigopesa: `
                <strong>TigoPesa Payment Instructions</strong><br>
                1. Dial <b>*150*01#</b> on your phone.<br>
                2. Select <b>4: Pay Bills</b>.<br>
                3. Select <b>3: Business Number</b>.<br>
                4. Enter <b>Lipa Namba: 654321</b>.<br>
                5. Enter your reference and amount.<br>
                6. Enter your PIN to complete the payment.<br>
            `,
            halopesa: `
                <strong>Halopesa Payment Instructions</strong><br>
                1. Dial <b>*150*88#</b> on your phone.<br>
                2. Select <b>5: Make Payment</b>.<br>
                3. Select <b>3: Business Number</b>.<br>
                4. Enter <b>Lipa Namba: 789012</b>.<br>
                5. Enter your reference and amount.<br>
                6. Enter your PIN to complete the payment.<br>
            `
        };
        const lipaSelect = document.getElementById('lipa_namba');
        const instructionsDiv = document.getElementById('payment-instructions');
        function showInstructions() {
            const val = lipaSelect.value;
            if (instructions[val]) {
                instructionsDiv.innerHTML = instructions[val];
                instructionsDiv.style.display = 'block';
            } else {
                instructionsDiv.style.display = 'none';
            }
        }
        lipaSelect.addEventListener('change', showInstructions);
        // Show on page load if already selected
        showInstructions();
    </script>
</body>
</html> 