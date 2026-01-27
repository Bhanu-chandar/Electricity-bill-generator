<?php

require_once __DIR__ . '/../config/database.php';

function formatName($name) {
    $name = substr(trim($name), 0, 32);
    
    $words = explode(' ', $name);
    $formattedWords = [];
    
    foreach ($words as $word) {
        if (empty($word)) continue;
        
        if (preg_match('/^[a-zA-Z]\.$/', $word)) {
            $formattedWords[] = strtoupper($word);
        } elseif (preg_match('/^[a-zA-Z]\.[a-zA-Z]/', $word)) {
            $parts = explode('.', $word, 2);
            $formattedWords[] = strtoupper($parts[0]) . '.';
            if (!empty($parts[1])) {
                $formattedWords[] = ucfirst(strtolower($parts[1]));
            }
        } else {
            $formattedWords[] = ucfirst(strtolower($word));
        }
    }
    
    return implode(' ', $formattedWords);
}

function validateName($name) {
    $name = trim($name);
    if (strlen($name) > 32) {
        return ['valid' => false, 'message' => 'Name must be 32 characters or less'];
    }
    if (!preg_match('/^[a-zA-Z\s.\-]+$/', $name)) {
        return ['valid' => false, 'message' => 'Name can only contain letters, spaces, dots, and hyphens'];
    }
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Name must be at least 2 characters'];
    }
    return ['valid' => true, 'message' => ''];
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) !== 10) {
        return ['valid' => false, 'message' => 'Phone number must be exactly 10 digits'];
    }
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        return ['valid' => false, 'message' => 'Invalid phone number format'];
    }
    return ['valid' => true, 'message' => '', 'value' => $phone];
}

function validatePincode($pincode) {
    $pincode = preg_replace('/[^0-9]/', '', $pincode);
    if (strlen($pincode) !== 6) {
        return ['valid' => false, 'message' => 'Pincode must be exactly 6 digits'];
    }
    if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
        return ['valid' => false, 'message' => 'Invalid pincode format'];
    }
    return ['valid' => true, 'message' => '', 'value' => $pincode];
}

function validateEmail($email) {
    if (empty($email)) {
        return ['valid' => true, 'message' => '', 'value' => ''];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format'];
    }
    return ['valid' => true, 'message' => '', 'value' => $email];
}

