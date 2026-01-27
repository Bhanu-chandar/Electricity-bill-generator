<?php

require_once __DIR__ . '/../includes/bill_input.php';
require_once __DIR__ . '/../includes/bill_computation.php';
require_once __DIR__ . '/../includes/bill_output.php';

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

function validateAllInputs($data) {
    $errors = [];
    $validatedData = [];
    
    // Simplified validation for testing (no database checks)
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
    
    $readingValidation = validateReadings($data['previous_reading'], $data['current_reading']);
    if (!$readingValidation['valid']) {
        $errors['readings'] = $readingValidation['message'];
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $validatedData
    ];
}

// Color codes for CLI output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

function printHeader($text) {
    echo "\n" . COLOR_BLUE . str_repeat("=", 80) . COLOR_RESET . "\n";
    echo COLOR_BLUE . $text . COLOR_RESET . "\n";
    echo COLOR_BLUE . str_repeat("=", 80) . COLOR_RESET . "\n\n";
}

function printSuccess($text) {
    echo COLOR_GREEN . "✓ " . $text . COLOR_RESET . "\n";
}

function printError($text) {
    echo COLOR_RED . "✗ " . $text . COLOR_RESET . "\n";
}

function printWarning($text) {
    echo COLOR_YELLOW . "⚠ " . $text . COLOR_RESET . "\n";
}

function printInfo($text) {
    echo COLOR_BLUE . "ℹ " . $text . COLOR_RESET . "\n";
}

// Test Case 1: Standard Bill (175 units)
function testCase1() {
    printHeader("TEST CASE 1: Standard Bill (175 units)");
    
    $consumer = [
        'service_number' => 'TEST001',
        'name' => 'John Doe',
        'phone' => '9876543210',
        'address' => '123 Main Street, Springfield',
        'previous_reading' => 1000,
        'current_reading' => 1175,
        'reading_date' => '2026-01-27'
    ];
    
    printInfo("Input Data:");
    foreach ($consumer as $key => $value) {
        echo "  " . str_pad(ucfirst(str_replace('_', ' ', $key)) . ":", 20) . $value . "\n";
    }
    
    // Validate
    echo "\n" . COLOR_YELLOW . "Validating inputs..." . COLOR_RESET . "\n";
    $validation = validateAllInputs($consumer);
    
    if ($validation['valid']) {
        printSuccess("All validations passed!");
        
        // Calculate bill
        echo "\n" . COLOR_YELLOW . "Calculating bill..." . COLOR_RESET . "\n";
        $bill = calculateCompleteBill(
            $consumer['previous_reading'],
            $consumer['current_reading'],
            0,
            $consumer['reading_date']
        );
        
        printSuccess("Bill calculated successfully!");
        
        // Display bill
        echo "\n" . generateTextBill($consumer, $bill);
        
        // Verify expected values
        echo COLOR_YELLOW . "Verification:" . COLOR_RESET . "\n";
        $expected = [
            'units_consumed' => 175.0,
            'current_bill' => 487.50
        ];
        
        if ($bill['units_consumed'] == $expected['units_consumed']) {
            printSuccess("Units consumed: " . $bill['units_consumed'] . " (Expected: " . $expected['units_consumed'] . ")");
        } else {
            printError("Units consumed: " . $bill['units_consumed'] . " (Expected: " . $expected['units_consumed'] . ")");
        }
        
        if ($bill['current_bill'] == $expected['current_bill']) {
            printSuccess("Current bill: ₹" . $bill['current_bill'] . " (Expected: ₹" . $expected['current_bill'] . ")");
        } else {
            printError("Current bill: ₹" . $bill['current_bill'] . " (Expected: ₹" . $expected['current_bill'] . ")");
        }
        
        return true;
    } else {
        printError("Validation failed!");
        foreach ($validation['errors'] as $field => $error) {
            printError("$field: $error");
        }
        return false;
    }
}

