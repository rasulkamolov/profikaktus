CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'cutter')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    image_path TEXT,
    width REAL NOT NULL, -- Width in cm or meters (display only mostly)
    unit TEXT DEFAULT 'meter',
    purchase_price REAL NOT NULL, -- Per meter
    selling_price REAL NOT NULL, -- Per meter
    min_stock_warning REAL DEFAULT 50,
    min_stock_critical REAL DEFAULT 30,
    max_stock_level REAL DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rolls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL,
    original_length REAL NOT NULL,
    current_length REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cuts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    roll_id INTEGER NOT NULL,
    material_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    customer_name TEXT,
    length_cut REAL NOT NULL,
    price_sold REAL NOT NULL,
    profit REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (roll_id) REFERENCES rolls(id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
