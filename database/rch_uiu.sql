DROP DATABASE IF EXISTS `rch_uiu`;
CREATE DATABASE `rch_uiu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rch_uiu`;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `users` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `first_name`        VARCHAR(60)  NOT NULL,
  `last_name`         VARCHAR(60)  NOT NULL,
  `email`             VARCHAR(150) NOT NULL UNIQUE,
  `password`          VARCHAR(255) NOT NULL,
  `role`              ENUM('student','faculty','admin') NOT NULL DEFAULT 'student',
  `researcher_type`   ENUM('student_academic','corporate','gov_ngo','medical','non_researcher') DEFAULT 'student_academic',
  `department`        VARCHAR(100) DEFAULT NULL,
  `university`        VARCHAR(150) DEFAULT 'United International University',
  `designation`       VARCHAR(120) DEFAULT NULL,
  `trimester`         VARCHAR(30)  DEFAULT NULL,
  `student_id`        VARCHAR(30)  DEFAULT NULL,
  `cgpa`              DECIMAL(3,2) DEFAULT NULL,
  `bio`               TEXT         DEFAULT NULL,
  `avatar`            VARCHAR(255) DEFAULT NULL,
  `cover`             VARCHAR(255) DEFAULT NULL,
  `phone`             VARCHAR(40)  DEFAULT NULL,
  `city`              VARCHAR(80)  DEFAULT 'Dhaka',
  `linkedin`          VARCHAR(200) DEFAULT NULL,
  `orcid`             VARCHAR(60)  DEFAULT NULL,
  `website`           VARCHAR(200) DEFAULT NULL,
  `availability`      VARCHAR(60)  DEFAULT NULL,
  `preferred_team`    VARCHAR(40)  DEFAULT NULL,
  `open_to_collab`    TINYINT(1)   NOT NULL DEFAULT 1,
  `status`            ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `interests` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `skills` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE `user_interests` (
  `user_id`     INT NOT NULL,
  `interest_id` INT NOT NULL,
  PRIMARY KEY (`user_id`,`interest_id`),
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`interest_id`) REFERENCES `interests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `user_skills` (
  `user_id`  INT NOT NULL,
  `skill_id` INT NOT NULL,
  `level`    ENUM('Beginner','Intermediate','Advanced','Expert') NOT NULL DEFAULT 'Intermediate',
  PRIMARY KEY (`user_id`,`skill_id`),
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `projects` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id`         INT NOT NULL,
  `title`            VARCHAR(200) NOT NULL,
  `summary`          VARCHAR(400) DEFAULT NULL,
  `description`      TEXT         DEFAULT NULL,
  `objectives`       TEXT         DEFAULT NULL,
  `project_type`     ENUM('FYDP','Thesis','RA','Research','Lab Work') NOT NULL DEFAULT 'Research',
  `status`           ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'in_progress',
  `is_open`          TINYINT(1)   NOT NULL DEFAULT 1,
  `required_members` VARCHAR(30)  DEFAULT '2-3',
  `deadline`         DATE         DEFAULT NULL,
  `progress`         INT          NOT NULL DEFAULT 0,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `project_interests` (
  `project_id`  INT NOT NULL,
  `interest_id` INT NOT NULL,
  PRIMARY KEY (`project_id`,`interest_id`),
  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`interest_id`) REFERENCES `interests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `project_skills` (
  `project_id` INT NOT NULL,
  `skill_id`   INT NOT NULL,
  PRIMARY KEY (`project_id`,`skill_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`)   REFERENCES `skills`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `project_members` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT NOT NULL,
  `user_id`     INT NOT NULL,
  `member_role` VARCHAR(60) NOT NULL DEFAULT 'Member',
  `joined_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_member` (`project_id`,`user_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `collab_requests` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `user_id`    INT NOT NULL,
  `message`    TEXT DEFAULT NULL,
  `status`     ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_request` (`project_id`,`user_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `milestones` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status`      ENUM('completed','in_progress','upcoming') NOT NULL DEFAULT 'upcoming',
  `progress`    INT NOT NULL DEFAULT 0,
  `due_date`    DATE DEFAULT NULL,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `tasks` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `assigned_to` INT DEFAULT NULL,
  `is_done`     TINYINT(1) NOT NULL DEFAULT 0,
  `due_date`    DATE DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `project_files` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `user_id`    INT NOT NULL,
  `file_name`  VARCHAR(200) NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL,
  `file_size`  INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `project_messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `user_id`    INT NOT NULL,
  `body`       TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `title`      VARCHAR(250) NOT NULL,
  `abstract`   TEXT DEFAULT NULL,
  `authors`    VARCHAR(400) DEFAULT NULL,
  `journal`    VARCHAR(200) DEFAULT NULL,
  `year`       INT DEFAULT NULL,
  `pub_type`   ENUM('Paper','Pre-print','Dataset','Thesis') NOT NULL DEFAULT 'Paper',
  `category`   VARCHAR(80) DEFAULT NULL,
  `file_name`  VARCHAR(200) DEFAULT NULL,
  `file_path`  VARCHAR(255) DEFAULT NULL,
  `file_size`  INT NOT NULL DEFAULT 0,
  `views`      INT NOT NULL DEFAULT 0,
  `downloads`  INT NOT NULL DEFAULT 0,
  `citations`  INT NOT NULL DEFAULT 0,
  `status`     ENUM('published','draft') NOT NULL DEFAULT 'published',
  `current_version` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publication_tags` (
  `publication_id` INT NOT NULL,
  `interest_id`    INT NOT NULL,
  PRIMARY KEY (`publication_id`,`interest_id`),
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interest_id`)    REFERENCES `interests`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publication_versions` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL,
  `version_no`     INT NOT NULL,
  `title`          VARCHAR(250) NOT NULL,
  `abstract`       TEXT DEFAULT NULL,
  `file_name`      VARCHAR(200) DEFAULT NULL,
  `file_path`      VARCHAR(255) DEFAULT NULL,
  `file_size`      INT NOT NULL DEFAULT 0,
  `change_note`    VARCHAR(400) DEFAULT NULL,
  `created_by`     INT NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_publication_version` (`publication_id`,`version_no`),
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)     REFERENCES `users`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publication_likes` (
  `publication_id` INT NOT NULL,
  `user_id`        INT NOT NULL,
  PRIMARY KEY (`publication_id`,`user_id`),
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)        REFERENCES `users`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publication_saves` (
  `publication_id` INT NOT NULL,
  `user_id`        INT NOT NULL,
  PRIMARY KEY (`publication_id`,`user_id`),
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)        REFERENCES `users`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `publication_comments` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `publication_id` INT NOT NULL,
  `user_id`        INT NOT NULL,
  `parent_id`      INT DEFAULT NULL,
  `body`           TEXT NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)        REFERENCES `users`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `communities` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `field`       VARCHAR(80) DEFAULT 'General',
  `icon`        VARCHAR(10) DEFAULT 'CM',
  `created_by`  INT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `community_members` (
  `community_id` INT NOT NULL,
  `user_id`      INT NOT NULL,
  `joined_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`community_id`,`user_id`),
  FOREIGN KEY (`community_id`) REFERENCES `communities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `community_posts` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `community_id` INT NOT NULL,
  `user_id`      INT NOT NULL,
  `title`        VARCHAR(250) NOT NULL,
  `body`         TEXT NOT NULL,
  `views`        INT NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`community_id`) REFERENCES `communities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `community_replies` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT NOT NULL,
  `user_id`    INT NOT NULL,
  `body`       TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)           ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `messages` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id`   INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `body`        TEXT NOT NULL,
  `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `connections` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `requester_id` INT NOT NULL,
  `addressee_id` INT NOT NULL,
  `status`       ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_conn` (`requester_id`,`addressee_id`),
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`addressee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `events` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `event_type`  VARCHAR(60) DEFAULT 'Seminar',
  `venue`       VARCHAR(200) DEFAULT NULL,
  `event_date`  DATETIME NOT NULL,
  `capacity`    INT NOT NULL DEFAULT 100,
  `created_by`  INT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `event_rsvps` (
  `event_id`  INT NOT NULL,
  `user_id`   INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`user_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `text`       VARCHAR(255) NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `interests` (`name`) VALUES
