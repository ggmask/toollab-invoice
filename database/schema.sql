-- ToolLab Invoice Database Schema
-- MySQL 8.0+ Compatible

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `tax_rates`;
DROP TABLE IF EXISTS `payment_allocations`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `recurring_occurrences`;
DROP TABLE IF EXISTS `recurring_profiles`;
DROP TABLE IF EXISTS `estimate_items`;
DROP TABLE IF EXISTS `estimates`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `document_sequences`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `business_members`;
DROP TABLE IF EXISTS `businesses`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;

-- 1. Users
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Businesses (Tenant workspace)
CREATE TABLE `businesses` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `legal_name` VARCHAR(200) NOT NULL,
  `trade_name` VARCHAR(200) NULL,
  `business_type` VARCHAR(100) DEFAULT 'Proprietorship',
  `gst_status` ENUM('registered_regular', 'registered_composition', 'unregistered', 'overseas', 'sez') NOT NULL DEFAULT 'registered_regular',
  `gstin` VARCHAR(20) NULL,
  `pan` VARCHAR(15) NULL,
  `address_line1` VARCHAR(255) NULL,
  `address_line2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `state_code` CHAR(2) NULL,
  `pincode` VARCHAR(10) NULL,
  `country` VARCHAR(50) NOT NULL DEFAULT 'India',
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(191) NULL,
  `website` VARCHAR(191) NULL,
  `logo_url` VARCHAR(255) NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `currency_symbol` VARCHAR(5) NOT NULL DEFAULT '₹',
  `invoice_prefix` VARCHAR(20) NOT NULL DEFAULT 'INV',
  `estimate_prefix` VARCHAR(20) NOT NULL DEFAULT 'EST',
  `payment_prefix` VARCHAR(20) NOT NULL DEFAULT 'PAY',
  `number_padding` INT UNSIGNED NOT NULL DEFAULT 4,
  `default_payment_terms` VARCHAR(100) NOT NULL DEFAULT 'Net 15',
  `default_due_days` INT UNSIGNED NOT NULL DEFAULT 15,
  `default_notes` TEXT NULL,
  `default_terms` TEXT NULL,
  `bank_name` VARCHAR(150) NULL,
  `bank_account_no` VARCHAR(60) NULL,
  `bank_ifsc` VARCHAR(20) NULL,
  `bank_branch` VARCHAR(100) NULL,
  `bank_upi_id` VARCHAR(100) NULL,
  `signature_title` VARCHAR(100) DEFAULT 'Authorized Signatory',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Business Members (User-to-business relation)
CREATE TABLE `business_members` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role` ENUM('owner', 'admin', 'member') NOT NULL DEFAULT 'owner',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_business_user` (`business_id`, `user_id`),
  CONSTRAINT `fk_bm_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Customers
CREATE TABLE `customers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `name` VARCHAR(200) NOT NULL,
  `contact_person` VARCHAR(150) NULL,
  `customer_type` ENUM('business', 'individual') NOT NULL DEFAULT 'business',
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(30) NULL,
  `gst_status` ENUM('registered_regular', 'registered_composition', 'unregistered', 'consumer', 'overseas', 'sez') NOT NULL DEFAULT 'registered_regular',
  `gstin` VARCHAR(20) NULL,
  `pan` VARCHAR(15) NULL,
  `billing_address` TEXT NULL,
  `billing_city` VARCHAR(100) NULL,
  `billing_state` VARCHAR(100) NULL,
  `billing_state_code` CHAR(2) NULL,
  `billing_pincode` VARCHAR(10) NULL,
  `billing_country` VARCHAR(50) NOT NULL DEFAULT 'India',
  `shipping_address` TEXT NULL,
  `shipping_city` VARCHAR(100) NULL,
  `shipping_state` VARCHAR(100) NULL,
  `shipping_state_code` CHAR(2) NULL,
  `shipping_pincode` VARCHAR(10) NULL,
  `shipping_country` VARCHAR(50) NOT NULL DEFAULT 'India',
  `payment_terms` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cust_biz_name` (`business_id`, `name`),
  INDEX `idx_cust_biz_gstin` (`business_id`, `gstin`),
  CONSTRAINT `fk_cust_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Products & Services
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(100) NULL,
  `item_type` ENUM('goods', 'services') NOT NULL DEFAULT 'goods',
  `hsn_sac` VARCHAR(20) NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'PCS',
  `selling_price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `gst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 18.0000,
  `tax_category` ENUM('taxable', 'exempt', 'nil_rated', 'non_gst') NOT NULL DEFAULT 'taxable',
  `description` TEXT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_prod_biz_name` (`business_id`, `name`),
  INDEX `idx_prod_biz_sku` (`business_id`, `sku`),
  CONSTRAINT `fk_prod_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Document Sequences (Concurrency-safe counter)
CREATE TABLE `document_sequences` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `document_type` ENUM('invoice', 'estimate', 'payment') NOT NULL,
  `financial_year` VARCHAR(10) NOT NULL,
  `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_biz_doc_fy` (`business_id`, `document_type`, `financial_year`),
  CONSTRAINT `fk_seq_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Invoices
CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `invoice_number` VARCHAR(50) NOT NULL,
  `financial_year` VARCHAR(10) NOT NULL,
  `document_type` ENUM('tax_invoice', 'bill_of_supply') NOT NULL DEFAULT 'tax_invoice',
  `status` ENUM('draft', 'issued', 'partially_paid', 'paid', 'void') NOT NULL DEFAULT 'draft',
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `po_number` VARCHAR(100) NULL,
  `payment_terms` VARCHAR(100) NULL,
  `place_of_supply_state` VARCHAR(100) NULL,
  `place_of_supply_state_code` CHAR(2) NULL,
  `supply_type` ENUM('intra_state', 'inter_state') NOT NULL DEFAULT 'intra_state',
  `is_reverse_charge` TINYINT(1) NOT NULL DEFAULT 0,
  
  -- Supplier snapshot
  `supplier_name` VARCHAR(200) NOT NULL,
  `supplier_trade_name` VARCHAR(200) NULL,
  `supplier_gstin` VARCHAR(20) NULL,
  `supplier_address` TEXT NULL,
  `supplier_city` VARCHAR(100) NULL,
  `supplier_state` VARCHAR(100) NULL,
  `supplier_state_code` CHAR(2) NULL,
  `supplier_pincode` VARCHAR(10) NULL,
  `supplier_phone` VARCHAR(30) NULL,
  `supplier_email` VARCHAR(191) NULL,

  -- Customer snapshot
  `customer_name` VARCHAR(200) NOT NULL,
  `customer_gstin` VARCHAR(20) NULL,
  `customer_billing_address` TEXT NULL,
  `customer_billing_city` VARCHAR(100) NULL,
  `customer_billing_state` VARCHAR(100) NULL,
  `customer_billing_state_code` CHAR(2) NULL,
  `customer_billing_pincode` VARCHAR(10) NULL,
  `customer_shipping_address` TEXT NULL,
  `customer_email` VARCHAR(191) NULL,
  `customer_phone` VARCHAR(30) NULL,

  -- Financial totals
  `subtotal_gross` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `line_discounts_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `invoice_discount_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'flat',
  `invoice_discount_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `invoice_discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `sgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `igst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cess_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `additional_charges` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `additional_charges_label` VARCHAR(100) NULL DEFAULT 'Shipping / Other Charges',
  `rounding_adjustment` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,

  -- Notes & bank
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `bank_details` TEXT NULL,
  `signature_title` VARCHAR(100) NULL,

  -- Lifecycle
  `issued_at` DATETIME NULL,
  `issued_by_user_id` BIGINT UNSIGNED NULL,
  `voided_at` DATETIME NULL,
  `voided_by_user_id` BIGINT UNSIGNED NULL,
  `void_reason` TEXT NULL,
  `created_from_estimate_id` BIGINT UNSIGNED NULL,
  `created_from_recurring_id` BIGINT UNSIGNED NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_inv_biz_num` (`business_id`, `invoice_number`),
  INDEX `idx_inv_biz_date` (`business_id`, `issue_date`),
  INDEX `idx_inv_biz_status` (`business_id`, `status`),
  INDEX `idx_inv_biz_cust` (`business_id`, `customer_id`),
  CONSTRAINT `fk_inv_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Invoice Items
CREATE TABLE `invoice_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `item_order` INT NOT NULL DEFAULT 0,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `hsn_sac` VARCHAR(20) NULL,
  `item_type` ENUM('goods', 'services') NOT NULL DEFAULT 'goods',
  `quantity` DECIMAL(15, 3) NOT NULL DEFAULT 1.000,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'PCS',
  `unit_price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `gross_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `tax_category` ENUM('taxable', 'exempt', 'nil_rated', 'non_gst') NOT NULL DEFAULT 'taxable',
  `gst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 18.0000,
  `cgst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `cgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `sgst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `sgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `igst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `igst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cess_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `cess_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total_tax` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Estimates
