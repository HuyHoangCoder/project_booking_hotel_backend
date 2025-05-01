-- Bảng Role (Vai trò)
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100) NOT NULL
);

-- Bảng Employees (Nhân viên)
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    citizen_identity VARCHAR(20) UNIQUE,
    fullname VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    dob DATE,
    email VARCHAR(100) UNIQUE,
    avatar VARCHAR(255),
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    role_id INT,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Bảng Accounts (Tài khoản)
CREATE TABLE accounts (
    username VARCHAR(50) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    employee_id INT UNIQUE,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Bảng Authorities (Quyền hạn)
CREATE TABLE authorities (
    username VARCHAR(50),
    permission VARCHAR(100),
    details TEXT,
    PRIMARY KEY (username, permission),
    FOREIGN KEY (username) REFERENCES accounts(username)
);

-- Bảng CustomerTypes (Loại khách hàng)
CREATE TABLE customer_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    details TEXT
);

-- Bảng Customers (Khách hàng)
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    citizen_identity VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    dob DATE,
    customer_type INT,
    FOREIGN KEY (customer_type) REFERENCES customer_types(id)
);

-- Bảng CustomerGroups (Nhóm khách hàng)
CREATE TABLE customer_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Bảng GroupMembers (Thành viên nhóm)
CREATE TABLE group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    relationship VARCHAR(100),
    group_id INT,
    FOREIGN KEY (group_id) REFERENCES customer_groups(id)
);

-- Bảng RoomTypes (Loại phòng)
CREATE TABLE room_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    details TEXT
);

-- Bảng RoomStatus (Trạng thái phòng)
CREATE TABLE room_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    details TEXT
);

-- Bảng Rooms (Phòng)
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    roomtype_id INT,
    room_status INT,
    FOREIGN KEY (roomtype_id) REFERENCES room_types(id),
    FOREIGN KEY (room_status) REFERENCES room_status(id)
);

-- Bảng ServiceTypes (Loại dịch vụ)
CREATE TABLE service_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    details TEXT
);

-- Bảng ServiceDetails (Chi tiết dịch vụ)
CREATE TABLE service_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_service VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    service_code VARCHAR(50) UNIQUE,
    remaining_inventory INT,
    unit VARCHAR(50),
    servicetype_id INT,
    FOREIGN KEY (servicetype_id) REFERENCES service_types(id)
);

-- Bảng Vouchers (Phiếu giảm giá)
CREATE TABLE vouchers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_code VARCHAR(50) UNIQUE,
    voucher_percent DECIMAL(5,2),
    date_start DATE,
    date_end DATE
);

-- Bảng Bookings (Đặt phòng)
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    room_id INT,
    date_booking DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_start DATETIME NOT NULL,
    date_end DATETIME NOT NULL,
    deposits DECIMAL(10,2),
    employee_id INT,
    vouchers_id INT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (vouchers_id) REFERENCES vouchers(id)
);

-- Bảng BookingHistories (Lịch sử đặt phòng)
CREATE TABLE booking_histories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    citizen_identity VARCHAR(20),
    booking_id INT,
    date_start DATETIME,
    date_end DATETIME,
    total_payment DECIMAL(10,2),
    special_request TEXT,
    feedback TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Bảng Invoices (Hóa đơn)
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    employee_id INT,
    payment_id INT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Bảng Payments (Thanh toán)
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_details TEXT
);

-- Bảng InvoiceDetails (Chi tiết hóa đơn)
CREATE TABLE invoice_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoices_id INT,
    servicedetails_id INT,
    quantity INT,
    use_date DATETIME,
    vouchers_id INT,
    FOREIGN KEY (invoices_id) REFERENCES invoices(id),
    FOREIGN KEY (servicedetails_id) REFERENCES service_details(id),
    FOREIGN KEY (vouchers_id) REFERENCES vouchers(id)
);