('Artificial Intelligence'),('Machine Learning'),('Computer Vision'),('Data Science'),
('NLP'),('IoT'),('Cyber Security'),('Blockchain'),('Robotics'),('Cloud Computing'),
('Human Computer Interaction'),('Renewable Energy'),('Bioinformatics'),('Networking'),
('Software Engineering'),('Quantum Computing'),('Deep Learning'),('Sustainability');

INSERT INTO `skills` (`name`) VALUES
('Python'),('Java'),('C++'),('PHP'),('JavaScript'),('SQL'),('TensorFlow'),('Keras'),
('PyTorch'),('OpenCV'),('R'),('MATLAB'),('Flutter'),('React'),('Data Analysis'),
('Arduino'),('Docker'),('Git'),('Figma'),('LaTeX');

INSERT INTO `users`
(`first_name`,`last_name`,`email`,`password`,`role`,`department`,`designation`,`trimester`,`student_id`,`cgpa`,`bio`,`phone`,`linkedin`,`orcid`,`availability`,`preferred_team`,`open_to_collab`) VALUES
('System','Admin','admin@rchuiu.edu','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','admin','Administration','Platform Administrator',NULL,NULL,NULL,'Maintains the Research Collaboration Hub UIU platform, verifies institutional accounts and moderates published work.','+880 1700000000',NULL,NULL,NULL,NULL,0),

