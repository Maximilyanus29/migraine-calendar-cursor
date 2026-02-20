-- Migraine Calendar schema (SQLite, dev fallback)

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS attacks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  attack_date TEXT NOT NULL,
  start_time TEXT NULL,
  end_time TEXT NULL,
  pain_level INTEGER NULL,
  medications TEXT NULL,
  notes TEXT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE (user_id, attack_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

