<?php

function validateServiceNumber($serviceNumber, $excludeConsumerId = null) {
    if (empty($serviceNumber)) {
        return ['valid' => false, 'message' => 'Service number is required'];
    }
    
    if (!preg_match('/^[A-Za-z0-9\-]+$/', $serviceNumber)) {
        return ['valid' => false, 'message' => 'Service number can only contain letters, numbers, and hyphens'];
    }
    
    try {
        $db = getDB();
        $query = "SELECT id FROM consumers WHERE service_number = ?";
        $params = [$serviceNumber];
        
        if ($excludeConsumerId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeConsumerId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'Service number already exists. Duplicate service numbers are not allowed.'];
        }
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Database error while checking service number'];
    }
    
    return ['valid' => true, 'message' => '', 'value' => strtoupper($serviceNumber)];
}

function validateConsumerName($name) {
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'message' => 'Name is required'];
    }
    
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        return ['valid' => false, 'message' => 'Name must contain only alphabets and spaces. No numbers or special characters allowed.'];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Name must be at least 2 characters'];
    }
    
    if (strlen($name) > 100) {
        return ['valid' => false, 'message' => 'Name must be 100 characters or less'];
    }
    
    $formattedName = ucwords(strtolower($name));
    
    return ['valid' => true, 'message' => '', 'value' => $formattedName];
}

function validateConsumerPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) !== 10) {
        return ['valid' => false, 'message' => 'Phone number must be exactly 10 digits. No more, no less.'];
    }
    
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        return ['valid' => false, 'message' => 'Invalid phone number format. Must start with 6, 7, 8, or 9'];
    }
    
    return ['valid' => true, 'message' => '', 'value' => $phone];
}

function validateReadings($previousReading, $currentReading) {
    if (!is_numeric($previousReading) || $previousReading < 0) {
        return ['valid' => false, 'message' => 'Previous reading must be a non-negative number'];
    }
    
    if (!is_numeric($currentReading) || $currentReading < 0) {
        return ['valid' => false, 'message' => 'Current reading must be a non-negative number'];
    }
    
    if ($currentReading < $previousReading) {
        return ['valid' => false, 'message' => 'Current reading cannot be less than previous reading'];
    }
    
    return ['valid' => true, 'message' => ''];
}

function validateAddress($address) {
    $address = trim($address);
    
    if (empty($address)) {
        return ['valid' => false, 'message' => 'Address is required'];
    }
    
    if (strlen($address) < 10) {
        return ['valid' => false, 'message' => 'Address must be at least 10 characters'];
    }
    
    return ['valid' => true, 'message' => '', 'value' => $address];
}

function validateDate($date) {
    if (empty($date)) {
        return ['valid' => false, 'message' => 'Date is required'];
    }
    
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        return ['valid' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD'];
    }
    
    if ($dateObj > new DateTime()) {
        return ['valid' => false, 'message' => 'Date cannot be in the future'];
    }
    
    return ['valid' => true, 'message' => '', 'value' => $date];
}

function validateAllInputs($data, $excludeConsumerId = null) {
    $errors = [];
    $validatedData = [];
    
    $serviceValidation = validateServiceNumber($data['service_number'], $excludeConsumerId);
    if (!$serviceValidation['valid']) {
        $errors['service_number'] = $serviceValidation['message'];
    } else {
        $validatedData['service_number'] = $serviceValidation['value'];
    }
    
    $nameValidation = validateConsumerName($data['name']);
    if (!$nameValidation['valid']) {
        $errors['name'] = $nameValidation['message'];
    } else {
        $validatedData['name'] = $nameValidation['value'];
    }
    
    $phoneValidation = validateConsumerPhone($data['phone']);
    if (!$phoneValidation['valid']) {
        $errors['phone'] = $phoneValidation['message'];
    } else {
        $validatedData['phone'] = $phoneValidation['value'];
    }
    
    $addressValidation = validateAddress($data['address']);
    if (!$addressValidation['valid']) {
        $errors['address'] = $addressValidation['message'];
    } else {
        $validatedData['address'] = $addressValidation['value'];
    }
    
    $readingValidation = validateReadings($data['previous_reading'], $data['current_reading']);
    if (!$readingValidation['valid']) {
        $errors['readings'] = $readingValidation['message'];
    } else {
        $validatedData['previous_reading'] = $data['previous_reading'];
        $validatedData['current_reading'] = $data['current_reading'];
    }
    
    if (!empty($data['reading_date'])) {
        $dateValidation = validateDate($data['reading_date']);
        if (!$dateValidation['valid']) {
            $errors['reading_date'] = $dateValidation['message'];
        } else {
            $validatedData['reading_date'] = $dateValidation['value'];
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $validatedData
    ];
}

?>