('Md. Mehrab Hosain','Khan','mehrab@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','CSE Department',NULL,'12th Trimester','011201377',3.50,'Passionate about solving real-world problems using AI and Machine Learning. I love working on innovative projects and collaborating with like-minded people to create research that makes an impact.','+880 1922444919','linkedin.com/in/md.mehrabhossainkhan','0000-0002-1825','15 - 20 hrs/week','2-3 Members',1),

('Dr. M. Rezwan','Khan','rezwan.khan@cse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','faculty','CSE Department','Professor & Senior Research Fellow',NULL,NULL,NULL,'Dr. M. Rezwan Khan is a researcher specialising in the intersection of neural networks and computational biology. With over 15 years of experience in academic collaboration, he has pioneered several open-source frameworks for genomic sequencing. He currently leads the Luminous Academic initiative at UIU, fostering interdisciplinary research cultures.','+880 1711223344','linkedin.com/in/rezwankhan','0000-0001-7742','10 hrs/week','3-5 Members',1),

('Tanjila Tafrim','Priyonta','tanjila@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','CSE Department',NULL,'12th Trimester','011201402',3.72,'Final year CSE student working on medical imaging and multimodal AI security. Looking for a strong FYDP team.','+880 1712345678','linkedin.com/in/tanjilatafrim',NULL,'20 hrs/week','2-4 Members',1),

('Atika Fairoz','Prity','atika@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','CSE Department',NULL,'12th Trimester','011201288',3.64,'Data science enthusiast. I enjoy building end-to-end ML pipelines and clean data visualisations.','+880 1798765432',NULL,NULL,'12 hrs/week','2-3 Members',1),

('Nahiun','Sajid','nahiun@bsee.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','EEE Department',NULL,'10th Trimester','012103311',3.41,'EEE student interested in embedded systems, IoT and renewable energy monitoring.','+880 1811223344',NULL,NULL,'10 hrs/week','2-3 Members',1),

('Farhan','Karim','farhan@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','CSE Department',NULL,'11th Trimester','011201955',3.29,'Backend developer turned researcher. Currently exploring blockchain based voting and secure smart contracts.','+880 1633445566',NULL,NULL,'15 hrs/week','3-4 Members',1),

('Dr. Ariful','Islam','ariful.islam@phy.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','faculty','Physics Department','Associate Professor',NULL,NULL,NULL,'Experimental physicist working on particle detection data pipelines and open science advocacy.','+880 1744556677',NULL,'0000-0003-9911','8 hrs/week','2-5 Members',1),

('Sara','Ahmed','sara.ahmed@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','CSE Department',NULL,'9th Trimester','011202144',3.58,'Interested in HCI, usability testing and accessible interface design.','+880 1655667788',NULL,NULL,'10 hrs/week','2-3 Members',1),

('Tanvir','Rahman','tanvir@bsds.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','Data Science',NULL,'8th Trimester','013200877',3.66,'Building open datasets for low-resource Bengali NLP.','+880 1566778899',NULL,NULL,'18 hrs/week','2-3 Members',1),

('Mehnaz','Chowdhury','mehnaz@bba.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','student','Economics',NULL,'7th Trimester','021190455',3.81,'Researching the economic impact of green operational practices among Bangladeshi SMEs.','+880 1477889900',NULL,NULL,'8 hrs/week','2-3 Members',1),

('Prof. Kabir','Hossain','kabir@bscse.uiu.ac.bd','$2y$12$KQDEWEpy4SWj0vYev/8tGeRJtvGW6ckK33B/vG2eXc3vRvIyonKAO','faculty','CSE Department','Professor',NULL,NULL,NULL,'Natural language processing researcher focused on Bengali language resources and low-resource machine translation.','+880 1388990011',NULL,'0000-0004-2233','6 hrs/week','3-6 Members',1);

INSERT INTO `user_interests` (`user_id`,`interest_id`) VALUES
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),
(3,13),(3,2),(3,17),(3,1),
(4,3),(4,2),(4,1),(4,7),
(5,4),(5,2),(5,15),
(6,6),(6,12),(6,9),
(7,8),(7,7),(7,15),
(8,16),(8,4),(8,18),
(9,11),(9,15),(9,1),
(10,5),(10,2),(10,4),
(11,18),(11,12),(11,4),
(12,5),(12,1),(12,2);

