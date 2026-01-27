<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/bill_input.php';
require_once __DIR__ . '/../includes/bill_validation.php';
require_once __DIR__ . '/../includes/bill_computation.php';
require_once __DIR__ . '/../includes/bill_output.php';

$errors = [];
$success = '';
$bill = null;
$consumer = null;
$showBill = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = getConsumerInput();
    
    $validation = validateAllInputs($inputData);
    
    if ($validation['valid']) {
        $consumer = $validation['data'];
        
        $bill = calculateCompleteBill(
            $consumer['previous_reading'],
            $consumer['current_reading'],
            $inputData['previous_due'],
            $consumer['reading_date']
        );
        
        $showBill = true;
        $success = 'Bill generated successfully!';
    } else {
        $errors = $validation['errors'];
        $consumer = $inputData;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Bill Generator - Electricity Bill System</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .standalone-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .bill-generator-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .bill-generator-header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .bill-generator-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h4 {
            margin-top: 0;
            color: #1976D2;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .rate-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .rate-table th,
        .rate-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .rate-table th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
        .rate-table tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="standalone-container">
        <div class="bill-generator-header">
            <h1>⚡ Electricity Bill Generator</h1>
            <p>Standalone Modular Bill System - Task 1 & Task 2 Implementation</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <?php echo displayErrors($errors); ?>
        <?php endif; ?>
        
        <?php if ($success && $showBill): ?>
            <?php echo displaySuccess($success); ?>
        <?php endif; ?>
        
        <?php if ($showBill && $bill): ?>
            <!-- Display Generated Bill -->
            <div class="card mb-3">
                <?php echo generateHtmlBill($consumer, $bill); ?>
            </div>
            
            <div class="text-center mb-3">
                <a href="/standalone/bill_generator.php" class="btn btn-primary btn-lg">Generate Another Bill</a>
                <button onclick="window.print()" class="btn btn-secondary btn-lg">Print Bill</button>
            </div>
        <?php else: ?>
            <!-- Input Form -->
            <div class="two-column">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Consumer & Meter Details</h3>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="service_number">
                                    Service Number <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="service_number" 
                                       name="service_number" 
                                       class="form-control <?php echo isset($errors['service_number']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($consumer['service_number']) ? htmlspecialchars($consumer['service_number']) : ''; ?>"
                                       placeholder="e.g., EB12345"
                                       required>
                                <?php if (isset($errors['service_number'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['service_number']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Unique identifier for the consumer</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="name">
                                    Consumer Name <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($consumer['name']) ? htmlspecialchars($consumer['name']) : ''; ?>"
                                       placeholder="e.g., John Doe"
                                       pattern="[a-zA-Z\s]+"
                                       title="Only alphabets and spaces allowed"
                                       required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Only alphabets and spaces (no numbers/special characters)</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="phone">
                                    Phone Number <span class="required">*</span>
                                </label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($consumer['phone']) ? htmlspecialchars($consumer['phone']) : ''; ?>"
                                       placeholder="10-digit number"
                                       pattern="[6-9][0-9]{9}"
                                       maxlength="10"
                                       title="Exactly 10 digits required"
                                       required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Must be exactly 10 digits</div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="address">
                                    Address <span class="required">*</span>
                                </label>
                                <textarea id="address" 
                                          name="address" 
                                          class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                                          rows="3"
                                          placeholder="Complete address"
                                          required><?php echo isset($consumer['address']) ? htmlspecialchars($consumer['address']) : ''; ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="mt-3 mb-2">Meter Reading</h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="previous_reading">
                                        Previous Reading <span class="required">*</span>
                                    </label>
                                    <input type="number" 
                                           id="previous_reading" 
                                           name="previous_reading" 
                                           class="form-control <?php echo isset($errors['readings']) ? 'is-invalid' : ''; ?>"
                                           value="<?php echo isset($consumer['previous_reading']) ? $consumer['previous_reading'] : '0'; ?>"
                                           min="0"
                                           step="0.01"
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="current_reading">
                                        Current Reading <span class="required">*</span>
                                    </label>
                                    <input type="number" 
                                           id="current_reading" 
                                           name="current_reading" 
                                           class="form-control <?php echo isset($errors['readings']) ? 'is-invalid' : ''; ?>"
                                           value="<?php echo isset($consumer['current_reading']) ? $consumer['current_reading'] : ''; ?>"
                                           min="0"
                                           step="0.01"
                                           required>
                                </div>
                            </div>
                            
                            <?php if (isset($errors['readings'])): ?>
                                <div class="alert alert-danger"><?php echo $errors['readings']; ?></div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label class="form-label" for="reading_date">
                                    Reading Date <span class="required">*</span>
                                </label>
                                <input type="date" 
                                       id="reading_date" 
                                       name="reading_date" 
                                       class="form-control <?php echo isset($errors['reading_date']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo isset($consumer['reading_date']) ? $consumer['reading_date'] : date('Y-m-d'); ?>"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       required>
                                <?php if (isset($errors['reading_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['reading_date']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="previous_due">
                                    Previous Pending Amount (if any)
                                </label>
                                <input type="number" 
                                       id="previous_due" 
                                       name="previous_due" 
                                       class="form-control"
                                       value="<?php echo isset($consumer['previous_due']) ? $consumer['previous_due'] : '0'; ?>"
                                       min="0"
                                       step="0.01">
                                <div class="form-text">Enter 0 if no pending dues</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg btn-block mt-3">
                                Generate Bill
                            </button>
                        </form>
                    </div>
                </div>
                
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rate Structure</h3>
                        </div>
                        
                        <table class="rate-table">
                            <thead>
                                <tr>
                                    <th>Slab</th>
                                    <th>Units</th>
                                    <th>Rate per Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Slab 1</td>
                                    <td>First 50 units</td>
                                    <td>₹1.5</td>
                                </tr>
                                <tr>
                                    <td>Slab 2</td>
                                    <td>Next 50 (51-100)</td>
                                    <td>₹2.5</td>
                                </tr>
                                <tr>
                                    <td>Slab 3</td>
                                    <td>Next 50 (101-150)</td>
                                    <td>₹3.5</td>
                                </tr>
                                <tr>
                                    <td>Slab 4</td>
                                    <td>Above 150</td>
                                    <td>₹4.5</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="info-box">
                            <h4>⚠️ Special Cases</h4>
                            <ul>
                                <li><strong>Zero Consumption:</strong> Minimum charge of ₹25 applicable</li>
                                <li><strong>Fine:</strong> Fixed ₹150 if payment after due date</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Validation Rules</h3>
                        </div>
                        
                        <div class="info-box">
                            <h4>✓ Input Requirements</h4>
                            <ul>
                                <li><strong>Service Number:</strong> Must be unique (no duplicates)</li>
                                <li><strong>Name:</strong> Alphabets and spaces only</li>
                                <li><strong>Phone:</strong> Exactly 10 digits (no more, no less)</li>
                                <li><strong>Current Reading:</strong> Must be ≥ Previous reading</li>
                            </ul>
                        </div>
                        
                        <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
                            <h4 style="color: #856404;">📅 Payment Timeline</h4>
                            <ul>
                                <li><strong>15 Days:</strong> Pay without fine</li>
                                <li><strong>After 15 Days:</strong> ₹150 fine added</li>
                                <li><strong>Final Date:</strong> 30 days from bill date</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">About This Implementation</h3>
            </div>
            <div style="padding: 20px;">
                <h4>Modular Design (Task 1 & 2)</h4>
                <p>This system uses separate modules for different functionalities:</p>
                <ul>
                    <li><strong>bill_input.php:</strong> Input handling and sanitization</li>
                    <li><strong>bill_validation.php:</strong> All validation rules with error handling</li>
                    <li><strong>bill_computation.php:</strong> Slab-based billing calculations</li>
                    <li><strong>bill_output.php:</strong> Formatted bill display (HTML/Text)</li>
                </ul>
                
                <h4>Quality Characteristics</h4>
                <ul>
                    <li><strong>Usability:</strong> Clear error messages with re-prompting</li>
                    <li><strong>Efficiency:</strong> Optimized calculations, minimal database queries</li>
                    <li><strong>Reusability:</strong> All modules can be used independently</li>
                    <li><strong>Maintainability:</strong> Well-documented code with clear separation</li>
                </ul>
                
                <p class="mt-2"><em>For complete documentation, test plans, and module specifications, please refer to the /docs folder.</em></p>
            </div>
        </div>
    </div>
    
    <script>
        // Real-time unit calculation
        const prevReading = document.getElementById('previous_reading');
        const currReading = document.getElementById('current_reading');
        
        function calculateUnits() {
            const prev = parseFloat(prevReading.value) || 0;
            const curr = parseFloat(currReading.value) || 0;
            const units = curr - prev;
            
            if (units < 0) {
                currReading.setCustomValidity('Current reading must be greater than or equal to previous reading');
            } else {
                currReading.setCustomValidity('');
            }
        }
        
        if (prevReading && currReading) {
            prevReading.addEventListener('input', calculateUnits);
            currReading.addEventListener('input', calculateUnits);
        }
    </script>
</body>
</html>
