# MEDITrack – Hospital Patient, Appointment and Resource Management System

A 4-role hospital management project for **Patient, Doctor, Receptionist and Admin**. Built with plain PHP, procedural MySQLi, prepared statements, HTML, CSS, JavaScript, AJAX and JSON. The project follows an MVC-style structure and runs through XAMPP.

---

## 1. Install (XAMPP)

1. Copy the MEDITrack project folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `database.sql` → **Go**.
4. Open the project in the browser, for example: `http://localhost/MEDITrack/`.
5. Sign in as the default Admin:

   **Email:** `admin@meditrack.com`  
   **Password:** `Admin@123`

The Admin account is created from `database.sql`. Patient, Doctor and Receptionist accounts can be created from the Register page.

If your MySQL uses a password, update `DB_PASS` in `config/config.php`.

---

## 2. Folder structure

| Path | Purpose |
| --- | --- |
| `index.php` | Main entry point and router |
| `database.sql` | Database schema and default Admin account |
| `config/config.php` | Database connection, session settings and application constants |
| `helpers/helpers.php` | Shared helpers, CSRF, session handling, escaping and role guards |
| `models/` | Database queries and data operations, separated by responsibility |
| `controllers/` | Request handling, validation and application logic |
| `views/auth/` | Login and registration pages |
| `views/patient/` | Patient module pages |
| `views/doctor/` | Doctor module pages |
| `views/receptionist/` | Separate Receptionist views: dashboard, patients, booking, appointments, slots and emergency |
| `views/admin/` | Separate Admin views: dashboard, billing, staff, equipment, inventory, doctors and records |
| `views/shared/` | Shared layout parts used by multiple pages |
| `assets/css/style.css` | Main stylesheet |
| `assets/js/app.js` | Main JavaScript file for validation, AJAX and live search |
| `assets/images/` | Images used by the interface |

**MVC rule used in the project:** models handle database work, controllers handle validation and decisions, and views display the interface.

### Model organization

The larger Admin and Receptionist models are also separated by responsibility instead of keeping unrelated database work in one large file.

| Model | Responsibility |
| --- | --- |
| `admin_model.php` | Admin dashboard statistics and recent activity |
| `doctor_management_model.php` | Admin doctor search and status management |
| `staff_model.php` | Staff CRUD and search |
| `equipment_model.php` | Equipment types and equipment CRUD |
| `inventory_model.php` | Stock items, history and low-stock data |
| `billing_model.php` | Patient, doctor and staff billing |
| `admin_records_model.php` | Hospital-wide patient, appointment and follow-up records |
| `patient_management_model.php` | Receptionist patient management |
| `doctor_slot_model.php` | Doctor availability lookup and bookable slot management |
| `receptionist_appointment_model.php` | Receptionist appointment booking and cancellation |
| `emergency_model.php` | Emergency registration CRUD and search |

This keeps each model focused on one database responsibility and makes the MVC structure easier to read and maintain.

---

## 3. How the router works

The application is routed through `index.php`.

Receptionist and Admin sections now use separate page routes, so `section=` is not needed to switch between their pages.

| URL example | What happens |
| --- | --- |
| `index.php?page=login&role=patient` | Patient login page |
| `index.php?page=register` | Registration page |
| `index.php?page=patient` | Patient dashboard |
| `index.php?page=doctor` | Doctor dashboard |
| `index.php?page=doctor_slots` | Doctor availability page |
| `index.php?page=receptionist_booking` | Receptionist appointment booking |
| `index.php?page=admin_inventory` | Admin stock monitor |
| `index.php?page=ajax&action=search_symptoms` | Returns AJAX/JSON search results |
| `index.php?page=logout&role=doctor` | Signs out only the Doctor session |

Protected pages check the logged-in role before showing the requested module.

---

## 4. The four roles

| Role | Main areas | Main features |
| --- | --- | --- |
| **Patient** | Profile, Symptoms, Appointments, Prescriptions, Follow-ups | Manage personal records, search symptoms, book/cancel appointments, view prescriptions and follow-ups |
| **Doctor** | Profile, Patient Management, Availability, Prescriptions, Follow-ups | Add availability, view connected patients, complete appointments, create prescriptions and follow-ups |
| **Receptionist** | Patients, Book Appointment, Appointments, Doctor Slots, Emergency | Manage patient records, create doctor slots, book appointments, manage emergencies |
| **Admin** | Dashboard, Billing, Staff, Equipment, Stock Monitor, Doctors, All Records | Manage hospital administration, billing, staff, equipment, stock, doctor status and overall records |