-- Bảng InventoryDelivery (Xuất kho)
CREATE TABLE inventory_delivery (
    UniqueID INT PRIMARY KEY AUTO_INCREMENT,
    id INT,
    servicedetails_id INT,
    quantity INT,
    out_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicedetails_id) REFERENCES service_details(id)
);

-- Bảng InventoryReceiving (Nhập kho)
CREATE TABLE inventory_receiving (
    id INT PRIMARY KEY AUTO_INCREMENT,
    servicedetails_id INT,
    quantity INT,
    price DECIMAL(10,2),
    in_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicedetails_id) REFERENCES service_details(id)
);

-- Bảng LostItems (Đồ thất lạc)
CREATE TABLE lost_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_number VARCHAR(50) UNIQUE,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    location_found VARCHAR(255),
    date_found DATE NOT NULL,
    status ENUM('pending', 'claimed', 'disposed') DEFAULT 'pending',
    storage_location VARCHAR(255),
    finder_name VARCHAR(255),
    finder_contact VARCHAR(255),
    claimer_name VARCHAR(255),
    claimer_contact VARCHAR(255),
    claim_date DATE,
    claim_notes TEXT,
    images JSON,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);







------------------------------------------------------------------------------------------------









-- Bảng Roles (Vai trò)
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng Employees (Nhân viên)
CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    citizen_identity VARCHAR(20) UNIQUE,
    fullname VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female', 'other') NULL,
    dob DATE NULL,
    email VARCHAR(255) UNIQUE,
    avatar VARCHAR(255) NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    role_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Bảng Accounts (Tài khoản)
CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    employee_id BIGINT UNSIGNED UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Bảng Authorities (Quyền hạn)
CREATE TABLE authorities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    permission VARCHAR(255) NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (username) REFERENCES accounts(username) ON DELETE CASCADE
);

-- Bảng CustomerTypes (Loại khách hàng)
CREATE TABLE customer_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    benefits JSON NULL,
    minimum_points INT DEFAULT 0,
    discount_rate DECIMAL(5,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng Customers (Khách hàng)
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    citizen_identity VARCHAR(20) UNIQUE,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    dob DATE NULL,
    address TEXT NULL,
    points INT DEFAULT 0,
    customer_type_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_type_id) REFERENCES customer_types(id) ON DELETE SET NULL
);

-- Bảng CustomerGroups (Nhóm khách hàng)
CREATE TABLE customer_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng GroupMembers (Thành viên nhóm)
CREATE TABLE group_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES customer_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Bảng Floors (Tầng lầu)
CREATE TABLE floors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    floor_number INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng RoomTypes (Loại phòng)
CREATE TABLE room_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    capacity INT NOT NULL,
    base_price DECIMAL(12,2) NOT NULL,
    amenities JSON NULL,
    images JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng RoomStatus (Trạng thái phòng)
CREATE TABLE room_status (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT NULL,
    color VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_available BOOLEAN DEFAULT TRUE,
    is_occupied BOOLEAN DEFAULT FALSE,
    is_maintenance BOOLEAN DEFAULT FALSE,
    is_cleaning BOOLEAN DEFAULT FALSE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng Rooms (Phòng)
CREATE TABLE rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL,
    floor_id BIGINT UNSIGNED NOT NULL,
    room_type_id BIGINT UNSIGNED NOT NULL,
    status_id BIGINT UNSIGNED NOT NULL,
    current_price DECIMAL(12,2) NOT NULL,
    description TEXT NULL,
    amenities JSON NULL,
    images JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (floor_id) REFERENCES floors(id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(id),
    FOREIGN KEY (status_id) REFERENCES room_status(id)
);

-- Bảng ServiceTypes (Loại dịch vụ)
CREATE TABLE service_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    icon VARCHAR(255) NULL,
    color VARCHAR(20) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng ServiceDetails (Chi tiết dịch vụ)
CREATE TABLE service_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_service VARCHAR(255) NOT NULL,
    description TEXT NULL,
    service_type_id BIGINT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'item',
    duration DECIMAL(5,2) NULL,
    is_available BOOLEAN DEFAULT TRUE,
    requires_booking BOOLEAN DEFAULT FALSE,
    min_booking_hours INT NULL,
    max_booking_hours INT NULL,
    operating_hours JSON NULL,
    requirements JSON NULL,
    included_items JSON NULL,
    additional_charges JSON NULL,
    images JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id)
);

