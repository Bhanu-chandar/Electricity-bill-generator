# Module Specifications

## 1. bill_input.php

**Purpose:** Get customer details from user

**Input:** None (prompts user)

**Preconditions:** None

**Logic:**
```
1. Ask for service number
2. Ask for consumer name  
3. Ask for phone number
4. Ask for previous reading
5. Ask for current reading
6. Remove extra spaces from all inputs
7. Return all data as array
```

**Output:** Array with all customer details


## 2. bill_validation.php

**Purpose:** Check if all inputs are correct

**Input:** Customer data array

**Preconditions:** Data must exist

**Logic:**
```
1. Check service number (5-10 digits only)
2. Check name (only letters and spaces)
3. Check phone (exactly 10 digits, starts with 6-9)
4. Check current reading >= previous reading
5. If any check fails, add error message
6. Return true if all pass, false if any fail
```

**Output:** 
- True/False
- Array of error messages


## 3. bill_computation.php

**Purpose:** Calculate electricity bill amount

**Input:** Previous reading, current reading, previous due amount

**Preconditions:** 
- Readings must be valid numbers
- Current >= Previous

**Logic:**
```
1. Units = Current - Previous

2. Calculate by slabs:
   - First 50 units: ₹1.5 per unit
   - Next 50 units (51-100): ₹2.5 per unit  
   - Next 50 units (101-150): ₹3.5 per unit
   - Above 150: ₹4.5 per unit

3. Special case: If 0 units, charge ₹25 minimum

4. Add previous due amount

5. Add ₹150 fixed fine

6. Total = Bill + Previous Due + Fine
```

**Output:** 
- Units consumed
- Bill amount
- Previous due
- Fine
- Total amount


## 4. bill_output.php

**Purpose:** Display bill in nice format

**Input:** All calculation results, customer details

**Preconditions:** All data must be available

**Logic:**
```
1. Create HTML table with customer info
2. Show units consumed
3. Show bill calculation
4. Show previous due and fine
5. Show total amount in bold
6. Add styling for better look
```

**Output:** Formatted HTML bill or plain text bill


## Visual Flow

```
User Input → Validate → Calculate → Display Bill
    ↓           ↓           ↓            ↓
  bill_input  bill_valid  bill_comp  bill_output
```


## Integration Flow

```
1. bill_input collects data
2. bill_validation checks it
3. If valid → bill_computation calculates
4. bill_output displays result
5. If invalid → bill_output shows errors
```
