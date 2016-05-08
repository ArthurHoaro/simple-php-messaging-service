BEGIN TRANSACTION;
CREATE TABLE spms_queue
(
  id_queue INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(255) UNIQUE NOT NULL,
  created REAL
);
CREATE TABLE spms_message
(
  id_message INTEGER PRIMARY KEY AUTOINCREMENT,
  id_queue INTEGER NOT NULL,
  handled REAL,
  content TEXT NOT NULL,
  checksum CHAR(32) NOT NULL,
  timeout INTEGER,
  created REAL,
  log TEXT
);
CREATE TABLE spms_log
(
  id_log INTEGER PRIMARY KEY AUTOINCREMENT,
  created REAL,
  message TEXT
);
COMMIT;
