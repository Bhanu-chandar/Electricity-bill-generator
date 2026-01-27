<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();

$serviceNumber = '';
$consumer = null;
$bills = [];

if (hasRole('user')) {
    $stmt = $db->prepare("SELECT * FROM consumers WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $consumer = $stmt->fetch();
    
    if ($consumer) {
        $serviceNumber = $consumer['service_number'];
    }
}

if (isset($_GET['service_number']) && !empty($_GET['service_number'])) {
    $serviceNumber = sanitize($_GET['service_number']);
    $consumer = getConsumerByServiceNumber($serviceNumber);
}

if ($serviceNumber && $consumer) {
    $bills = getConsumerBills($serviceNumber);
}

$selectedBill = null;
if (isset($_GET['bill_id'])) {
    $billId = (int)$_GET['bill_id'];
    $stmt = $db->prepare("
        SELECT b.*, c.name, c.address, c.city, c.pincode, c.phone, c.email, c.meter_number
        FROM bills b
        JOIN consumers c ON b.consumer_id = c.id
        WHERE b.id = ?
    ");
    $stmt->execute([$billId]);
    $selectedBill = $stmt->fetch();
    
    if ($selectedBill && $selectedBill['service_number'] !== $serviceNumber) {
        $selectedBill = null;
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills - Electricity Bill Generator</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-icon">⚡</span> EBG User</h2>
            </div>
            
            <div class="sidebar-user">
                <div class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="/user/dashboard.php" class="active"><span class="nav-icon">📄</span> My Bills</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Electricity Bills</h1>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Search Bills</h3>
                </div>
                
                <form method="GET" action="" class="search-box">
                    <input type="text" 
                           name="service_number" 
                           class="form-control" 
                           placeholder="Enter your Service Number (e.g., HH-000001)"
                           value="<?php echo sanitize($serviceNumber); ?>"
                           required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <?php if ($serviceNumber && !$consumer): ?>
                <div class="alert alert-danger">
                    No consumer found with service number: <?php echo sanitize($serviceNumber); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($selectedBill): ?>
                <div class="card no-print">
                    <a href="?service_number=<?php echo urlencode($serviceNumber); ?>" class="btn btn-secondary">
                        ← Back to Bill List
                    </a>
                </div>
                
                <div class="bill-container">
                    <div class="bill-header">
                        <h2>⚡ Electricity Bill</h2>
                        <p>Bill No: <?php echo sanitize($selectedBill['bill_number']); ?></p>
                    </div>
                    
                    <div class="bill-body">
                        <div class="bill-section">
                            <div class="bill-section-title">Consumer Information</div>
                            <div class="form-row">
                                <div>
                                    <div class="bill-row">
                                        <span class="label">Name</span>
                                        <span class="value"><strong><?php echo sanitize($selectedBill['name']); ?></strong></span>
                                    </div>
                                    <div class="bill-row">
                                        <span class="label">Service Number</span>
                                        <span class="value"><code><?php echo sanitize($selectedBill['service_number']); ?></code></span>
                                    </div>
                                    <div class="bill-row">
                                        <span class="label">Phone</span>
                                        <span class="value"><?php echo sanitize($selectedBill['phone']); ?></span>
                                    </div>
                                </div>
                                <div>
                                    <div class="bill-row">
                                        <span class="label">Address</span>
                                        <span class="value"><?php echo sanitize($selectedBill['address']); ?></span>
                                    </div>
                                    <div class="bill-row">
                                        <span class="label">City</span>
                                        <span class="value"><?php echo sanitize($selectedBill['city']); ?> - <?php echo sanitize($selectedBill['pincode']); ?></span>
                                    </div>
                                    <div class="bill-row">
                                        <span class="label">Meter Number</span>
                                        <span class="value"><?php echo sanitize($selectedBill['meter_number']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bill-section">
                            <div class="bill-section-title">Billing Period</div>
                            <div class="form-row">
                                <div>
                                    <div class="bill-row">
                                        <span class="label">Bill Date</span>
                                        <span class="value"><?php echo formatDate($selectedBill['generated_at']); ?></span>
                                    </div>
                                    <div class="bill-row">
                                        <span class="label">Billing Period</span>
                                        <span class="value">
                                            <?php echo formatDate($selectedBill['billing_start_date']); ?> 
                                            to <?php echo formatDate($selectedBill['billing_end_date']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="bill-row">
                                        <span class="label">Category</span>
                                        <span class="value">
                                            <span class="badge <?php echo getCategoryBadge($selectedBill['category']); ?>">
                                                <?php echo getCategoryName($selectedBill['category']); ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bill-section">
                            <div class="bill-section-title">Reading Details</div>
                            <div class="bill-row">
                                <span class="label">Previous Reading</span>
                                <span class="value"><?php echo number_format($selectedBill['previous_reading']); ?> units</span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Current Reading</span>
                                <span class="value"><?php echo number_format($selectedBill['current_reading']); ?> units</span>
                            </div>
                            <div class="bill-row">
                                <span class="label"><strong>Units Consumed</strong></span>
                                <span class="value"><strong><?php echo number_format($selectedBill['units_consumed']); ?> units</strong></span>
                            </div>
                        </div>
                        
                        <div class="bill-section">
                            <div class="bill-section-title">Bill Breakdown</div>
                            <div class="bill-row">
                                <span class="label">Basic Charge</span>
                                <span class="value"><?php echo formatCurrency($selectedBill['basic_charge']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Energy Charge</span>
                                <span class="value"><?php echo formatCurrency($selectedBill['energy_charge']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Fuel Adjustment</span>
                                <span class="value"><?php echo formatCurrency($selectedBill['fuel_adjustment']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Electricity Duty</span>
                                <span class="value"><?php echo formatCurrency($selectedBill['electricity_duty']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Meter Rent</span>
                                <span class="value"><?php echo formatCurrency($selectedBill['meter_rent']); ?></span>
                            </div>
                            <div class="bill-row" style="border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.5rem;">
                                <span class="label"><strong>Sub Total</strong></span>
                                <span class="value"><strong><?php echo formatCurrency($selectedBill['total_amount']); ?></strong></span>
                            </div>
                            <?php if ($selectedBill['fine_amount'] > 0): ?>
                                <div class="bill-row text-danger">
                                    <span class="label"><strong>Late Payment Fine</strong></span>
                                    <span class="value"><strong><?php echo formatCurrency($selectedBill['fine_amount']); ?></strong></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bill-section">
                            <div class="bill-section-title">Payment Due Dates</div>
                            <div class="bill-row">
                                <span class="label">Due Date (Without Fine)</span>
                                <span class="value text-success"><strong><?php echo formatDate($selectedBill['due_date_without_fine']); ?></strong></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Due Date (With Fine)</span>
                                <span class="value text-warning"><?php echo formatDate($selectedBill['due_date_with_fine']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bill-total">
                        <span>Grand Total</span>
                        <span class="amount"><?php echo formatCurrency($selectedBill['grand_total']); ?></span>
                    </div>
                    
                    <div class="bill-footer">
                        <span>Payment Status</span>
                        <?php if ($selectedBill['is_paid']): ?>
                            <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                ✓ PAID on <?php echo formatDate($selectedBill['paid_date']); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                ✗ UNPAID
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-2 no-print">
                    <button onclick="window.print()" class="btn btn-primary">
                        🖨️ Print Bill
                    </button>
                </div>
                
            <?php elseif ($consumer): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Consumer Details</h3>
                        <span class="badge <?php echo getCategoryBadge($consumer['category']); ?>">
                            <?php echo getCategoryName($consumer['category']); ?>
                        </span>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <div class="bill-row">
                                <span class="label">Name</span>
                                <span class="value"><strong><?php echo sanitize($consumer['name']); ?></strong></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Service Number</span>
                                <span class="value"><code><?php echo sanitize($consumer['service_number']); ?></code></span>
                            </div>
                        </div>
                        <div>
                            <div class="bill-row">
                                <span class="label">Phone</span>
                                <span class="value"><?php echo sanitize($consumer['phone']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Address</span>
                                <span class="value"><?php echo sanitize($consumer['city']); ?> - <?php echo sanitize($consumer['pincode']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Bill History</h3>
                        <span class="badge badge-primary"><?php echo count($bills); ?> Bills</span>
                    </div>
                    
                    <?php if (empty($bills)): ?>
                        <div class="empty-state">
                            <div class="icon">📄</div>
                            <h3>No Bills Yet</h3>
                            <p>No bills have been generated for this service number yet</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Bill No.</th>
                                        <th>Period</th>
                                        <th>Units</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bills as $bill): ?>
                                        <tr>
                                            <td><strong><?php echo sanitize($bill['bill_number']); ?></strong></td>
                                            <td>
                                                <?php echo formatDate($bill['billing_start_date']); ?>
                                                <br><small>to <?php echo formatDate($bill['billing_end_date']); ?></small>
                                            </td>
                                            <td><?php echo number_format($bill['units_consumed']); ?></td>
                                            <td>
                                                <?php echo formatCurrency($bill['grand_total']); ?>
                                                <?php if ($bill['fine_amount'] > 0): ?>
                                                    <br><small class="text-danger">(incl. <?php echo formatCurrency($bill['fine_amount']); ?> fine)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($bill['due_date_without_fine']); ?></td>
                                            <td>
                                                <?php if ($bill['is_paid']): ?>
                                                    <span class="status-paid" title="Paid">✓</span>
                                                <?php else: ?>
                                                    <span class="status-unpaid" title="Unpaid">✗</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?service_number=<?php echo urlencode($serviceNumber); ?>&bill_id=<?php echo $bill['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    View Bill
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
