<?php

function calculateUnitsConsumed($previousReading, $currentReading) {
    return $currentReading - $previousReading;
}

function calculateBillAmount($units) {
    $breakdown = [
        'units_consumed' => $units,
        'slab_1' => 0,
        'slab_2' => 0,
        'slab_3' => 0,
        'slab_4' => 0,
        'energy_charge' => 0,
        'minimum_charge' => 0,
        'total_amount' => 0
    ];
    
    if ($units == 0) {
        $breakdown['minimum_charge'] = 25.00;
        $breakdown['total_amount'] = 25.00;
        return $breakdown;
    }
    
    $remainingUnits = $units;
    $totalCharge = 0;
    
    if ($remainingUnits > 0) {
        $unitsInSlab = min($remainingUnits, 50);
        $breakdown['slab_1'] = $unitsInSlab * 1.5;
        $totalCharge += $breakdown['slab_1'];
        $remainingUnits -= $unitsInSlab;
    }
    
    if ($remainingUnits > 0) {
        $unitsInSlab = min($remainingUnits, 50);
        $breakdown['slab_2'] = $unitsInSlab * 2.5;
        $totalCharge += $breakdown['slab_2'];
        $remainingUnits -= $unitsInSlab;
    }
    
    if ($remainingUnits > 0) {
        $unitsInSlab = min($remainingUnits, 50);
        $breakdown['slab_3'] = $unitsInSlab * 3.5;
        $totalCharge += $breakdown['slab_3'];
        $remainingUnits -= $unitsInSlab;
    }
    
    if ($remainingUnits > 0) {
        $breakdown['slab_4'] = $remainingUnits * 4.5;
        $totalCharge += $breakdown['slab_4'];
    }
    
    $breakdown['energy_charge'] = round($totalCharge, 2);
    $breakdown['total_amount'] = round($totalCharge, 2);
    
    return $breakdown;
}

function calculateCompleteBill($previousReading, $currentReading, $previousDue = 0, $readingDate = null) {
    if ($readingDate === null) {
        $readingDate = date('Y-m-d');
    }
    
    $units = calculateUnitsConsumed($previousReading, $currentReading);
    
    $billBreakdown = calculateBillAmount($units);
    
    $readingDateObj = new DateTime($readingDate);
    $dueDateWithoutFine = clone $readingDateObj;
    $dueDateWithoutFine->add(new DateInterval('P15D'));
    
    $dueDateWithFine = clone $readingDateObj;
    $dueDateWithFine->add(new DateInterval('P30D'));
    
    $bill = [
        'reading_date' => $readingDate,
        'previous_reading' => $previousReading,
        'current_reading' => $currentReading,
        'units_consumed' => $units,
        'slab_breakdown' => [
            'slab_1' => $billBreakdown['slab_1'],
            'slab_2' => $billBreakdown['slab_2'],
            'slab_3' => $billBreakdown['slab_3'],
            'slab_4' => $billBreakdown['slab_4']
        ],
        'energy_charge' => $billBreakdown['energy_charge'],
        'minimum_charge' => $billBreakdown['minimum_charge'],
        'current_bill' => $billBreakdown['total_amount'],
        'previous_due' => $previousDue,
        'total_without_fine' => round($billBreakdown['total_amount'] + $previousDue, 2),
        'fine_amount' => 150.00,
        'total_with_fine' => round($billBreakdown['total_amount'] + $previousDue + 150.00, 2),
        'due_date_without_fine' => $dueDateWithoutFine->format('Y-m-d'),
        'due_date_with_fine' => $dueDateWithFine->format('Y-m-d')
    ];
    
    return $bill;
}

function getAmountToPay($bill, $currentDate = null) {
    if ($currentDate === null) {
        $currentDate = date('Y-m-d');
    }
    
    $currentDateObj = new DateTime($currentDate);
    $dueDateWithoutFine = new DateTime($bill['due_date_without_fine']);
    
    if ($currentDateObj <= $dueDateWithoutFine) {
        return [
            'amount' => $bill['total_without_fine'],
            'fine_applicable' => false,
            'message' => 'Pay before ' . date('d-M-Y', strtotime($bill['due_date_without_fine'])) . ' to avoid fine'
        ];
    } else {
        return [
            'amount' => $bill['total_with_fine'],
            'fine_applicable' => true,
            'message' => 'Payment overdue. Fine of ₹150 added.'
        ];
    }
}

function formatCurrencyDisplay($amount) {
    return '₹' . number_format($amount, 2);
}

function getSlabDescriptions() {
    return [
        'slab_1' => 'First 50 units @ ₹1.5/unit',
        'slab_2' => 'Next 50 units (51-100) @ ₹2.5/unit',
        'slab_3' => 'Next 50 units (101-150) @ ₹3.5/unit',
        'slab_4' => 'Above 150 units @ ₹4.5/unit'
    ];
}

?>