function generateServiceNumber($category) {
    $db = getDB();
    
    $prefixes = [
        'household' => 'HH',
        'commercial' => 'CM',
        'industry' => 'IN'
    ];
    
    $prefix = $prefixes[$category] ?? 'XX';
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT last_number FROM service_number_counter WHERE category = ? FOR UPDATE");
        $stmt->execute([$category]);
        $row = $stmt->fetch();
        
        $newNumber = ($row['last_number'] ?? 0) + 1;
        
        $stmt = $db->prepare("UPDATE service_number_counter SET last_number = ? WHERE category = ?");
        $stmt->execute([$newNumber, $category]);
        
        $db->commit();
        
        return $prefix . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function generateBillNumber() {
    $db = getDB();
    $datePrefix = date('Ymd');
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT last_number FROM bill_number_counter WHERE date_prefix = ? FOR UPDATE");
        $stmt->execute([$datePrefix]);
        $row = $stmt->fetch();
        
        if ($row) {
            $newNumber = $row['last_number'] + 1;
            $stmt = $db->prepare("UPDATE bill_number_counter SET last_number = ? WHERE date_prefix = ?");
            $stmt->execute([$newNumber, $datePrefix]);
        } else {
            $newNumber = 1;
            $stmt = $db->prepare("INSERT INTO bill_number_counter (date_prefix, last_number) VALUES (?, ?)");
            $stmt->execute([$datePrefix, $newNumber]);
        }
        
        $db->commit();
        
        return 'BILL-' . $datePrefix . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function getRateSlabs($category) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM rates 
        WHERE category = ? AND is_active = 1 
        ORDER BY slab_start ASC
    ");
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

function calculateBill($units, $category) {
    $slabs = getRateSlabs($category);
    
    if (empty($slabs)) {
        throw new Exception("No rate slabs found for category: $category");
    }
    
    $energyCharge = 0;
    $basicCharge = 0;
    $remainingUnits = $units;
    
    foreach ($slabs as $slab) {
        if ($remainingUnits <= 0) break;
        
        $slabRange = $slab['slab_end'] - $slab['slab_start'] + 1;
        
        if ($units >= $slab['slab_start']) {
            $basicCharge = $slab['basic_charge'];
            
            if ($units <= $slab['slab_end']) {
                $unitsInSlab = $units - $slab['slab_start'] + 1;
                if ($slab['slab_start'] == 0) {
                    $unitsInSlab = $units + 1;
                }
            } else {
                $unitsInSlab = $slabRange;
            }
            
            if ($slab['slab_start'] == 0) {
                $unitsInSlab = min($units, $slab['slab_end']) + 1;
                if ($unitsInSlab > $slab['slab_end'] + 1) {
                    $unitsInSlab = $slab['slab_end'] + 1;
                }
            }
            
            $energyCharge += $unitsInSlab * $slab['rate_per_unit'];
        }
    }
    
    $fuelAdjustment = $energyCharge * 0.05;
    $electricityDuty = $energyCharge * 0.06;
    $meterRent = 20.00;
    
    $totalAmount = $basicCharge + $energyCharge + $fuelAdjustment + $electricityDuty + $meterRent;
    
    return [
        'basic_charge' => round($basicCharge, 2),
        'energy_charge' => round($energyCharge, 2),
        'fuel_adjustment' => round($fuelAdjustment, 2),
        'electricity_duty' => round($electricityDuty, 2),
        'meter_rent' => round($meterRent, 2),
        'total_amount' => round($totalAmount, 2)
    ];
}

function calculateFine($totalAmount, $dueDate) {
    $today = new DateTime();
    $due = new DateTime($dueDate);
    
    if ($today <= $due) {
        return 0;
    }
    
    $daysOverdue = $today->diff($due)->days;
    
    $weeks = ceil($daysOverdue / 7);
    $finePercentage = min($weeks * 2, 20);
    
    return round($totalAmount * ($finePercentage / 100), 2);
}

function generateBill($consumerId, $currentReading, $readingDate, $recordedBy) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM consumers WHERE id = ?");
    $stmt->execute([$consumerId]);
    $consumer = $stmt->fetch();
    
    if (!$consumer) {
        throw new Exception("Consumer not found");
    }
    
    $stmt = $db->prepare("
        SELECT reading_value, reading_date FROM readings 
        WHERE consumer_id = ? 
        ORDER BY reading_date DESC, id DESC 
        LIMIT 1
    ");
    $stmt->execute([$consumerId]);
    $previousReading = $stmt->fetch();
    
    $prevReadingValue = $previousReading ? $previousReading['reading_value'] : 0;
    $prevReadingDate = $previousReading ? $previousReading['reading_date'] : $consumer['connection_date'];
    
    $billingStartDate = date('Y-m-d', strtotime($prevReadingDate . ' +1 day'));
    $billingEndDate = $readingDate;
    
    $unitsConsumed = $currentReading - $prevReadingValue;
    
    if ($unitsConsumed < 0) {
        throw new Exception("Current reading cannot be less than previous reading");
    }
    
    $billDetails = calculateBill($unitsConsumed, $consumer['category']);
    
    $dueDateWithoutFine = date('Y-m-d', strtotime($readingDate . ' +15 days'));
    $dueDateWithFine = date('Y-m-d', strtotime($readingDate . ' +30 days'));
    
    $billNumber = generateBillNumber();
    
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO readings (consumer_id, reading_value, reading_date, recorded_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$consumerId, $currentReading, $readingDate, $recordedBy]);
        
        $stmt = $db->prepare("
            INSERT INTO bills (
                bill_number, consumer_id, service_number, billing_start_date, billing_end_date,
                previous_reading, current_reading, units_consumed, category,
                basic_charge, energy_charge, fuel_adjustment, electricity_duty, meter_rent,
                total_amount, fine_amount, grand_total, due_date_without_fine, due_date_with_fine
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
        ");
        $stmt->execute([
            $billNumber,
            $consumerId,
            $consumer['service_number'],
            $billingStartDate,
            $billingEndDate,
            $prevReadingValue,
            $currentReading,
            $unitsConsumed,
            $consumer['category'],
            $billDetails['basic_charge'],
            $billDetails['energy_charge'],
            $billDetails['fuel_adjustment'],
            $billDetails['electricity_duty'],
            $billDetails['meter_rent'],
            $billDetails['total_amount'],
            $billDetails['total_amount'],
            $dueDateWithoutFine,
            $dueDateWithFine
        ]);
        
        $db->commit();
        
        return $billNumber;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function updateOverdueFines() {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT id, total_amount, due_date_without_fine 
        FROM bills 
        WHERE is_paid = 0
    ");
    $bills = $stmt->fetchAll();
    
    foreach ($bills as $bill) {
        $fine = calculateFine($bill['total_amount'], $bill['due_date_without_fine']);
        $grandTotal = $bill['total_amount'] + $fine;
        
        $updateStmt = $db->prepare("
            UPDATE bills SET fine_amount = ?, grand_total = ? WHERE id = ?
        ");
        $updateStmt->execute([$fine, $grandTotal, $bill['id']]);
    }
}


function getConsumerBills($serviceNumber) {
    $db = getDB();
    
    updateOverdueFines();
    
    $stmt = $db->prepare("
        SELECT b.*, c.name, c.address, c.city, c.pincode, c.phone, c.email
        FROM bills b
        JOIN consumers c ON b.consumer_id = c.id
        WHERE b.service_number = ?
        ORDER BY b.billing_end_date DESC
    ");
    $stmt->execute([$serviceNumber]);
    return $stmt->fetchAll();
}

function getConsumerByServiceNumber($serviceNumber) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM consumers WHERE service_number = ?");
    $stmt->execute([$serviceNumber]);
    return $stmt->fetch();
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function formatDate($date) {
    return date('d-M-Y', strtotime($date));
}

function getCategoryName($category) {
    $names = [
        'household' => 'Household (Domestic)',
        'commercial' => 'Commercial',
        'industry' => 'Industrial'
    ];
    return $names[$category] ?? $category;
}

function getCategoryBadge($category) {
    $badges = [
        'household' => 'badge-primary',
        'commercial' => 'badge-warning',
        'industry' => 'badge-purple'
    ];
    return $badges[$category] ?? 'badge-primary';
}
?>