### How to use the main workflow

- A **Doctor** adds an available date and time range.
- The **Receptionist** creates bookable slots inside that availability.
- A **Patient** books a slot, or the Receptionist books it on the patient's behalf.
- Once booked, the selected **Doctor** can see that Patient in Patient Management.
- The Doctor can create a **prescription**, add a **follow-up** and complete the appointment.
- The **Patient** can then view the prescription and follow-up from the Patient module.
- The **Admin** can monitor hospital-wide records and manage billing, staff, equipment and stock.

### Important module features

- **Patient:** symptom CRUD and search, appointment booking, cancellation, prescription history, follow-up history.
- **Doctor:** availability management, connected-patient search, prescription creation with multiple medicines, follow-up management, appointment completion.
- **Receptionist:** patient CRUD and search, doctor-slot creation, appointment booking and cancellation, emergency registration.
- **Admin:** billing CRUD and search, staff management, equipment management, stock monitoring with low-stock alerts, doctor status management, hospital-wide records.

---

## 5. Requirement checklist

| Requirement | Where to look |
| --- | --- |
| **MVC-style structure** | `models/`, `controllers/`, `views/`, routed by `index.php` |
| **MySQL + procedural MySQLi** | Database operations inside `models/` |
| **Prepared statements** | Used for user-supplied values in database queries |
| **Session + cookie authentication** | `controllers/auth_controller.php`, `helpers/helpers.php`, `config/config.php` |
| **PHP validation** | Controllers validate submitted data |
| **JavaScript validation** | `assets/js/app.js` |
| **AJAX / JSON** | `controllers/ajax_controller.php` + `assets/js/app.js` |
| **CRUD + Search** | Implemented across all four modules |
| **UI** | `views/` + `assets/css/style.css` |
| **One CSS file** | `assets/css/style.css` |
| **One JavaScript file** | `assets/js/app.js` |
| **Basic web security** | See section 6 |

---

## 6. Security and session handling

| Area | How MEDITrack handles it |
| --- | --- |
| SQL injection | Prepared statements are used for user input |
| Password storage | `password_hash()` and `password_verify()` |
| XSS | Output is escaped before being displayed |
| CSRF | State-changing forms/actions use CSRF tokens |
| Session fixation | Session ID is regenerated after login |
| Session cookie | Uses HttpOnly and SameSite=Lax |
| Wrong-role access | Protected pages check the required role |
| Session timeout | Automatic sign-out after 30 minutes of inactivity |
| Multi-role login | Patient, Doctor, Receptionist and Admin can stay logged in in separate tabs |
| Role-specific logout | Signing out from one role does not sign out the other active roles |

JavaScript validation improves the user experience, but important values are also validated again in PHP.

---

## 7. Settings you can change

Main settings are stored in `config/config.php`.

| Setting | Current value | Purpose |
| --- | --- | --- |
| `DB_HOST` | `localhost` | MySQL host |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | empty by default | MySQL password |
| `DB_NAME` | `meditrack` | Database name |
| `SESSION_TIMEOUT` | `1800` seconds | Signs a role out after 30 minutes of inactivity |
| Timezone | `Asia/Dhaka` | Application time zone |

---

## 8. Test accounts

| Role | Login | Password |
| --- | --- | --- |
| **Admin** | `admin@meditrack.com` | `Admin@123` |
| **Patient** | Sign up from the Register page | User-selected password |
| **Doctor** | Sign up from the Register page | User-selected password |
| **Receptionist** | Sign up from the Register page | User-selected password |

Admin registration is not available from the public Register page. The default Admin account is included in `database.sql`.

---

## 9. Quick demonstration order

1. Log in as Doctor and add availability.
2. Log in as Receptionist and create appointment slots.
3. Log in as Patient and book a slot.
4. Return to Doctor and show the connected Patient.
5. Create a prescription and follow-up.
6. Return to Patient and show the prescription and follow-up.
7. Use Receptionist to demonstrate patient, appointment and emergency management.
8. Use Admin to demonstrate billing, staff, equipment, stock alerts, doctor management and all records.

This shows how all four modules work together instead of demonstrating each dashboard separately.
