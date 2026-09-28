-- MEDITrack FINAL DATABASE - FRESH INSTALL
-- WARNING: This recreates the meditrack database from scratch.

DROP DATABASE IF EXISTS meditrack;
CREATE DATABASE meditrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE meditrack;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient','doctor','receptionist','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin account for project demonstration and evaluation.
-- Email: admin@meditrack.com
-- Password: Admin@123
INSERT INTO users (email, password, role)
VALUES (
    'admin@meditrack.com',
    '$2y$12$VMwAUcnq6mnAUeie808JdOupsJNZYoH1c1EQtXrJE5uOcytSPM5Xa',
    'admin'
);

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NULL,
    address TEXT NULL,
    age INT NULL,
    gender ENUM('Male','Female','Other') NULL,
    blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    emergency_contact VARCHAR(20) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(120) NULL,
    gender ENUM('Male','Female','Other') NULL,
    qualification VARCHAR(150) NULL,
    license_number VARCHAR(100) NULL,
    experience INT DEFAULT 0,
    department VARCHAR(100) NULL,
    room_number VARCHAR(50) NULL,
    bio TEXT NULL,
    availability VARCHAR(255) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    available_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    note VARCHAR(255) NULL,
    status ENUM('Available','Unavailable') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY uq_doctor_availability_day (doctor_id, available_date),
    UNIQUE KEY uq_doctor_availability_id_doctor (id, doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    availability_id INT NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('Available','Booked','Closed') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    CONSTRAINT fk_doctor_slots_availability
        FOREIGN KEY (availability_id, doctor_id)
        REFERENCES doctor_availability(id, doctor_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_appointment_counters (
    doctor_id INT PRIMARY KEY,
    last_serial INT NOT NULL DEFAULT 0,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    doctor_serial INT NOT NULL,
    slot_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('Booked','Completed','Cancelled','Did not appear') NOT NULL DEFAULT 'Booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES doctor_slots(id) ON DELETE CASCADE,
    UNIQUE KEY uq_doctor_appointment_serial (doctor_id, doctor_serial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE symptoms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    symptom VARCHAR(150) NOT NULL,
    severity INT NOT NULL,
    duration VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    symptom_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NULL,
    followup_date DATE NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    status ENUM('Active','Completed','Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NULL,
    note TEXT NULL,
    status ENUM('Active','Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prescription_medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_name VARCHAR(150) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    instructions VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emergency_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emergency_code VARCHAR(30) NOT NULL UNIQUE,
    patient_name VARCHAR(100) NOT NULL,
    age INT NULL,
    gender ENUM('Male','Female','Other') NULL,
    phone VARCHAR(30) NULL,
    emergency_contact VARCHAR(30) NULL,
    emergency_type VARCHAR(120) NOT NULL,
    arrival_time DATETIME NOT NULL,
    notes VARCHAR(255) NULL,
    status ENUM('Waiting','Completed','Cancelled') DEFAULT 'Waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    staff_role VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    department VARCHAR(100) NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NULL,
    doctor_id INT NULL,
    staff_id INT NULL,
    type ENUM('Patient Charge','Doctor Payment','Staff Payment') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    CONSTRAINT fk_billing_staff
        FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment_sequences (
    department_code VARCHAR(10) PRIMARY KEY,
    last_number INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(20) NOT NULL UNIQUE,
    equipment_type_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    purchase_date DATE NULL,
    condition_status ENUM('Good','Needs Maintenance','Out of Service') DEFAULT 'Good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_type
        FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_item_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_item_type_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    minimum_level INT NOT NULL DEFAULT 0,
    supplier VARCHAR(150) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_stock_item_type
        FOREIGN KEY (stock_item_type_id) REFERENCES stock_item_types(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    action_type ENUM('Created','Updated') NOT NULL,
    old_item_name VARCHAR(120) NULL,
    new_item_name VARCHAR(120) NULL,
    old_category VARCHAR(100) NULL,
    new_category VARCHAR(100) NULL,
    old_quantity INT NULL,
    new_quantity INT NULL,
    old_minimum_level INT NULL,
    new_minimum_level INT NULL,
    old_supplier VARCHAR(150) NULL,
    new_supplier VARCHAR(150) NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    change_quantity INT NOT NULL,
    transaction_type ENUM('Stock In','Stock Out','Adjustment') NOT NULL,
    note VARCHAR(255) NULL,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Starter master catalogs. Admin can add more types from the application.
INSERT IGNORE INTO equipment_types(name) VALUES
('MRI Machine'),
('CT Scanner'),
('X-Ray Machine'),
('Ultrasound Machine'),
('ECG Machine'),
('Echocardiography Machine'),
('EEG Machine'),
('EMG Machine'),
('Mammography Machine'),
('C-Arm Machine'),
('Bone Densitometer'),
('Ventilator'),
('Defibrillator'),
('Patient Monitor'),
('Pulse Oximeter'),
('Nebulizer'),
('Infusion Pump'),
('Syringe Pump'),
('Anesthesia Machine'),
('Electrosurgical Unit'),
('Dialysis Machine'),
('Endoscopy System'),
('Gastroscope'),
('Colonoscope'),
('Operating Table'),
('Surgical Light'),
('Autoclave'),
('Sterilizer'),
('Suction Machine'),
('Oxygen Concentrator'),
('Oxygen Cylinder'),
('Hospital Bed'),
('ICU Bed'),
('Wheelchair'),
('Stretcher'),
('Examination Table'),
('Fetal Monitor'),
('Infant Incubator'),
('Phototherapy Unit'),
('Dental Chair'),
('Dental X-Ray Machine'),
('Microscope'),
('Centrifuge'),
('Hematology Analyzer'),
('Biochemistry Analyzer'),
('Blood Gas Analyzer'),
('Medical Refrigerator'),
('Medical Freezer'),
('Laboratory Incubator'),
('Blood Bank Refrigerator');

INSERT IGNORE INTO stock_item_types(name,category) VALUES
('Paracetamol','Medicine'),
('Ibuprofen','Medicine'),
('Aspirin','Medicine'),
('Amoxicillin','Medicine'),
('Azithromycin','Medicine'),
('Ceftriaxone','Medicine'),
('Metronidazole','Medicine'),
('Omeprazole','Medicine'),
('Pantoprazole','Medicine'),
('Ondansetron','Medicine'),
('Salbutamol','Medicine'),
('Insulin','Medicine'),
('Oral Rehydration Salts (ORS)','Medicine'),
('Normal Saline','Medicine'),
('Ringer Lactate','Medicine'),
('Dextrose 5%','Medicine'),
('Surgical Gloves','PPE'),
('Examination Gloves','PPE'),
('Surgical Masks','PPE'),
('N95 Respirators','PPE'),
('Face Shields','PPE'),
('Disposable Gowns','PPE'),
('Surgical Caps','PPE'),
('Shoe Covers','PPE'),
('Protective Aprons','PPE'),
('2 ml Syringes','Medical Supplies'),
('5 ml Syringes','Medical Supplies'),
('10 ml Syringes','Medical Supplies'),
('20 ml Syringes','Medical Supplies'),
('IV Cannula','Medical Supplies'),
('IV Infusion Set','Medical Supplies'),
('Blood Transfusion Set','Medical Supplies'),
('Sterile Gauze','Medical Supplies'),
('Elastic Bandage','Medical Supplies'),
('Crepe Bandage','Medical Supplies'),
('Adhesive Medical Tape','Medical Supplies'),
('Cotton Roll','Medical Supplies'),
('Sutures','Medical Supplies'),
('Foley Catheter','Medical Supplies'),
('Urinary Catheter','Medical Supplies'),
('Nasogastric Tube','Medical Supplies'),
('Oxygen Mask','Medical Supplies'),
('Nasal Cannula','Medical Supplies'),
('Specimen Container','Medical Supplies'),
('Tongue Depressor','Medical Supplies'),
('ECG Electrodes','Medical Supplies'),
('Disposable Bed Sheets','Medical Supplies'),
('Test Tubes','Lab Supplies'),
('EDTA Tubes','Lab Supplies'),
('Citrate Tubes','Lab Supplies'),
('Serum Separator Tubes','Lab Supplies'),
('Pipette Tips','Lab Supplies'),
('Microscope Slides','Lab Supplies'),
('Cover Slips','Lab Supplies'),
('Petri Dishes','Lab Supplies'),
('Reagent Bottles','Lab Supplies'),
('Blood Lancets','Lab Supplies'),
('Vacutainer Needles','Lab Supplies'),
('Sterile Swabs','Lab Supplies'),
('Urine Sample Containers','Lab Supplies'),
('Culture Media','Lab Supplies'),
('Laboratory Reagents','Lab Supplies'),
('Hand Sanitizer','Hygiene'),
('Liquid Hand Soap','Hygiene'),
('Disinfectant Solution','Hygiene'),
('Surface Disinfectant Wipes','Hygiene'),
('Bleach Solution','Hygiene'),
('Paper Towels','Hygiene'),
('Tissue Rolls','Hygiene'),
('General Waste Bags','Hygiene'),
('Biohazard Waste Bags','Hygiene'),
('Sharps Containers','Hygiene'),
('Floor Cleaner','Hygiene');

