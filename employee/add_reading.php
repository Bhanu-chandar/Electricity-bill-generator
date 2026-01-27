<?php

require_once __DIR__ . '/../includes/auth.php';
requireEmployee();

$db = getDB();
$errors = [];
$success = '';
$consumer = null;
$lastReading = null;

if (isset($_GET['service_number'])) {
    $serviceNumber = sanitize($_GET['service_number']);
    $consumer = getConsumerByServiceNumber($serviceNumber);
    
    if ($consumer) {
        $stmt = $db->prepare("
            SELECT reading_value, reading_date 
            FROM readings 
            WHERE consumer_id = ? 
            ORDER BY reading_date DESC, id DESC 
            LIMIT 1
        ");
        $stmt->execute([$consumer['id']]);
        $lastReading = $stmt->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consumerId = (int)$_POST['consumer_id'];
    $currentReading = (float)$_POST['current_reading'];
    $readingDate = $_POST['reading_date'];
    
    $stmt = $db->prepare("SELECT * FROM consumers WHERE id = ?");
    $stmt->execute([$consumerId]);
    $consumer = $stmt->fetch();
    
    if (!$consumer) {
        $errors['general'] = 'Consumer not found';
    } else {
        $stmt = $db->prepare("
            SELECT reading_value, reading_date 
            FROM readings 
            WHERE consumer_id = ? 
            ORDER BY reading_date DESC, id DESC 
            LIMIT 1
        ");
        $stmt->execute([$consumerId]);
        $lastReading = $stmt->fetch();
        
        $prevReading = $lastReading ? $lastReading['reading_value'] : 0;
        
        if ($currentReading < $prevReading) {
            $errors['current_reading'] = "Reading cannot be less than previous reading ($prevReading)";
        }
        
        if (empty($readingDate)) {
            $errors['reading_date'] = 'Reading date is required';
        } elseif (strtotime($readingDate) > time()) {
            $errors['reading_date'] = 'Reading date cannot be in the future';
        }
        
        if (empty($errors)) {
            try {
                $billNumber = generateBill($consumerId, $currentReading, $readingDate, $_SESSION['user_id']);
                $success = "Meter reading recorded successfully! Bill generated: <strong>$billNumber</strong>";
                
                $stmt = $db->prepare("SELECT * FROM consumers WHERE id = ?");
                $stmt->execute([$consumerId]);
                $consumer = $stmt->fetch();
                
                $stmt = $db->prepare("
                    SELECT reading_value, reading_date 
                    FROM readings 
                    WHERE consumer_id = ? 
                    ORDER BY reading_date DESC, id DESC 
                    LIMIT 1
                ");
                $stmt->execute([$consumerId]);
                $lastReading = $stmt->fetch();
                
            } catch (Exception $e) {
                $errors['general'] = 'Failed to generate bill: ' . $e->getMessage();
            }
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Reading - Electricity Bill Generator</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-icon">⚡</span> EBG Employee</h2>
            </div>
            
            <div class="sidebar-user">
                <div class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="/employee/dashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="/employee/add_reading.php" class="active"><span class="nav-icon">📝</span> Add Reading</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Record Meter Reading</h1>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Step 1: Find Consumer</h3>
                </div>
                
                <form method="GET" action="" class="search-box">
                    <input type="text" 
                           name="service_number" 
                           class="form-control" 
                           placeholder="Enter Service Number (e.g., HH-000001)"
                           value="<?php echo sanitize($_GET['service_number'] ?? ''); ?>"
                           required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <?php if (isset($_GET['service_number']) && !$consumer): ?>
                <div class="alert alert-danger">
                    No consumer found with service number: <?php echo sanitize($_GET['service_number']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($consumer): ?>
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
                                <span class="label">Service Number</span>
                                <span class="value"><code><?php echo sanitize($consumer['service_number']); ?></code></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Consumer Name</span>
                                <span class="value"><strong><?php echo sanitize($consumer['name']); ?></strong></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Phone</span>
                                <span class="value"><?php echo sanitize($consumer['phone']); ?></span>
                            </div>
                        </div>
                        <div>
                            <div class="bill-row">
                                <span class="label">Meter Number</span>
                                <span class="value"><?php echo sanitize($consumer['meter_number']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Address</span>
                                <span class="value"><?php echo sanitize($consumer['city']); ?> - <?php echo sanitize($consumer['pincode']); ?></span>
                            </div>
                            <div class="bill-row">
                                <span class="label">Connection Date</span>
                                <span class="value"><?php echo formatDate($consumer['connection_date']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 2: Previous Reading</h3>
                    </div>
                    
                    <?php if ($lastReading): ?>
                        <div class="form-row">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo number_format($lastReading['reading_value']); ?></div>
                                <div class="stat-label">Last Reading (Units)</div>
                            </div>
                            <div class="stat-card success">
                                <div class="stat-value"><?php echo formatDate($lastReading['reading_date']); ?></div>
                                <div class="stat-label">Reading Date</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            This is the first reading for this consumer. Previous reading will be considered as 0.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Step 3: Enter New Reading</h3>
                    </div>
                    
                    <form method="POST" action="?service_number=<?php echo urlencode($consumer['service_number']); ?>">
                        <input type="hidden" name="consumer_id" value="<?php echo $consumer['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="current_reading">
                                    Current Meter Reading <span class="required">*</span>
                                </label>
                                <input type="number" 
                                       id="current_reading" 
                                       name="current_reading" 
                                       class="form-control <?php echo isset($errors['current_reading']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo sanitize($_POST['current_reading'] ?? ''); ?>"
                                       min="<?php echo $lastReading ? $lastReading['reading_value'] : 0; ?>"
                                       step="0.01"
                                       required
                                       autofocus>
                                <?php if (isset($errors['current_reading'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['current_reading']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">
                                    Must be greater than or equal to <?php echo $lastReading ? number_format($lastReading['reading_value']) : 0; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="reading_date">
                                    Reading Date <span class="required">*</span>
                                </label>
                                <input type="date" 
                                       id="reading_date" 
                                       name="reading_date" 
                                       class="form-control <?php echo isset($errors['reading_date']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo sanitize($_POST['reading_date'] ?? date('Y-m-d')); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       required>
                                <?php if (isset($errors['reading_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['reading_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group" id="unitsPreview" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Units to be billed:</strong> <span id="unitsCount">0</span> units
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            Record Reading & Generate Bill
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        const prevReading = <?php echo $lastReading ? $lastReading['reading_value'] : 0; ?>;
        const currentReadingInput = document.getElementById('current_reading');
        const unitsPreview = document.getElementById('unitsPreview');
        const unitsCount = document.getElementById('unitsCount');
        
        if (currentReadingInput) {
            currentReadingInput.addEventListener('input', function() {
                const currentValue = parseFloat(this.value) || 0;
                const units = currentValue - prevReading;
                
                if (units >= 0) {
                    unitsPreview.style.display = 'block';
                    unitsCount.textContent = units.toFixed(2);
                } else {
                    unitsPreview.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
