-- Table: spms_message
DROP TABLE IF EXISTS spms_message;

CREATE TABLE spms_message
(
  id_message serial,
  id_queue integer NOT NULL,
  handled decimal,
  content text NOT NULL,
  checksum varchar(32) NOT NULL,
  timeout integer NOT NULL,
  created decimal,
  log text,
  CONSTRAINT pkey_message PRIMARY KEY (id_message)
);

-- Table: spms_queue
DROP TABLE IF EXISTS spms_queue;

CREATE TABLE spms_queue (
  id_queue serial,
  name varchar(255) UNIQUE NOT NULL,
  created decimal NOT NULL,
  CONSTRAINT pkey_queue PRIMARY KEY  (id_queue)
);

-- Table: spms_log
DROP TABLE IF EXISTS spms_log;

CREATE TABLE spms_log (
  id_log serial,
  created decimal NOT NULL,
  message text NOT NULL,
  CONSTRAINT pkey_log PRIMARY KEY (id_log)
);