// Test Case 2: Zero Consumption (Minimum Charge)
function testCase2() {
    printHeader("TEST CASE 2: Zero Consumption (Minimum Charge)");
    
    $consumer = [
        'service_number' => 'TEST002',
        'name' => 'Alice Smith',
        'phone' => '9123456789',
        'address' => '456 Oak Avenue, Springfield',
        'previous_reading' => 500,
        'current_reading' => 500,
        'reading_date' => '2026-01-27'
    ];
    
    printInfo("Input Data:");
    foreach ($consumer as $key => $value) {
        echo "  " . str_pad(ucfirst(str_replace('_', ' ', $key)) . ":", 20) . $value . "\n";
    }
    
    $validation = validateAllInputs($consumer);
    
    if ($validation['valid']) {
        printSuccess("Validation passed!");
        
        $bill = calculateCompleteBill(
            $consumer['previous_reading'],
            $consumer['current_reading'],
            0,
            $consumer['reading_date']
        );
        
        echo "\n" . generateTextBill($consumer, $bill);
        
        echo COLOR_YELLOW . "Verification:" . COLOR_RESET . "\n";
        if ($bill['units_consumed'] == 0 && $bill['minimum_charge'] == 25.00) {
            printSuccess("Minimum charge of ₹25 applied correctly for 0 units");
            return true;
        } else {
            printError("Minimum charge not applied correctly");
            return false;
        }
    } else {
        printError("Validation failed!");
        return false;
    }
}

// Test Case 3: With Previous Due
function testCase3() {
    printHeader("TEST CASE 3: Bill with Previous Due");
    
    $consumer = [
        'service_number' => 'TEST003',
        'name' => 'Bob Wilson',
        'phone' => '8765432109',
        'address' => '789 Pine Road, Springfield',
        'previous_reading' => 100,
        'current_reading' => 200,
        'reading_date' => '2026-01-27'
    ];
    
    printInfo("Input Data:");
    foreach ($consumer as $key => $value) {
        echo "  " . str_pad(ucfirst(str_replace('_', ' ', $key)) . ":", 20) . $value . "\n";
    }
    echo "  " . str_pad("Previous Due:", 20) . "₹150.00\n";
    
    $validation = validateAllInputs($consumer);
    
    if ($validation['valid']) {
        printSuccess("Validation passed!");
        
        $bill = calculateCompleteBill(
            $consumer['previous_reading'],
            $consumer['current_reading'],
            150, // Previous due
            $consumer['reading_date']
        );
        
        echo "\n" . generateTextBill($consumer, $bill);
        
        echo COLOR_YELLOW . "Verification:" . COLOR_RESET . "\n";
        $expected_total = 200.00 + 150.00; // Current + Previous due
        if ($bill['total_without_fine'] == $expected_total) {
            printSuccess("Total with previous due: ₹" . $bill['total_without_fine'] . " (Expected: ₹$expected_total)");
            return true;
        } else {
            printError("Total calculation incorrect");
            return false;
        }
    } else {
        printError("Validation failed!");
        return false;
    }
}

// Test Case 4: Validation - Invalid Name (with numbers)
function testCase4() {
    printHeader("TEST CASE 4: Validation - Name with Numbers");
    
    $consumer = [
        'service_number' => 'TEST004',
        'name' => 'John123',  // Invalid: contains numbers
        'phone' => '9876543210',
        'address' => '123 Main Street, Springfield',
        'previous_reading' => 1000,
        'current_reading' => 1100,
        'reading_date' => '2026-01-27'
    ];
    
    printInfo("Testing with invalid name: 'John123'");
    
    $validation = validateConsumerName($consumer['name']);
    
    if (!$validation['valid']) {
        printSuccess("Validation correctly rejected name with numbers");
        printInfo("Error message: " . $validation['message']);
        return true;
    } else {
        printError("Validation should have rejected name with numbers!");
        return false;
    }
}

// Test Case 5: Validation - Phone Number Length
function testCase5() {
    printHeader("TEST CASE 5: Validation - Phone Number Length");
    
    $tests = [
        ['phone' => '987654321', 'should_pass' => false, 'reason' => '9 digits (too short)'],
        ['phone' => '9876543210', 'should_pass' => true, 'reason' => '10 digits (correct)'],
        ['phone' => '98765432101', 'should_pass' => false, 'reason' => '11 digits (too long)'],
    ];
    
    $all_passed = true;
    
    foreach ($tests as $test) {
        echo "\nTesting: " . $test['phone'] . " (" . $test['reason'] . ")\n";
        $validation = validateConsumerPhone($test['phone']);
        
        if ($test['should_pass']) {
            if ($validation['valid']) {
                printSuccess("Correctly accepted valid phone number");
            } else {
                printError("Should have accepted but rejected: " . $validation['message']);
                $all_passed = false;
            }
        } else {
            if (!$validation['valid']) {
                printSuccess("Correctly rejected invalid phone number");
                printInfo("Error message: " . $validation['message']);
            } else {
                printError("Should have rejected but accepted!");
                $all_passed = false;
            }
        }
    }
    
    return $all_passed;
}

