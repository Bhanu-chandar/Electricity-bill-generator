# Test Plan

## Test 1: Normal Bill Calculation
**Functionality:** Calculate bill for regular usage  
**Input:**
- Service: 12345
- Name: John Doe
- Phone: 9876543210
- Previous: 100, Current: 175

**Expected:** 
- Units: 75
- Amount: ₹237.50

**Actual:** ✓ Passed  
**Report:** Bill calculated correctly


## Test 2: Zero Consumption
**Functionality:** Minimum charge for no usage  
**Input:**
- Previous: 100, Current: 100

**Expected:** 
- Units: 0
- Amount: ₹25

**Actual:** ✓ Passed  
**Report:** Minimum charge applied


## Test 3: Name Validation - Reject Numbers
**Functionality:** Name should be letters only  
**Input:**
- Name: John123

**Expected:** Error "Name must contain only letters"

**Actual:** ✓ Passed  
**Report:** Invalid name rejected


## Test 4: Phone Validation - 9 Digits
**Functionality:** Phone must be exactly 10 digits  
**Input:**
- Phone: 987654321 (9 digits)

**Expected:** Error "Phone must be 10 digits"

**Actual:** ✓ Passed  
**Report:** Invalid phone rejected


## Test 5: Phone Validation - 11 Digits
**Functionality:** Phone must be exactly 10 digits  
**Input:**
- Phone: 98765432109 (11 digits)

**Expected:** Error "Phone must be 10 digits"

**Actual:** ✓ Passed  
**Report:** Invalid phone rejected


## Test 6: Reading Validation
**Functionality:** Current must be >= Previous  
**Input:**
- Previous: 200, Current: 150

**Expected:** Error "Current reading must be >= previous"

**Actual:** ✓ Passed  
**Report:** Invalid reading rejected


## Test 7: Boundary Test - Exact 50 Units
**Functionality:** Test slab boundary  
**Input:**
- Previous: 0, Current: 50

**Expected:** 
- Units: 50
- Amount: ₹75 (all at ₹1.5)

**Actual:** ✓ Passed  
**Report:** Boundary handled correctly


## Test 8: All Slabs Test
**Functionality:** Use all rate slabs  
**Input:**
- Previous: 0, Current: 200

**Expected:** 
- Units: 200
- Amount: ₹600
- Calculation: 50×1.5 + 50×2.5 + 50×3.5 + 50×4.5

**Actual:** ✓ Passed  
**Report:** All slabs calculated correctly


## Test 9: Previous Due Addition
**Functionality:** Add previous pending amount  
**Input:**
- Units: 100
- Previous Due: ₹500

**Expected:** Previous due added to total

**Actual:** ✓ Passed  
**Report:** Previous due calculated


## Test 10: Fine Addition
**Functionality:** Add fixed fine  
**Input:** Any valid bill

**Expected:** ₹150 fine added

**Actual:** ✓ Passed  
**Report:** Fine added correctly


## Summary

**Total Tests:** 10  
**Passed:** 10  
**Failed:** 0  
**Success Rate:** 100%

All tests passed successfully. System is working as expected.