-- Bảng Vouchers (Phiếu giảm giá)
CREATE TABLE vouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('percentage', 'fixed_amount') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(12,2) NULL,
    max_discount_amount DECIMAL(12,2) NULL,
    usage_limit INT NULL,
    usage_count INT DEFAULT 0,
    per_user_limit INT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    applicable_room_types JSON NULL,
    applicable_customer_types JSON NULL,
    applicable_customer_groups JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng Bookings (Đặt phòng)
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_number VARCHAR(255) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    deposit_amount DECIMAL(12,2) DEFAULT 0.00,
    special_requests TEXT NULL,
    notes TEXT NULL,
    employee_id BIGINT UNSIGNED NULL,
    voucher_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id)
);

-- Bảng BookingHistories (Lịch sử đặt phòng)
CREATE TABLE booking_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    employee_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Bảng Invoices (Hóa đơn)
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(255) UNIQUE NOT NULL,
    booking_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    remaining_amount DECIMAL(12,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'draft',
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    payment_date DATE NULL,
    notes TEXT NULL,
    payment_details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Bảng InvoiceItems (Chi tiết hóa đơn)
CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    service_detail_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    type VARCHAR(50) DEFAULT 'item',
    details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_detail_id) REFERENCES service_details(id)
);

-- Bảng Payments (Thanh toán)
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(255) UNIQUE NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(255) NULL,
    payment_date DATETIME NOT NULL,
    notes TEXT NULL,
    payment_details JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- Bảng InventoryReceiving (Nhập kho)
CREATE TABLE inventory_receiving (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receiving_number VARCHAR(255) UNIQUE NOT NULL,
    receiving_date DATETIME NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_contact VARCHAR(255) NULL,
    supplier_address TEXT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT NULL,
    attachments JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng InventoryReceivingItems (Chi tiết nhập kho)
CREATE TABLE inventory_receiving_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receiving_id BIGINT UNSIGNED NOT NULL,
    service_detail_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_code VARCHAR(100) NULL,
    unit VARCHAR(50) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    batch_number VARCHAR(100) NULL,
    expiry_date DATE NULL,
    storage_location VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (receiving_id) REFERENCES inventory_receiving(id) ON DELETE CASCADE,
    FOREIGN KEY (service_detail_id) REFERENCES service_details(id)
);

-- Bảng InventoryDelivery (Xuất kho)
CREATE TABLE inventory_delivery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_number VARCHAR(255) UNIQUE NOT NULL,
    delivery_date DATETIME NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    recipient_contact VARCHAR(255) NULL,
    recipient_address TEXT NULL,
    delivery_method VARCHAR(100) NULL,
    tracking_number VARCHAR(100) NULL,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT NULL,
    attachments JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bảng InventoryDeliveryItems (Chi tiết xuất kho)
CREATE TABLE inventory_delivery_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    delivery_id BIGINT UNSIGNED NOT NULL,
    service_detail_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_code VARCHAR(100) NULL,
    unit VARCHAR(50) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    batch_number VARCHAR(100) NULL,
    expiry_date DATE NULL,
    storage_location VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (delivery_id) REFERENCES inventory_delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (service_detail_id) REFERENCES service_details(id)
);

-- Bảng LostItems (Đồ thất lạc)
CREATE TABLE lost_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_number VARCHAR(255) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    location_found VARCHAR(255) NULL,
    date_found DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    storage_location VARCHAR(255) NULL,
    finder_name VARCHAR(255) NULL,
    finder_contact VARCHAR(255) NULL,
    claimer_name VARCHAR(255) NULL,
    claimer_contact VARCHAR(255) NULL,
    claim_date DATE NULL,
    claim_notes TEXT NULL,
    images JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);