INSERT INTO `user_skills` (`user_id`,`skill_id`,`level`) VALUES
(2,1,'Expert'),(2,8,'Advanced'),(2,6,'Expert'),(2,15,'Intermediate'),(2,7,'Advanced'),
(3,1,'Expert'),(3,11,'Expert'),(3,3,'Advanced'),(3,9,'Advanced'),
(4,1,'Advanced'),(4,10,'Advanced'),(4,7,'Intermediate'),
(5,1,'Advanced'),(5,15,'Expert'),(5,6,'Advanced'),
(6,16,'Expert'),(6,3,'Advanced'),(6,1,'Intermediate'),
(7,4,'Expert'),(7,5,'Advanced'),(7,6,'Advanced'),
(8,12,'Expert'),(8,1,'Advanced'),(8,20,'Expert'),
(9,19,'Advanced'),(9,5,'Intermediate'),(9,14,'Intermediate'),
(10,1,'Expert'),(10,9,'Advanced'),(10,15,'Advanced'),
(11,11,'Advanced'),(11,15,'Advanced'),
(12,1,'Expert'),(12,9,'Expert'),(12,20,'Advanced');

INSERT INTO `projects`
(`owner_id`,`title`,`summary`,`description`,`objectives`,`project_type`,`status`,`is_open`,`required_members`,`deadline`,`progress`) VALUES
(3,'AI-Based Plant Disease Detection',
 'Develop an intelligent system for early detection of plant diseases through high-fidelity imaging and deep learning algorithms.',
 'This project aims to build a deep learning based plant disease detection system that can identify and classify plant leaf diseases from images with high accuracy. The solution can help farmers detect diseases early and take necessary actions to improve crop production and reduce loss. We are exploring Transformer-based architectures combined with CNN backbones for robust edge-device deployment.',
 'Build a DL-based model for plant disease classification using a multi-spectral dataset.\nOptimise model weights for real-time mobile inference within restricted RAM environments.\nImplement a data pipeline for automatic leaf segmentation and noise reduction.\nPublish a benchmark comparison against three baseline CNN architectures.',
 'FYDP','in_progress',1,'2-3','2026-11-20',65),

(7,'Blockchain for Healthcare Data Security',
 'Research assistant position open for smart contract optimisation on a permissioned medical records ledger.',
 'Healthcare records in Bangladesh are fragmented across private clinics with no interoperable audit trail. This project designs a permissioned blockchain layer with role-based smart contracts so that patients own their record access keys while hospitals retain verifiable audit logs.',
 'Design a permissioned ledger schema for fragmented clinical records.\nOptimise smart contract gas cost for low-bandwidth deployments.\nBuild a patient-facing key management prototype.',
 'Research','in_progress',1,'1-2','2026-11-25',30),

(6,'Smart Traffic Management System',
 'Optimising urban traffic flow using real-time IoT sensors and adaptive signal control.',
 'A city-scale IoT deployment that measures junction density with low-cost ultrasonic and camera nodes, then adapts signal timing using a reinforcement learning controller. Field tested at two Dhaka junctions.',
 'Deploy 12 low-cost sensor nodes across two junctions.\nTrain an RL controller on collected density data.\nMeasure average waiting-time reduction against fixed-cycle baselines.',
 'FYDP','completed',0,'3-4','2026-06-30',100),

(7,'Blockchain for Secure Voting',
 'Investigating decentralised ledger systems to ensure transparency in university elections.',
 'A transparent, verifiable voting prototype for university club elections, using a permissioned chain with zero-knowledge ballot proofs so that votes stay secret while tallies stay publicly auditable.',
 'Design a verifiable but secret ballot scheme.\nBuild a voter registration and eligibility module.\nRun a mock election with 200 simulated voters.',
 'Thesis','pending',1,'2-3','2027-01-15',15),

(12,'Low-Resource Bengali Sentiment Analysis',
 'Building an open sentiment corpus and transformer baseline for Bengali social media text.',
 'Bengali is spoken by over 250 million people but remains under-resourced in NLP. This project collects, annotates and releases a 50k-sentence sentiment corpus from Bengali social media, then benchmarks multilingual transformer models against it.',
 'Collect and clean 50,000 Bengali social media sentences.\nDevelop an annotation guideline and inter-annotator agreement protocol.\nBenchmark mBERT, XLM-R and BanglaBERT on the released corpus.',
 'Research','in_progress',1,'2-4','2026-12-10',45),

(3,'Cross-domain Adaptation for Medical Imaging',
 'Adapting diagnostic imaging models across scanner types without re-labelling data.',
 'Medical imaging models degrade badly when deployed on a scanner they were not trained on. This project studies unsupervised domain adaptation so a model trained on one hospital''s MRI scanner transfers to another without new annotations.',
 'Reproduce three domain-adaptation baselines on public MRI datasets.\nDesign a lightweight adaptation head for limited GPU memory.\nValidate on a locally collected scan set.',
 'Research','in_progress',1,'2-3','2026-12-20',40),

(11,'Green Operational Practices in Bangladeshi SMEs',
 'Measuring how sustainable operations affect productivity among small and medium enterprises.',
 'Using World Bank Enterprise Survey data for Bangladesh, this study examines whether adopting green operational practices measurably improves firm productivity and export readiness among SMEs.',
 'Clean and merge the WBES Bangladesh enterprise panel.\nEstimate the productivity effect of green practice adoption.\nProduce policy recommendations for SME regulators.',
 'Research','in_progress',1,'1-2','2026-10-30',55),

