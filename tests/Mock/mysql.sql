
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `dev_orm_mock` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE dev_orm_mock;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE addresses (
  address_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned DEFAULT NULL,
  address_line_1 varchar(100) DEFAULT NULL,
  address_city varchar(50) DEFAULT NULL,
  address_date_added datetime DEFAULT NULL,
  address_date_updated datetime DEFAULT NULL,
  PRIMARY KEY (address_id),
  KEY user_id (user_id),
  CONSTRAINT addresses_user_id FOREIGN KEY (user_id) REFERENCES `users` (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE users (
  user_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_name varchar(50) DEFAULT NULL,
  user_email varchar(100) DEFAULT NULL,
  user_happy tinyint(1) unsigned NOT NULL DEFAULT '0',
  user_date_added datetime DEFAULT NULL,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
