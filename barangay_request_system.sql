CREATE DATABASE IF NOT EXISTS barangay_request_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE barangay_request_system;


-- =========================================
-- USERS
-- =========================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    role ENUM('resident', 'staff', 'admin') NOT NULL DEFAULT 'resident',

    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================
-- RESIDENTS
-- =========================================

CREATE TABLE residents (
    resident_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),

    birth_date DATE NOT NULL,

    sex ENUM('Male', 'Female') NOT NULL,

    address TEXT NOT NULL,

    contact_number VARCHAR(30),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_resident_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- =========================================
-- CERTIFICATIONS
-- =========================================

CREATE TABLE certifications (
    certification_id INT AUTO_INCREMENT PRIMARY KEY,

    certification_name VARCHAR(150) NOT NULL UNIQUE,

    description TEXT,

    processing_days INT NOT NULL DEFAULT 1,

    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================
-- REQUESTS
-- =========================================

CREATE TABLE requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,

    request_reference VARCHAR(50) NOT NULL UNIQUE,

    resident_id INT NOT NULL,

    certification_id INT NOT NULL,

    purpose VARCHAR(255) NOT NULL,

    request_status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Scheduled',
        'Ready for Pickup',
        'Released',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    date_requested DATE NOT NULL,

    time_requested TIME NOT NULL,

    processed_by INT NULL,

    processed_at DATETIME NULL,

    released_at DATETIME NULL,

    remarks TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_request_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_request_certification
        FOREIGN KEY (certification_id)
        REFERENCES certifications(certification_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_request_processed_by
        FOREIGN KEY (processed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);


-- =========================================
-- APPOINTMENTS
-- =========================================

CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,

    request_id INT NOT NULL UNIQUE,

    appointment_date DATE NOT NULL,

    appointment_time TIME NOT NULL,

    status ENUM(
        'Scheduled',
        'Completed',
        'Cancelled',
        'Missed'
    ) NOT NULL DEFAULT 'Scheduled',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_appointment_request
        FOREIGN KEY (request_id)
        REFERENCES requests(request_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- =========================================
-- ACTIVITY LOGS
-- =========================================

CREATE TABLE activity_logs (
    activity_log_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    user_email VARCHAR(255),

    activity_log_action VARCHAR(100) NOT NULL,

    activity_log_status ENUM(
        'success',
        'failed'
    ) NOT NULL DEFAULT 'success',

    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);


-- =========================================
-- INDEXES
-- =========================================

CREATE INDEX idx_requests_resident
ON requests(resident_id);

CREATE INDEX idx_requests_certification
ON requests(certification_id);

CREATE INDEX idx_requests_status
ON requests(request_status);

CREATE INDEX idx_requests_date
ON requests(date_requested);

CREATE INDEX idx_appointments_date
ON appointments(appointment_date);

CREATE INDEX idx_activity_date
ON activity_logs(created_at);


-- =========================================
-- DEFAULT CERTIFICATIONS
-- =========================================

INSERT INTO certifications
(
    certification_name,
    description,
    processing_days,
    fee
)
VALUES
(
    'Barangay Clearance',
    'Certificate issued by the barangay for various legal, employment, and personal purposes.',
    1,
    50.00
),
(
    'Certificate of Residency',
    'Certificate confirming that the resident currently resides within the barangay.',
    1,
    30.00
),
(
    'Certificate of Indigency',
    'Certificate issued to qualified residents who require proof of financial hardship.',
    1,
    0.00
),
(
    'Certificate of Good Moral',
    'Certificate confirming the good moral standing of a resident within the barangay.',
    1,
    30.00
);