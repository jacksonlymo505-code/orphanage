<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("No donation ID provided");
}

$donation_id = $_GET['id'];
$donor_id = $_SESSION['user_id'];

// Verify the donation belongs to the logged-in donor
$query = "SELECT d.*, u.name as donor_name, u.email as donor_email 
          FROM donations d 
          JOIN users u ON d.donor_id = u.id 
          WHERE d.id = ? AND d.donor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $donation_id, $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donation = $result->fetch_assoc();

if (!$donation) {
    die("Donation not found or unauthorized");
}

// Create new PDF document
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Orphanage Management System');
$pdf->SetTitle('Donation Receipt');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add content
$html = '
<div style="text-align: center;">
    <h1>Donation Receipt</h1>
    <p>Thank you for your generous donation!</p>
</div>

<div style="margin: 20px 0;">
    <h2>Receipt Details</h2>
    <table border="1" cellpadding="5">
        <tr>
            <td><strong>Receipt No:</strong></td>
            <td>#' . str_pad($donation['id'], 6, '0', STR_PAD_LEFT) . '</td>
        </tr>
        <tr>
            <td><strong>Date:</strong></td>
            <td>' . date('F d, Y', strtotime($donation['created_at'])) . '</td>
        </tr>
        <tr>
            <td><strong>Donor Name:</strong></td>
            <td>' . htmlspecialchars($donation['donor_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Donor Email:</strong></td>
            <td>' . htmlspecialchars($donation['donor_email']) . '</td>
        </tr>
        <tr>
            <td><strong>Amount:</strong></td>
            <td>$' . number_format($donation['amount'], 2) . '</td>
        </tr>
        <tr>
            <td><strong>Payment Method:</strong></td>
            <td>' . ucfirst(str_replace('_', ' ', $donation['payment_method'])) . '</td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>' . ucfirst($donation['status']) . '</td>
        </tr>
        <tr>
            <td><strong>Notes:</strong></td>
            <td>' . htmlspecialchars($donation['notes']) . '</td>
        </tr>
    </table>
</div>

<div style="margin-top: 30px; text-align: center;">
    <p>This receipt serves as proof of your donation.</p>
    <p>Thank you for your support!</p>
</div>';

// Print content
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('donation_receipt_' . $donation_id . '.pdf', 'D'); 