(9,'Accessible Interface Design for Bengali Screen Readers',
 'Improving screen-reader usability for Bengali-language web interfaces.',
 'Bengali web content is poorly served by existing screen readers. This HCI project runs usability studies with visually impaired participants and proposes concrete markup and typography guidelines for accessible Bengali interfaces.',
 'Run usability sessions with 15 participants.\nIdentify the top failure patterns in Bengali screen-reader output.\nPublish an accessible-markup guideline for Bengali web content.',
 'FYDP','in_progress',1,'2-3','2026-12-05',25);

INSERT INTO `project_interests` (`project_id`,`interest_id`) VALUES
(1,1),(1,17),(1,3),(1,4),
(2,8),(2,7),
(3,6),(3,2),(3,9),
(4,8),(4,7),
(5,5),(5,2),
(6,3),(6,2),(6,13),
(7,18),(7,4),
(8,11),(8,15);

INSERT INTO `project_skills` (`project_id`,`skill_id`) VALUES
(1,1),(1,6),(1,8),(1,7),(1,10),
(2,5),(2,6),
(3,16),(3,1),
(4,5),(4,4),
(5,1),(5,9),
(6,1),(6,9),(6,12),
(7,11),(7,15),
(8,19),(8,5);

INSERT INTO `project_members` (`project_id`,`user_id`,`member_role`) VALUES
(1,3,'Project Owner'),(1,2,'Senior Member'),(1,5,'Member'),(1,4,'Guest Contributor'),
(2,7,'Project Owner'),(2,2,'Member'),
(3,6,'Project Owner'),(3,2,'Collaborator'),(3,10,'Member'),
(4,7,'Initiator'),
(5,12,'Project Owner'),(5,10,'Member'),
(6,3,'Project Owner'),(6,4,'Member'),
(7,11,'Project Owner'),
(8,9,'Project Owner'),(8,5,'Member');

INSERT INTO `collab_requests` (`project_id`,`user_id`,`message`,`status`) VALUES
(1,10,'I have worked with CNN architectures for leaf segmentation in my previous course project. I would love to contribute to the data pipeline.','pending'),
(5,4,'I can help with annotation guidelines and inter-annotator agreement, I did something similar last trimester.','pending'),
(2,5,'Interested in the smart contract optimisation side. I have Solidity experience.','pending'),
(6,2,'I would like to join the adaptation head design task.','accepted');

INSERT INTO `milestones` (`project_id`,`title`,`description`,`status`,`progress`,`due_date`) VALUES
(1,'Initial Dataset Collection','Collected and validated 12,000 labelled leaf images across 8 disease classes.','completed',100,'2026-03-15'),
(1,'Model Training & Benchmarking','Training the CNN-Transformer hybrid and comparing against three baselines.','in_progress',65,'2026-08-25'),
(1,'Mobile Deployment & Field Test','Quantise the trained model and test inference on low-end Android devices.','upcoming',0,'2026-10-30'),
(5,'Corpus Collection','Scrape and de-duplicate 50k Bengali sentences.','completed',100,'2026-05-20'),
(5,'Annotation Round 1','First annotation pass with three annotators.','in_progress',60,'2026-09-30'),
(5,'Model Benchmarking','Fine-tune and evaluate three transformer baselines.','upcoming',0,'2026-11-30'),
(3,'Sensor Deployment','Install ultrasonic and camera nodes at two junctions.','completed',100,'2026-02-10'),
(3,'RL Controller Training','Train and validate the adaptive signal controller.','completed',100,'2026-05-01');

INSERT INTO `tasks` (`project_id`,`title`,`assigned_to`,`is_done`,`due_date`) VALUES
(1,'Optimise the leaf segmentation pre-processing script',2,0,'2026-09-20'),
(1,'Prepare manuscript draft (Introduction)',3,0,'2026-09-28'),
(1,'Review confusion-matrix comparison plots',5,0,'2026-09-15'),
(1,'Clean and re-label the noisy image subset',4,1,'2026-08-30'),
(5,'Write the annotation guideline v2',10,0,'2026-09-25'),
(5,'Set up the inter-annotator agreement script',12,1,'2026-08-18');

INSERT INTO `project_messages` (`project_id`,`user_id`,`body`) VALUES
(1,5,'I have just uploaded the new confusion-matrix plots. There is a curious deviation in the rust-disease class around batch 4500. Has anyone else seen this in their sub-runs?'),
(1,2,'Interesting, Prity. I will take a look this afternoon. It might be a sampling artifact or a genuine label-noise issue. Tanjila, can you check whether the augmentation settings were consistent during that window?'),
(1,4,'On it. I am running a quick re-validation on that specific batch now. Will post results shortly.'),
(5,12,'Annotation round 1 is at 60%. Please push your assigned batches before Thursday so we can compute agreement.'),
(5,10,'My batch is done and pushed. Agreement script is ready to run.');