CREATE TABLE `estimates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `estimate_number` VARCHAR(50) NOT NULL,
  `financial_year` VARCHAR(10) NOT NULL,
  `status` ENUM('draft', 'sent', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'draft',
  `estimate_date` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `place_of_supply_state` VARCHAR(100) NULL,
  `place_of_supply_state_code` CHAR(2) NULL,
  `supply_type` ENUM('intra_state', 'inter_state') NOT NULL DEFAULT 'intra_state',
  
  -- Supplier snapshot
  `supplier_name` VARCHAR(200) NOT NULL,
  `supplier_trade_name` VARCHAR(200) NULL,
  `supplier_gstin` VARCHAR(20) NULL,
  `supplier_address` TEXT NULL,
  `supplier_city` VARCHAR(100) NULL,
  `supplier_state` VARCHAR(100) NULL,
  `supplier_state_code` CHAR(2) NULL,
  `supplier_pincode` VARCHAR(10) NULL,
  `supplier_phone` VARCHAR(30) NULL,
  `supplier_email` VARCHAR(191) NULL,

  -- Customer snapshot
  `customer_name` VARCHAR(200) NOT NULL,
  `customer_gstin` VARCHAR(20) NULL,
  `customer_billing_address` TEXT NULL,
  `customer_billing_city` VARCHAR(100) NULL,
  `customer_billing_state` VARCHAR(100) NULL,
  `customer_billing_state_code` CHAR(2) NULL,
  `customer_billing_pincode` VARCHAR(10) NULL,
  `customer_email` VARCHAR(191) NULL,
  `customer_phone` VARCHAR(30) NULL,

  -- Financial totals
  `subtotal_gross` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `line_discounts_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `estimate_discount_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'flat',
  `estimate_discount_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `estimate_discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `sgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `igst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cess_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `additional_charges` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `rounding_adjustment` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,

  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `converted_invoice_id` BIGINT UNSIGNED NULL,
  `converted_at` DATETIME NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_est_biz_num` (`business_id`, `estimate_number`),
  INDEX `idx_est_biz_date` (`business_id`, `estimate_date`),
  INDEX `idx_est_biz_status` (`business_id`, `status`),
  CONSTRAINT `fk_est_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_est_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Estimate Items
CREATE TABLE `estimate_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `estimate_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL,
  `item_order` INT NOT NULL DEFAULT 0,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `hsn_sac` VARCHAR(20) NULL,
  `item_type` ENUM('goods', 'services') NOT NULL DEFAULT 'goods',
  `quantity` DECIMAL(15, 3) NOT NULL DEFAULT 1.000,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'PCS',
  `unit_price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `gross_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `taxable_value` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `tax_category` ENUM('taxable', 'exempt', 'nil_rated', 'non_gst') NOT NULL DEFAULT 'taxable',
  `gst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 18.0000,
  `cgst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `cgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `sgst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `sgst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `igst_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `igst_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `cess_rate` DECIMAL(7, 4) NOT NULL DEFAULT 0.0000,
  `cess_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `total_tax` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_ei_estimate` FOREIGN KEY (`estimate_id`) REFERENCES `estimates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Recurring Profiles
CREATE TABLE `recurring_profiles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `profile_name` VARCHAR(150) NOT NULL,
  `frequency` ENUM('weekly', 'biweekly', 'monthly', 'quarterly', 'half_yearly', 'yearly') NOT NULL DEFAULT 'monthly',
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `next_issue_date` DATE NOT NULL,
  `due_days` INT UNSIGNED NOT NULL DEFAULT 15,
  `generation_mode` ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
  `status` ENUM('active', 'paused', 'completed') NOT NULL DEFAULT 'active',
  `items_json` LONGTEXT NOT NULL,
  `po_number` VARCHAR(100) NULL,
  `payment_terms` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `last_generated_at` DATETIME NULL,
  `last_generated_invoice_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rec_biz_next` (`business_id`, `next_issue_date`),
  CONSTRAINT `fk_rec_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Recurring Occurrences (Idempotent tracking)
CREATE TABLE `recurring_occurrences` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recurring_profile_id` BIGINT UNSIGNED NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `invoice_id` BIGINT UNSIGNED NULL,
  `status` ENUM('success', 'failed', 'skipped') NOT NULL DEFAULT 'success',
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_rec_occ` (`recurring_profile_id`, `scheduled_date`),
  CONSTRAINT `fk_ro_profile` FOREIGN KEY (`recurring_profile_id`) REFERENCES `recurring_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payments
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `payment_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `allocated_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `unallocated_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'bank_transfer', 'upi', 'cheque', 'debit_card', 'credit_card', 'other') NOT NULL DEFAULT 'upi',
  `reference_number` VARCHAR(100) NULL,
  `bank_name` VARCHAR(150) NULL,
  `status` ENUM('posted', 'reversed') NOT NULL DEFAULT 'posted',
  `reversal_reason` TEXT NULL,
  `reversed_at` DATETIME NULL,
  `reversed_by_user_id` BIGINT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_pay_biz_num` (`business_id`, `payment_number`),
  INDEX `idx_pay_biz_cust` (`business_id`, `customer_id`),
  INDEX `idx_pay_biz_date` (`business_id`, `payment_date`),
  CONSTRAINT `fk_pay_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Payment Allocations
CREATE TABLE `payment_allocations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Tax Rates Configuration
CREATE TABLE `tax_rates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `rate` DECIMAL(7, 4) NOT NULL,
  `tax_category` ENUM('taxable', 'exempt', 'nil_rated', 'non_gst') NOT NULL DEFAULT 'taxable',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tr_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Audit Logs
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `business_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` BIGINT UNSIGNED NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_biz` (`business_id`, `created_at`),
  INDEX `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Password Resets
CREATE TABLE `password_resets` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(191) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
