<?php

function generateHtmlBill($consumer, $bill) {
    $html = '<div class="bill-container">';
    $html .= '<div class="bill-header">';
    $html .= '<h2 class="text-center">ELECTRICITY BILL</h2>';
    $html .= '<p class="text-center">Date: ' . date('d-M-Y', strtotime($bill['reading_date'])) . '</p>';
    $html .= '</div>';
    
    $html .= '<div class="bill-section">';
    $html .= '<h3>Consumer Details</h3>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Service Number:</span>';
    $html .= '<span class="value"><strong>' . htmlspecialchars($consumer['service_number']) . '</strong></span>';
    $html .= '</div>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Consumer Name:</span>';
    $html .= '<span class="value">' . htmlspecialchars($consumer['name']) . '</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Address:</span>';
    $html .= '<span class="value">' . htmlspecialchars($consumer['address']) . '</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Phone:</span>';
    $html .= '<span class="value">' . htmlspecialchars($consumer['phone']) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="bill-section">';
    $html .= '<h3>Meter Reading</h3>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Previous Reading:</span>';
    $html .= '<span class="value">' . number_format($bill['previous_reading'], 2) . ' units</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Current Reading:</span>';
    $html .= '<span class="value">' . number_format($bill['current_reading'], 2) . ' units</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row highlight">';
    $html .= '<span class="label"><strong>Units Consumed:</strong></span>';
    $html .= '<span class="value"><strong>' . number_format($bill['units_consumed'], 2) . ' units</strong></span>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="bill-section">';
    $html .= '<h3>Charge Breakdown</h3>';
    
    if ($bill['minimum_charge'] > 0) {
        $html .= '<div class="bill-row">';
        $html .= '<span class="label">Minimum Charge (0 units):</span>';
        $html .= '<span class="value">' . formatCurrencyDisplay($bill['minimum_charge']) . '</span>';
        $html .= '</div>';
    } else {
        $slabs = getSlabDescriptions();
        foreach ($bill['slab_breakdown'] as $key => $amount) {
            if ($amount > 0) {
                $html .= '<div class="bill-row">';
                $html .= '<span class="label">' . $slabs[$key] . ':</span>';
                $html .= '<span class="value">' . formatCurrencyDisplay($amount) . '</span>';
                $html .= '</div>';
            }
        }
    }
    
    $html .= '<div class="bill-row highlight">';
    $html .= '<span class="label"><strong>Current Bill Amount:</strong></span>';
    $html .= '<span class="value"><strong>' . formatCurrencyDisplay($bill['current_bill']) . '</strong></span>';
    $html .= '</div>';
    $html .= '</div>';
    
    if ($bill['previous_due'] > 0) {
        $html .= '<div class="bill-section alert-warning">';
        $html .= '<div class="bill-row">';
        $html .= '<span class="label">Previous Pending Bill:</span>';
        $html .= '<span class="value text-danger"><strong>' . formatCurrencyDisplay($bill['previous_due']) . '</strong></span>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '<div class="bill-section bill-total">';
    $html .= '<h3>Payment Information</h3>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Due Date (without fine):</span>';
    $html .= '<span class="value"><strong>' . date('d-M-Y', strtotime($bill['due_date_without_fine'])) . '</strong></span>';
    $html .= '</div>';
    $html .= '<div class="bill-row success">';
    $html .= '<span class="label"><strong>Amount to Pay (before due date):</strong></span>';
    $html .= '<span class="value"><strong class="text-success">' . formatCurrencyDisplay($bill['total_without_fine']) . '</strong></span>';
    $html .= '</div>';
    $html .= '<hr>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">After Due Date:</span>';
    $html .= '<span class="value">' . date('d-M-Y', strtotime($bill['due_date_with_fine'])) . '</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row">';
    $html .= '<span class="label">Fine (after due date):</span>';
    $html .= '<span class="value text-danger">' . formatCurrencyDisplay($bill['fine_amount']) . '</span>';
    $html .= '</div>';
    $html .= '<div class="bill-row danger">';
    $html .= '<span class="label"><strong>Amount with Fine:</strong></span>';
    $html .= '<span class="value"><strong class="text-danger">' . formatCurrencyDisplay($bill['total_with_fine']) . '</strong></span>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="bill-footer">';
    $html .= '<p class="text-center"><em>Please pay your bill before the due date to avoid fine of ₹150/-</em></p>';
    $html .= '<p class="text-center" style="font-size: 0.9em;">Thank you for using our services!</p>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

function generateTextBill($consumer, $bill) {
    $output = "\n";
    $output .= "================================================================================\n";
    $output .= "                         ELECTRICITY BILL                                       \n";
    $output .= "================================================================================\n";
    $output .= "Date: " . date('d-M-Y', strtotime($bill['reading_date'])) . "\n";
    $output .= "--------------------------------------------------------------------------------\n";
    
    $output .= "\nCONSUMER DETAILS:\n";
    $output .= "  Service Number : " . $consumer['service_number'] . "\n";
    $output .= "  Name           : " . $consumer['name'] . "\n";
    $output .= "  Address        : " . $consumer['address'] . "\n";
    $output .= "  Phone          : " . $consumer['phone'] . "\n";
    
    $output .= "\nMETER READING:\n";
    $output .= "  Previous Reading : " . number_format($bill['previous_reading'], 2) . " units\n";
    $output .= "  Current Reading  : " . number_format($bill['current_reading'], 2) . " units\n";
    $output .= "  Units Consumed   : " . number_format($bill['units_consumed'], 2) . " units\n";
    
    $output .= "\nCHARGE BREAKDOWN:\n";
    if ($bill['minimum_charge'] > 0) {
        $output .= "  Minimum Charge (0 units) : " . formatCurrencyDisplay($bill['minimum_charge']) . "\n";
    } else {
        $slabs = getSlabDescriptions();
        foreach ($bill['slab_breakdown'] as $key => $amount) {
            if ($amount > 0) {
                $output .= "  " . str_pad($slabs[$key], 40) . " : " . formatCurrencyDisplay($amount) . "\n";
            }
        }
    }
    $output .= "  " . str_repeat("-", 60) . "\n";
    $output .= "  Current Bill Amount : " . formatCurrencyDisplay($bill['current_bill']) . "\n";
    
    if ($bill['previous_due'] > 0) {
        $output .= "\n*** PREVIOUS PENDING BILL: " . formatCurrencyDisplay($bill['previous_due']) . " ***\n";
    }
    
    $output .= "\nPAYMENT INFORMATION:\n";
    $output .= "  Due Date (without fine) : " . date('d-M-Y', strtotime($bill['due_date_without_fine'])) . "\n";
    $output .= "  Amount to Pay           : " . formatCurrencyDisplay($bill['total_without_fine']) . "\n";
    $output .= "\n  After Due Date          : " . date('d-M-Y', strtotime($bill['due_date_with_fine'])) . "\n";
    $output .= "  Fine Amount             : " . formatCurrencyDisplay($bill['fine_amount']) . "\n";
    $output .= "  Amount with Fine        : " . formatCurrencyDisplay($bill['total_with_fine']) . "\n";
    
    $output .= "\n================================================================================\n";
    $output .= "Please pay your bill before the due date to avoid fine of ₹150/-\n";
    $output .= "Thank you for using our services!\n";
    $output .= "================================================================================\n\n";
    
    return $output;
}

function displayErrors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="alert alert-danger">';
    $html .= '<h4>Please correct the following errors:</h4>';
    $html .= '<ul>';
    foreach ($errors as $field => $message) {
        $html .= '<li><strong>' . ucfirst(str_replace('_', ' ', $field)) . ':</strong> ' . htmlspecialchars($message) . '</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

?>