INSERT INTO `publications`
(`user_id`,`title`,`abstract`,`authors`,`journal`,`year`,`pub_type`,`category`,`views`,`downloads`,`citations`) VALUES
(3,'Neural Correlates of Cognitive Flexibility in Collaborative Problem Solving',
 'This study explores the neural mechanisms underlying cognitive flexibility during real-time collaborative tasks. By employing hyperscanning EEG techniques, we monitored twenty dyads engaged in a complex decision-making paradigm. Our findings reveal a significant correlation between inter-brain synchrony in the prefrontal cortex and successful adaptation to shifting task demands. This research provides a novel framework for understanding how collective intelligence emerges from synchronised neural activity, offering implications for both organisational psychology and artificial intelligence systems designed to augment human teamwork.',
 'Dr. M. Rezwan Khan, Md. Mehrab Hosain Khan','International Journal of Research',2026,'Paper','Machine Learning',1247,456,89),

(12,'Quantum Cryptography in Low-Bandwidth Academic Networks',
 'We evaluate the feasibility of quantum key distribution protocols over the constrained fibre links typical of South Asian university campuses, and propose a hybrid classical-quantum handshake that tolerates high packet loss.',
 'Prof. Kabir Hossain, Tanvir Rahman','International Journal of Research',2026,'Paper','Computer Vision',890,220,34),

(11,'Sustainable Urban Development and Firm-Level Productivity',
 'Using enterprise survey panel data, this paper estimates the productivity return to green operational practice adoption among Bangladeshi SMEs and finds a statistically significant positive association concentrated among exporting firms.',
 'Mehnaz Chowdhury','International Journal of Research',2026,'Paper','Data Science',2510,610,45),

(12,'Neural Architectures for Natural Bengali Language Understanding',
 'A systematic comparison of multilingual transformer architectures on six Bengali downstream tasks, together with an open release of the evaluation harness.',
 'Prof. Kabir Hossain, Tanvir Rahman, Atika Fairoz Prity','International Journal of Research',2026,'Paper','Machine Learning',1680,510,58),

(8,'Advanced Biocatalysis in Low-Temperature Reaction Systems',
 'We report a low-temperature enzymatic pathway with a 40% yield improvement over conventional catalysis, characterised through time-resolved spectroscopy.',
 'Dr. Ariful Islam','International Journal of Research',2026,'Pre-print','IoT',640,180,12),

(11,'Economic Impacts of Renewable Energy Adoption in Rural Bangladesh',
 'A district-level analysis of off-grid solar adoption and its measurable effect on household enterprise income between 2018 and 2024.',
 'Mehnaz Chowdhury, Nahiun Sajid','International Journal of Research',2026,'Paper','Data Science',1120,330,27),

(2,'Benchmarking CNN Backbones for Edge Plant-Disease Inference',
 'A reproducible benchmark of six convolutional backbones quantised for mobile inference on a multi-spectral plant disease dataset, with latency and accuracy trade-off curves for low-end Android hardware.',
 'Md. Mehrab Hosain Khan, Dr. M. Rezwan Khan','UIU Research Repository',2026,'Pre-print','Machine Learning',430,96,7),

(9,'Screen Reader Failure Patterns in Bengali Web Interfaces',
 'A qualitative study of fifteen visually impaired participants navigating Bengali-language websites, cataloguing the most frequent screen-reader failure modes and their markup causes.',
 'Sara Ahmed','UIU Research Repository',2026,'Dataset','Robotics',380,142,5);

INSERT INTO `publication_tags` (`publication_id`,`interest_id`) VALUES
(1,2),(1,11),(1,1),
(2,16),(2,7),(2,14),
(3,18),(3,4),
(4,5),(4,2),(4,17),
(5,13),
(6,12),(6,4),
(7,3),(7,17),(7,2),
(8,11),(8,15);

INSERT INTO `publication_likes` (`publication_id`,`user_id`) VALUES
(1,2),(1,4),(1,5),(1,10),(4,2),(4,5),(3,11),(7,3),(7,4);

INSERT INTO `publication_saves` (`publication_id`,`user_id`) VALUES
(1,2),(1,5),(4,10),(2,12),(7,2);

INSERT INTO `publication_comments` (`publication_id`,`user_id`,`parent_id`,`body`) VALUES
(1,8,NULL,'Excellent study on synchrony. Did you account for the baseline noise in the frontal channels during active movement?'),
(1,2,1,'Great question. We used a modified ICA algorithm specifically designed for hyperscanning data to isolate motion artifacts without losing signal complexity.'),
(1,5,NULL,'Is the EEG preprocessing pipeline available anywhere? I would like to reuse it for a course project.'),
(4,10,NULL,'The evaluation harness release is really useful, thank you for open-sourcing it.');

