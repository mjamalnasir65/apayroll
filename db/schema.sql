-- Database schema for Foreign Worker Payroll PWA
CREATE DATABASE IF NOT EXISTS apayroll;
USE apayroll;

-- Users table (for authentication)
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'worker', 'employer') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- Worker table
CREATE TABLE IF NOT EXISTS Worker (
    worker_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    passport_no VARCHAR(50) UNIQUE NOT NULL,
    nationality VARCHAR(50) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    mobile_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    wallet_id VARCHAR(100) NOT NULL,
    wallet_brand VARCHAR(50) NOT NULL,
    expiry_date DATE NOT NULL,
    employer_name VARCHAR(100) NOT NULL,
    employer_roc VARCHAR(50) NOT NULL,
    sector ENUM('construction', 'manufacturing', 'services', 'plantation', 'agriculture') NOT NULL,
    contract_start DATE NOT NULL,
    copy_passport VARCHAR(255) NOT NULL,
    copy_permit VARCHAR(255) NOT NULL,
    photo VARCHAR(255) NOT NULL,
    copy_contract VARCHAR(255) NOT NULL,
    monthly_salary DECIMAL(10,2) NOT NULL,
    subscription_status ENUM('active', 'expired', 'pending') NOT NULL DEFAULT 'pending',
    subscription_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
) ENGINE=InnoDB;

-- SubscriptionPayment table
CREATE TABLE IF NOT EXISTS SubscriptionPayment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    wallet_ref VARCHAR(100),
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES Worker(worker_id)
) ENGINE=InnoDB;

-- Employer table
CREATE TABLE IF NOT EXISTS Employer (
    employer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    employer_roc VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    sector ENUM('construction', 'manufacturing', 'services', 'plantation', 'agriculture') NOT NULL,
    contact_email VARCHAR(100),
    mobile VARCHAR(20),
    access_level ENUM('free', 'premium') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
) ENGINE=InnoDB;

-- SalaryLog table
CREATE TABLE IF NOT EXISTS SalaryLog (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    employer_id INT,
    month DATE NOT NULL,
    expected_amount DECIMAL(10,2) NOT NULL,
    receipt_url VARCHAR(255),
    status ENUM('pending', 'received', 'disputed') NOT NULL DEFAULT 'pending',
    employer_note TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES Worker(worker_id),
    FOREIGN KEY (employer_id) REFERENCES Employer(employer_id)
) ENGINE=InnoDB;

-- Create indexes for better performance
CREATE INDEX idx_worker_passport ON Worker(passport_no);
CREATE INDEX idx_salary_month ON SalaryLog(month);
CREATE INDEX idx_subscription_status ON Worker(subscription_status);
CREATE INDEX idx_employer_roc ON Employer(employer_roc);
CREATE INDEX idx_user_email ON Users(email);
