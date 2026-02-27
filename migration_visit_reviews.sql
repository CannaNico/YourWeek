-- Migration: Aggiunge la tabella VisitReviews per le recensioni post-visita
-- Data: 2026-02-25

CREATE TABLE IF NOT EXISTS `visitreviews` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `nutritionist_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(1) UNSIGNED NOT NULL COMMENT 'Valutazione da 1 a 5 mele',
  `comment` text DEFAULT NULL COMMENT 'Commento opzionale del paziente',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_appointment_review` (`appointment_id`),
  KEY `idx_patient` (`patient_id`),
  KEY `idx_nutritionist` (`nutritionist_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `fk_review_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recensioni post-visita dei pazienti';