INSERT INTO `publication_versions`
  (`publication_id`,`version_no`,`title`,`abstract`,`file_name`,`file_path`,`file_size`,`change_note`,`created_by`,`created_at`)
SELECT p.`id`, 1, p.`title`, p.`abstract`, p.`file_name`, p.`file_path`, p.`file_size`,
       'Initial submission.', p.`user_id`, p.`created_at`
FROM `publications` p WHERE p.`id` NOT IN (1,4,7);

INSERT INTO `publication_versions`
  (`publication_id`,`version_no`,`title`,`abstract`,`file_name`,`file_path`,`file_size`,`change_note`,`created_by`,`created_at`)
SELECT p.`id`, 1, p.`title`, p.`abstract`, NULL, NULL, 0,
       'Initial submission.', p.`user_id`, DATE_SUB(p.`created_at`, INTERVAL 90 DAY)
FROM `publications` p WHERE p.`id` IN (1,4,7);

INSERT INTO `publication_versions`
  (`publication_id`,`version_no`,`title`,`abstract`,`file_name`,`file_path`,`file_size`,`change_note`,`created_by`,`created_at`)
SELECT p.`id`, 2, p.`title`, p.`abstract`, NULL, NULL, 0,
       'Revised after supervisor review: expanded the methodology section and corrected two figures.',
       p.`user_id`, DATE_SUB(p.`created_at`, INTERVAL 40 DAY)
FROM `publications` p WHERE p.`id` IN (1,4,7);

INSERT INTO `publication_versions`
  (`publication_id`,`version_no`,`title`,`abstract`,`file_name`,`file_path`,`file_size`,`change_note`,`created_by`,`created_at`)
SELECT p.`id`, 3, p.`title`, p.`abstract`, p.`file_name`, p.`file_path`, p.`file_size`,
       'Camera-ready version. Added the ethics statement and updated the reference list.',
       p.`user_id`, p.`created_at`
FROM `publications` p WHERE p.`id` = 1;

UPDATE `publication_versions` v
  JOIN `publications` p ON p.`id` = v.`publication_id`
  SET v.`file_name` = p.`file_name`, v.`file_path` = p.`file_path`,
      v.`file_size` = p.`file_size`, v.`created_at` = p.`created_at`
WHERE v.`publication_id` IN (4,7) AND v.`version_no` = 2;

UPDATE `publications` SET `current_version` = 3 WHERE `id` = 1;
UPDATE `publications` SET `current_version` = 2 WHERE `id` IN (4,7);

INSERT INTO `communities` (`name`,`description`,`field`,`icon`,`created_by`) VALUES
('AI Research Lab','Exploring neural networks, natural language processing, and ethical AI implementation across interdisciplinary domains.','Artificial Intelligence','AI',3),
('Sustainable Energy Hub','Dedicated to renewable energy technologies, grid optimisation, and reducing carbon footprints through engineering innovation.','Engineering','SE',6),
('Data Science Circle','Advanced data visualisation, predictive modeling, and big data analytics for scientific and commercial research.','Data Science','DS',5),
('Life Sciences Network','Fostering collaboration in biotechnology, genetics, and pharmaceutical research with institutional ethics compliance.','Life Sciences','LS',8),
('Robotics Collective','From industrial automation to humanoid robotics, focusing on hardware-software integration and machine vision.','Engineering','RC',6),
('Quantum Computing Group','Theoretical and experimental research into quantum algorithms, cryptography, and superconducting circuits.','Artificial Intelligence','QC',12);

INSERT INTO `community_members` (`community_id`,`user_id`) VALUES
(1,2),(1,3),(1,4),(1,5),(1,10),(1,12),
(2,6),(2,11),(2,2),
(3,5),(3,10),(3,11),(3,2),
(4,8),(4,3),
(5,6),(5,9),
(6,12),(6,8);

INSERT INTO `community_posts` (`community_id`,`user_id`,`title`,`body`,`views`) VALUES
(1,2,'Best augmentation strategy for small leaf-disease datasets?','We only have about 12k labelled images across 8 classes and the model overfits after 15 epochs. Has anyone had luck with mixup or CutMix on plant imagery specifically? Standard flips and rotations are not enough.',142),
(1,4,'Reading group: Transformers in medical imaging','Starting a weekly reading group on Thursdays, 7 PM, online. First paper is the ViT-Adapter line of work. Reply here if you want the invite link.',86),
(3,5,'Cleaning WBES panel data - any shortcuts?','The enterprise survey files have inconsistent firm identifiers across waves. How are people handling the merge without losing half the panel?',64),
(2,6,'Low-cost irradiance sensors that actually survive monsoon','Looking for sensor recommendations that do not die after two weeks of Dhaka rain. Budget is tight.',97),
(6,12,'Is quantum key distribution practical on campus fibre?','Curious whether anyone has tried QKD over existing campus infrastructure rather than dedicated dark fibre.',53);

