<?php
session_start();
require_once '../config/database.php';

// Ensure user is logged in as adoptive parent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$adopter_id = null;
$errors = [];
$success = '';

// Get adopter ID from donor table
$user_email = '';
$result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $user_email = $row['email'];
}

if ($user_email) {
    $email_esc = $conn->real_escape_string($user_email);
    $result = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' AND type = 'adoptive' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $adopter_id = (int)$row['id'];
    }
}

if (!$adopter_id) {
    $errors[] = 'Unable to identify your adoptive parent profile. Please contact support.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adopter_id) {
    $child_full_name = trim($_POST['child_full_name'] ?? '');
    $child_last_name = '';
    $child_date_of_birth = trim($_POST['child_date_of_birth'] ?? '');
    $child_gender = trim($_POST['child_gender'] ?? '');
    $child_health_status = trim($_POST['child_health_status'] ?? '');
    $child_notes = trim($_POST['child_notes'] ?? '');

    if ($child_full_name === '') $errors[] = 'Child name is required.';
    if ($child_date_of_birth === '') $errors[] = 'Child date of birth is required.';
    if ($child_gender === '') $errors[] = 'Child gender is required.';
    if ($child_health_status === '') $errors[] = 'Child health status is required.';

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $child_sql = "INSERT INTO children (first_name, last_name, date_of_birth, gender, health_status) VALUES (?, ?, ?, ?, ?)";
            $child_stmt = $conn->prepare($child_sql);
            if (!$child_stmt) throw new Exception('Database error: ' . $conn->error);
            $child_stmt->bind_param('sssss', $child_full_name, $child_last_name, $child_date_of_birth, $child_gender, $child_health_status);
            $child_stmt->execute();
            $child_id = $conn->insert_id;
            if ($child_id <= 0) throw new Exception('Unable to register child profile.');

            $note_text = $child_notes !== '' ? $child_notes : 'Child profile submitted by adoptive parent.';
            $adoption_sql = "INSERT INTO adoptions (child_id, adopter_id, status, application_date, notes) VALUES (?, ?, 'pending', CURDATE(), ?)";
            $adoption_stmt = $conn->prepare($adoption_sql);
            if (!$adoption_stmt) throw new Exception('Database error: ' . $conn->error);
            $adoption_stmt->bind_param('iis', $child_id, $adopter_id, $note_text);
            $adoption_stmt->execute();

            $conn->commit();
            $success = 'Child profile registered successfully! Your adoption application has been submitted for review.';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Submission failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Child - Adoption Application</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { width: 100%; max-width: 420px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 28px 80px rgba(15, 23, 42, 0.18); overflow: hidden; }
        .card-header { padding: 36px 28px 18px; text-align: center; }
        .header-top { display: flex; flex-direction: column; align-items: center; gap: 14px; margin-bottom: 12px; }
        .header-icon { width: 72px; height: 72px; border-radius: 14px; display: grid; place-items: center; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-size: 30px; }
        .header-text h1 { font-size: 20px; color: #0f172a; margin: 0 0 6px; line-height: 1.05; font-weight: 800; }
        .header-text p { margin: 0; color: #6b7280; font-size: 13px; }
        .header-user { display: inline-flex; align-items: center; gap: 10px; padding: 8px 14px; background: rgba(99, 102, 241, 0.08); border-radius: 12px; margin: 0 auto; }
        .user-badge { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; display: grid; place-items: center; font-weight: 700; font-size: 14px; }
        .user-text { font-size: 13px; color: #111827; }
        .card-body { padding: 22px 22px 28px; }
        .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; line-height: 1.6; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-error ul { list-style: none; padding-left: 0; margin-top: 8px; }
        .alert-error li { margin-bottom: 6px; padding-left: 14px; position: relative; }
        .alert-error li:before { content: '●'; position: absolute; left: 0; font-size: 10px; top: 6px; }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .form-section { margin-bottom: 18px; }
        .section-title { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .section-title h2 { margin: 0; font-size: 15px; color: #111827; font-weight: 700; }
        .section-icon { font-size: 18px; color: #4f46e5; }
        .form-grid { display: grid; gap: 12px; }
        .form-row.two { grid-template-columns: 1fr; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #e6edf3; background: #f9fafb; font-size: 14px; color: #111827; transition: all 0.22s ease; font-family: inherit; }
        input:hover, select:hover, textarea:hover { border-color: #c7d2fe; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 6px rgba(102, 126, 234, 0.06); background: white; }
        textarea { min-height: 110px; resize: vertical; }
        .field-hint { display: block; font-size: 12px; color: #6b7280; margin-top: 6px; }
        .form-actions { display: grid; gap: 12px; margin-top: 18px; }
        .btn { width: 100%; padding: 12px 14px; border: none; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.22s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn { width: 100%; padding: 16px 20px; border: none; border-radius: 18px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.22s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-submit { background: linear-gradient(90deg, #4f46e5, #6366f1); color: #ffffff; box-shadow: 0 16px 36px rgba(79, 70, 229, 0.18); }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 20px 44px rgba(79, 70, 229, 0.22); }
        .btn-submit:active { transform: translateY(0); }
        .btn-reset { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-reset:hover { background: #eff6ff; }
        .back-nav { display: inline-flex; align-items: center; gap: 8px; color: #4f46e5; text-decoration: none; font-weight: 600; font-size: 14px; margin-bottom: 24px; transition: all 0.3s; }
        .back-nav:hover { gap: 12px; }
        .required { color: #ef4444; }
        @media (max-width: 768px) {
            .card-header { padding: 32px 24px; }
            .card-body { padding: 24px; }
            .form-row.two { grid-template-columns: 1fr; }
            .header-text h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="header-top">
                    <div class="header-icon"><i class="fas fa-child"></i></div>
                    <div class="header-text">
                        <h1>Register a Child</h1>
                        <p>Add child profile to your adoption application</p>
                    </div>
                </div>
                <div class="header-user">
                    <div class="user-badge"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <span class="user-text"><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
            <div class="card-body">
                <a href="dashboard.php" class="back-nav"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Please fix the following issues:
                    <ul>
                        <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" id="childForm">
                    <div class="form-section">
                        <div class="section-title">
                            <span class="section-icon"><i class="fas fa-info-circle"></i></span>
                            <h2>Child Personal Information</h2>
                        </div>
                            <div class="form-grid">
                            <div>
                                <label for="child_full_name">Full Name <span class="required">*</span></label>
                                <input type="text" id="child_full_name" name="child_full_name" placeholder="Full name" value="<?php echo htmlspecialchars($_POST['child_full_name'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="child_date_of_birth">Date of Birth <span class="required">*</span></label>
                                <input type="date" id="child_date_of_birth" name="child_date_of_birth" value="<?php echo htmlspecialchars($_POST['child_date_of_birth'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="child_gender">Gender <span class="required">*</span></label>
                                <select id="child_gender" name="child_gender" required>
                                    <option value="">Gender</option>
                                    <option value="male" <?php echo ($_POST['child_gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($_POST['child_gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($_POST['child_gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <span class="section-icon"><i class="fas fa-heartbeat"></i></span>
                            <h2>Health Information</h2>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label for="child_health_status">Health Status <span class="required">*</span></label>
                                <textarea id="child_health_status" name="child_health_status" placeholder="Health status, allergies, medications" required><?php echo htmlspecialchars($_POST['child_health_status'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">
                            <span class="section-icon"><i class="fas fa-sticky-note"></i></span>
                            <h2>Additional Information</h2>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label for="child_notes">Notes</label>
                                <textarea id="child_notes" name="child_notes" placeholder="Optional notes"><?php echo htmlspecialchars($_POST['child_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-redo"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('childForm').addEventListener('submit', function() {
            const btn = this.querySelector('.btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });
    </script>
</body>
</html>