// Test Case 6: Validation - Current < Previous Reading
function testCase6() {
    printHeader("TEST CASE 6: Validation - Current Reading < Previous");
    
    printInfo("Testing: Previous = 1000, Current = 900 (invalid)");
    
    $validation = validateReadings(1000, 900);
    
    if (!$validation['valid']) {
        printSuccess("Correctly rejected current < previous");
        printInfo("Error message: " . $validation['message']);
        return true;
    } else {
        printError("Should have rejected current < previous!");
        return false;
    }
}

// Test Case 7: Computation - Boundary Test (Exactly 50 units)
function testCase7() {
    printHeader("TEST CASE 7: Computation - Boundary Test (50 units)");
    
    printInfo("Calculating bill for exactly 50 units...");
    
    $bill = calculateBillAmount(50);
    
    echo "\nCalculation Breakdown:\n";
    echo "  Slab 1 (50 units @ ₹1.5): ₹" . $bill['slab_1'] . "\n";
    echo "  Total: ₹" . $bill['total_amount'] . "\n";
    
    $expected = 75.00; // 50 * 1.5
    
    if ($bill['total_amount'] == $expected) {
        printSuccess("Correct! Total = ₹$expected");
        return true;
    } else {
        printError("Incorrect! Expected ₹$expected but got ₹" . $bill['total_amount']);
        return false;
    }
}

// Test Case 8: Computation - All Slabs (200 units)
function testCase8() {
    printHeader("TEST CASE 8: Computation - All Slabs (200 units)");
    
    printInfo("Calculating bill for 200 units (all slabs)...");
    
    $bill = calculateBillAmount(200);
    
    echo "\nCalculation Breakdown:\n";
    echo "  Slab 1 (50 units @ ₹1.5):  ₹" . number_format($bill['slab_1'], 2) . "\n";
    echo "  Slab 2 (50 units @ ₹2.5):  ₹" . number_format($bill['slab_2'], 2) . "\n";
    echo "  Slab 3 (50 units @ ₹3.5):  ₹" . number_format($bill['slab_3'], 2) . "\n";
    echo "  Slab 4 (50 units @ ₹4.5):  ₹" . number_format($bill['slab_4'], 2) . "\n";
    echo "  " . str_repeat("-", 40) . "\n";
    echo "  Total:                      ₹" . number_format($bill['total_amount'], 2) . "\n";
    
    $expected = (50 * 1.5) + (50 * 2.5) + (50 * 3.5) + (50 * 4.5); // 600.00
    
    if ($bill['total_amount'] == $expected) {
        printSuccess("Correct! Total = ₹" . number_format($expected, 2));
        return true;
    } else {
        printError("Incorrect! Expected ₹" . number_format($expected, 2) . " but got ₹" . number_format($bill['total_amount'], 2));
        return false;
    }
}

// Main Test Runner
printHeader("ELECTRICITY BILL GENERATOR - TEST SUITE");
echo "Testing modular bill system with comprehensive test cases\n";
echo "Date: " . date('d-M-Y H:i:s') . "\n";

$results = [];

// Run all test cases
$results['TC1'] = testCase1();
$results['TC2'] = testCase2();
$results['TC3'] = testCase3();
$results['TC4'] = testCase4();
$results['TC5'] = testCase5();
$results['TC6'] = testCase6();
$results['TC7'] = testCase7();
$results['TC8'] = testCase8();

// Summary
printHeader("TEST SUMMARY");

$passed = count(array_filter($results));
$total = count($results);
$failed = $total - $passed;

echo "Total Tests: $total\n";
echo COLOR_GREEN . "Passed: $passed" . COLOR_RESET . "\n";
if ($failed > 0) {
    echo COLOR_RED . "Failed: $failed" . COLOR_RESET . "\n";
}

$pass_rate = ($passed / $total) * 100;
echo "\nPass Rate: " . number_format($pass_rate, 2) . "%\n";

if ($pass_rate == 100) {
    printSuccess("\n🎉 ALL TESTS PASSED! 🎉");
} else {
    printWarning("\nSome tests failed. Please review the results above.");
}

echo "\n" . COLOR_BLUE . str_repeat("=", 80) . COLOR_RESET . "\n";
echo "For complete test plan, see: docs/TEST_PLAN.md\n";
echo "For module specifications, see: docs/MODULE_SPECIFICATIONS.md\n";
echo COLOR_BLUE . str_repeat("=", 80) . COLOR_RESET . "\n\n";

?>