INSERT INTO `community_replies` (`post_id`,`user_id`,`body`) VALUES
(1,3,'Mixup tends to hurt on fine-grained leaf texture because it blurs the lesion boundaries. Try heavy colour jitter plus random erasing instead, and freeze the backbone for the first five epochs.'),
(1,5,'Agreed. Also check your train/val split - if images from the same plant appear in both, your validation accuracy is lying to you.'),
(2,10,'Count me in for the reading group.'),
(3,11,'I used a fuzzy match on firm name plus district plus sector, then manually verified the ambiguous 3%. Tedious but it saved most of the panel.'),
(4,2,'We used sealed BH1750 modules in a 3D-printed vented housing. Survived a full monsoon at the rooftop lab.');

INSERT INTO `messages` (`sender_id`,`receiver_id`,`body`,`is_read`,`created_at`) VALUES
(8,2,'Good morning! I have been reviewing the latest data from the physics repository. Have you managed to finalise the Lab_Data_V2 summary yet?',1,'2026-09-10 10:30:00'),
(2,8,'Yes, Dr. Islam. I have compiled the results into a CSV format for your review. The trend in the secondary particle decay looks consistent with our hypothesis.',1,'2026-09-10 10:35:00'),
(8,2,'The peer review draft looks solid. I suggest we highlight the anomaly in Figure 4 before submitting it to the journal.',0,'2026-09-10 10:42:00'),
(9,2,'Did you check the new accessibility API? It might help with the Bengali screen reader study.',0,'2026-09-11 09:16:00'),
(10,2,'I have updated the repository with the cleaned Bengali corpus.',1,'2026-09-10 15:02:00'),
(2,10,'Perfect, I will pull it tonight and run the agreement script.',1,'2026-09-10 15:20:00'),
(11,2,'Meeting rescheduled to 4 PM tomorrow.',1,'2026-09-10 12:00:00'),
(4,2,'Hi Mehrab! I am interested in the AI-Based Plant Disease Detection project. Is the FYDP team still open?',0,'2026-09-11 11:40:00'),
(7,2,'The dataset for the ML model is ready.',0,'2026-09-11 08:10:00');

INSERT INTO `connections` (`requester_id`,`addressee_id`,`status`) VALUES
(2,3,'accepted'),(2,4,'accepted'),(2,5,'accepted'),(4,2,'accepted'),
(2,8,'accepted'),(10,2,'accepted'),(6,2,'pending'),(9,2,'pending');

INSERT INTO `events` (`title`,`description`,`event_type`,`venue`,`event_date`,`capacity`,`created_by`) VALUES
('Research Workspace: Deep Learning','A hands-on workshop covering practical deep learning workflows, from dataset preparation to model deployment on constrained hardware. Bring a laptop.','Workshop','UIU Auditorium, Level 5','2026-09-22 10:00:00',120,3),
('Collaboration Meetup: AI in Agriculture','An informal meetup connecting CSE and agricultural science researchers working on precision farming and crop health monitoring.','Meetup','Room 411, UIU Permanent Campus','2026-09-24 15:00:00',60,3),
('Seminar: Writing Your First Q1 Journal Paper','Prof. Kabir Hossain walks through the full pipeline of preparing, submitting and revising a manuscript for a Q1 journal, with real reviewer reports.','Seminar','UIU Multipurpose Hall','2026-10-02 14:00:00',200,12),
('FYDP Proposal Clinic','Bring your final year design project proposal and get structured feedback from faculty members across CSE and EEE.','Clinic','Lab 502, UIU','2026-10-09 11:00:00',45,3),
('Open Science & Reproducibility Panel','A panel discussion on data sharing, pre-registration and reproducible research practice in Bangladeshi universities.','Panel','UIU Auditorium, Level 5','2026-10-18 16:00:00',150,8),
('Bengali NLP Datathon','A one-day datathon building open annotated resources for Bengali language technology. Teams of three.','Datathon','UIU Computer Lab Complex','2026-11-05 09:00:00',80,12);

INSERT INTO `event_rsvps` (`event_id`,`user_id`) VALUES
(1,2),(1,4),(1,5),(1,10),(2,2),(2,4),(3,2),(3,10),(3,12),(6,10),(6,2),(4,7);

INSERT INTO `notifications` (`user_id`,`text`,`link`,`is_read`) VALUES
(2,'Tanvir Rahman requested to join AI-Based Plant Disease Detection','opportunity.php?id=1',0),
(2,'Dr. Ariful Islam sent you a message','messages.php',0),
(2,'Your publication reached 1,000 views','publication.php?id=1',1),
(2,'Reminder: Research Workspace: Deep Learning starts in 3 days','events.php',0);
