<?php
class SecurityValidator {
    private $logger;
    private $errors = [];
    
    public function __construct($logger = null) {
        $this->logger = $logger;
    }
    
    /**
     * Validate email format
     */
    public function validateEmail($email, $user_id = null, $username = null) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logValidationFailure($user_id, $username, 'email', $email, 'valid email format');
            $this->errors[] = "Invalid email format";
            return false;
        }
        
        // Check length
        if (strlen($email) > 255) {
            $this->logValidationFailure($user_id, $username, 'email', $email, 'maximum length 255');
            $this->errors[] = "Email is too long";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password complexity
     */
    public function validatePassword($password, $user_id = null, $username = null) {
        // Check minimum length (8 characters)
        if (strlen($password) < 8) {
            $this->logValidationFailure($user_id, $username, 'password', '***', 'minimum length 8');
            $this->errors[] = "Password must be at least 8 characters long";
            return false;
        }
        
        // Check maximum length (128 characters)
        if (strlen($password) > 128) {
            $this->logValidationFailure($user_id, $username, 'password', '***', 'maximum length 128');
            $this->errors[] = "Password is too long";
            return false;
        }
        
        // Check complexity requirements - simpler approach
        $has_lowercase = preg_match('/[a-z]/', $password);
        $has_uppercase = preg_match('/[A-Z]/', $password);
        $has_number = preg_match('/[0-9]/', $password);
        $has_special = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
        
        if (!$has_lowercase || !$has_uppercase || !$has_number || !$has_special) {
            $this->logValidationFailure($user_id, $username, 'password', '***', 'complexity requirements');
            $this->errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate username
     */
    public function validateUsername($username, $user_id = null, $current_username = null) {
        // Check length (3-50 characters)
        if (strlen($username) < 3 || strlen($username) > 50) {
            $this->logValidationFailure($user_id, $current_username, 'username', $username, 'length 3-50 characters');
            $this->errors[] = "Username must be between 3 and 50 characters";
            return false;
        }
        
        // Check format (alphanumeric and underscores only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->logValidationFailure($user_id, $current_username, 'username', $username, 'alphanumeric and underscores only');
            $this->errors[] = "Username can only contain letters, numbers, and underscores";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate product name
     */
    public function validateProductName($product_name, $user_id = null, $username = null) {
        // Check length (1-100 characters)
        if (empty($product_name) || strlen($product_name) > 100) {
            $this->logValidationFailure($user_id, $username, 'product_name', $product_name, 'length 1-100 characters');
            $this->errors[] = "Product name must be between 1 and 100 characters";
            return false;
        }
        
        // Check for potentially dangerous characters
        if (preg_match('/[<>"\']/', $product_name)) {
            $this->logValidationFailure($user_id, $username, 'product_name', $product_name, 'no HTML tags or quotes');
            $this->errors[] = "Product name contains invalid characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate quantity (positive integer)
     */
    public function validateQuantity($quantity, $user_id = null, $username = null) {
        if (!is_numeric($quantity) || $quantity < 0 || $quantity != (int)$quantity) {
            $this->logValidationFailure($user_id, $username, 'quantity', $quantity, 'positive integer');
            $this->errors[] = "Quantity must be a positive whole number";
            return false;
        }
        
        // Check maximum value (prevent integer overflow)
        if ($quantity > 999999) {
            $this->logValidationFailure($user_id, $username, 'quantity', $quantity, 'maximum value 999999');
            $this->errors[] = "Quantity is too large";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate price (positive decimal)
     */
    public function validatePrice($price, $user_id = null, $username = null) {
        if (!is_numeric($price) || $price < 0) {
            $this->logValidationFailure($user_id, $username, 'price', $price, 'positive decimal');
            $this->errors[] = "Price must be a positive number";
            return false;
        }
        
        // Check maximum value
        if ($price > 999999.99) {
            $this->logValidationFailure($user_id, $username, 'price', $price, 'maximum value 999999.99');
            $this->errors[] = "Price is too large";
            return false;
        }
        
        // Check decimal places (max 2)
        if (strpos($price, '.') !== false) {
            $decimal_places = strlen(substr(strrchr($price, "."), 1));
            if ($decimal_places > 2) {
                $this->logValidationFailure($user_id, $username, 'price', $price, 'maximum 2 decimal places');
                $this->errors[] = "Price can have maximum 2 decimal places";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate supplier name
     */
    public function validateSupplier($supplier, $user_id = null, $username = null) {
        // Check length (1-100 characters)
        if (empty($supplier) || strlen($supplier) > 100) {
            $this->logValidationFailure($user_id, $username, 'supplier', $supplier, 'length 1-100 characters');
            $this->errors[] = "Supplier name must be between 1 and 100 characters";
            return false;
        }
        
        // Check for potentially dangerous characters
        if (preg_match('/[<>"\']/', $supplier)) {
            $this->logValidationFailure($user_id, $username, 'supplier', $supplier, 'no HTML tags or quotes');
            $this->errors[] = "Supplier name contains invalid characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate customer name
     */
    public function validateCustomerName($customer_name, $user_id = null, $username = null) {
        // Check length (1-100 characters)
        if (empty($customer_name) || strlen($customer_name) > 100) {
            $this->logValidationFailure($user_id, $username, 'customer_name', $customer_name, 'length 1-100 characters');
            $this->errors[] = "Customer name must be between 1 and 100 characters";
            return false;
        }
        
        // Check for potentially dangerous characters
        if (preg_match('/[<>"\']/', $customer_name)) {
            $this->logValidationFailure($user_id, $username, 'customer_name', $customer_name, 'no HTML tags or quotes');
            $this->errors[] = "Customer name contains invalid characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate security question answer
     */
    public function validateSecurityAnswer($answer, $user_id = null, $username = null) {
        // Check length (1-100 characters)
        if (empty($answer) || strlen($answer) > 100) {
            $this->logValidationFailure($user_id, $username, 'security_answer', $answer, 'length 1-100 characters');
            $this->errors[] = "Security answer must be between 1 and 100 characters";
            return false;
        }
        
        // Check for potentially dangerous characters
        if (preg_match('/[<>"\']/', $answer)) {
            $this->logValidationFailure($user_id, $username, 'security_answer', $answer, 'no HTML tags or quotes');
            $this->errors[] = "Security answer contains invalid characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate user ID (positive integer)
     */
    public function validateUserId($user_id, $current_user_id = null, $username = null) {
        if (!is_numeric($user_id) || $user_id < 1 || $user_id != (int)$user_id) {
            $this->logValidationFailure($current_user_id, $username, 'user_id', $user_id, 'positive integer');
            $this->errors[] = "Invalid user ID";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate order ID (positive integer)
     */
    public function validateOrderId($order_id, $user_id = null, $username = null) {
        if (!is_numeric($order_id) || $order_id < 1 || $order_id != (int)$order_id) {
            $this->logValidationFailure($user_id, $username, 'order_id', $order_id, 'positive integer');
            $this->errors[] = "Invalid order ID";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate product ID (positive integer)
     */
    public function validateProductId($product_id, $user_id = null, $username = null) {
        if (!is_numeric($product_id) || $product_id < 1 || $product_id != (int)$product_id) {
            $this->logValidationFailure($user_id, $username, 'product_id', $product_id, 'positive integer');
            $this->errors[] = "Invalid product ID";
            return false;
        }
        
        return true;
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if there are validation errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear all validation errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Log validation failure
     */
    private function logValidationFailure($user_id, $username, $field, $value, $rule) {
        if ($this->logger) {
            $this->logger->logValidationFailure(
                $user_id,
                $username ?? 'Unknown',
                $field,
                $value,
                $rule
            );
        }
    }
}
?>
