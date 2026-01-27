<?php

function getConsumerInput() {
    return [
        'service_number' => isset($_POST['service_number']) ? sanitizeInput($_POST['service_number']) : '',
        'name' => isset($_POST['name']) ? sanitizeInput($_POST['name']) : '',
        'phone' => isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '',
        'address' => isset($_POST['address']) ? sanitizeInput($_POST['address']) : '',
        'previous_reading' => isset($_POST['previous_reading']) ? floatval($_POST['previous_reading']) : 0,
        'current_reading' => isset($_POST['current_reading']) ? floatval($_POST['current_reading']) : 0,
        'reading_date' => isset($_POST['reading_date']) ? sanitizeInput($_POST['reading_date']) : '',
        'previous_due' => isset($_POST['previous_due']) ? floatval($_POST['previous_due']) : 0
    ];
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function promptInput($prompt, $validator = null) {
    do {
        echo $prompt;
        $input = trim(fgets(STDIN));
        
        if ($validator === null) {
            return $input;
        }
        
        $validation = $validator($input);
        if ($validation['valid']) {
            return isset($validation['value']) ? $validation['value'] : $input;
        }
        
        echo "Error: " . $validation['message'] . " Please try again.\n";
    } while (true);
}

?>
