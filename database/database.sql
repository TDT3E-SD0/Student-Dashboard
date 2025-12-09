-- ============================================
-- TDT3E - Student Dashboard Database Schema
-- ============================================
-- Created: December 2025
-- Purpose: Complete database schema for Student Dashboard
-- MySQL Version: 5.7+

-- Create Database
CREATE DATABASE IF NOT EXISTS `student_dashboard` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `student_dashboard`;

-- ============================================
-- TABLE: users
-- Description: User accounts with approval workflow
-- ============================================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') DEFAULT 'student',
  `status` ENUM('pending', 'active', 'suspended', 'deleted') DEFAULT 'pending',
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `profile_picture` VARCHAR(255),
  `bio` TEXT,
  `date_of_birth` DATE,
  `phone` VARCHAR(20),
  `address` TEXT,
  `city` VARCHAR(100),
  `country` VARCHAR(100),
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `approval_date` TIMESTAMP NULL,
  `approved_by` INT,
  `last_login` TIMESTAMP NULL,
  `is_verified` BOOLEAN DEFAULT 0,
  `verification_token` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`),
  INDEX `idx_username` (`username`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: grades
-- Description: Student grades and assessments
-- ============================================
CREATE TABLE `grades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `assessment_type` ENUM('mid-term', 'final', 'quiz', 'assignment', 'project', 'participation') DEFAULT 'quiz',
  `score` DECIMAL(5, 2) NOT NULL,
  `max_score` DECIMAL(5, 2) DEFAULT 100,
  `percentage` DECIMAL(5, 2) GENERATED ALWAYS AS ((score / max_score) * 100) STORED,
  `weight` DECIMAL(5, 2) DEFAULT 1.0,
  `grade_letter` ENUM('A', 'B', 'C', 'D', 'F', 'N/A'),
  `assessment_date` DATE NOT NULL,
  `feedback` TEXT,
  `instructor_name` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_subject` (`user_id`, `subject`),
  INDEX `idx_assessment_type` (`assessment_type`),
  INDEX `idx_assessment_date` (`assessment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: tasks
-- Description: Student tasks and assignments
-- ============================================
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `subject` VARCHAR(100),
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `status` ENUM('not-started', 'in-progress', 'completed', 'overdue', 'cancelled') DEFAULT 'not-started',
  `deadline` DATETIME NOT NULL,
  `completion_date` DATETIME,
  `category` VARCHAR(100),
  `tags` VARCHAR(255),
  `assignment_type` ENUM('homework', 'project', 'reading', 'exam-prep', 'research', 'other'),
  `estimated_hours` DECIMAL(5, 2),
  `actual_hours` DECIMAL(5, 2),
  `attachment_path` VARCHAR(255),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_status` (`user_id`, `status`),
  INDEX `idx_deadline` (`deadline`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_completion_date` (`completion_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: blogs
-- Description: Blog posts written by students
-- ============================================
CREATE TABLE `blogs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `author_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE,
  `content` LONGTEXT NOT NULL,
  `excerpt` TEXT,
  `featured_image` VARCHAR(255),
  `category` VARCHAR(100),
  `tags` VARCHAR(255),
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `view_count` INT DEFAULT 0,
  `like_count` INT DEFAULT 0,
  `comment_count` INT DEFAULT 0,
  `is_featured` BOOLEAN DEFAULT 0,
  `published_date` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_author_status` (`author_id`, `status`),
  INDEX `idx_published_date` (`published_date`),
  INDEX `idx_status` (`status`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: blog_comments
-- Description: Comments on blog posts
-- ============================================
CREATE TABLE `blog_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `blog_id` INT NOT NULL,
  `commenter_id` INT NOT NULL,
  `parent_comment_id` INT,
  `comment_text` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`commenter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_comment_id`) REFERENCES `blog_comments`(`id`) ON DELETE CASCADE,
  INDEX `idx_blog_status` (`blog_id`, `status`),
  INDEX `idx_commenter` (`commenter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: files
-- Description: File management (local + Google Drive metadata)
-- ============================================
CREATE TABLE `files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50),
  `file_size` BIGINT,
  `file_path` VARCHAR(255),
  `storage_type` ENUM('local', 'google-drive', 'dropbox') DEFAULT 'local',
  `google_drive_id` VARCHAR(255),
  `google_drive_url` VARCHAR(500),
  `access_token` VARCHAR(500),
  `is_shared` BOOLEAN DEFAULT 0,
  `shared_with` JSON,
  `category` VARCHAR(100),
  `description` TEXT,
  `mime_type` VARCHAR(100),
  `visibility` ENUM('private', 'shared', 'public') DEFAULT 'private',
  `tags` VARCHAR(255),
  `view_count` INT DEFAULT 0,
  `last_accessed` TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_category` (`user_id`, `category`),
  INDEX `idx_storage_type` (`storage_type`),
  INDEX `idx_visibility` (`visibility`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: file_shares
-- Description: Track file sharing between users
-- ============================================
CREATE TABLE `file_shares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `file_id` INT NOT NULL,
  `owner_id` INT NOT NULL,
  `shared_with_user_id` INT,
  `shared_with_role` VARCHAR(50),
  `permission` ENUM('view', 'edit', 'download') DEFAULT 'view',
  `share_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shared_with_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_file_owner` (`file_id`, `owner_id`),
  INDEX `idx_shared_with` (`shared_with_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: audit_log
-- Description: Audit trail for admin actions
-- ============================================
CREATE TABLE `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_user_id` INT,
  `target_table` VARCHAR(50),
  `target_record_id` INT,
  `old_value` JSON,
  `new_value` JSON,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_admin_action` (`admin_id`, `action`),
  INDEX `idx_target_user` (`target_user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: notifications
-- Description: In-app notifications
-- ============================================
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `notification_type` ENUM('grade', 'task', 'blog', 'file', 'account', 'system') DEFAULT 'system',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `related_id` INT,
  `is_read` BOOLEAN DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `action_url` VARCHAR(500),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_read` (`user_id`, `is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: user_sessions
-- Description: Track active user sessions
-- ============================================
CREATE TABLE `user_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_token` VARCHAR(255) UNIQUE NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL,
  `is_active` BOOLEAN DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_active` (`user_id`, `is_active`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional - Remove in production)
-- ============================================

-- Admin user (password: hashed 'admin123')
INSERT INTO `users` (
  `username`, `email`, `password`, `role`, `status`, `first_name`, 
  `last_name`, `approval_date`, `is_verified`
) VALUES (
  'admin', 'admin@dashboard.local', '$2y$10$ZIvQHqI0SfD8bY9tN.5Z2.Z9L7K6M5P4Q3R2S1T0U9V8W7X6Y5Z4', 
  'admin', 'active', 'System', 'Administrator', NOW(), 1
);

-- Sample student user (password: hashed 'student123')
INSERT INTO `users` (
  `username`, `email`, `password`, `role`, `status`, `first_name`, 
  `last_name`, `approval_date`, `is_verified`
) VALUES (
  'john_doe', 'john@student.local', '$2y$10$ZIvQHqI0SfD8bY9tN.5Z2.Z9L7K6M5P4Q3R2S1T0U9V8W7X6Y5Z4', 
  'student', 'active', 'John', 'Doe', NOW(), 1
);

-- ============================================
-- INDEXES SUMMARY
-- ============================================
-- users: 5 indexes (id, status, role, email, username)
-- grades: 4 indexes (user_subject, assessment_type, assessment_date)
-- tasks: 4 indexes (user_status, deadline, priority, completion_date)
-- blogs: 5 indexes (author_status, published_date, status, slug, category)
-- files: 4 indexes (user_category, storage_type, visibility, created_at)
-- audit_log: 3 indexes (admin_action, target_user, created_at)
-- notifications: 2 indexes (user_read, created_at)
-- user_sessions: 3 indexes (user_active, session_token, last_activity)

-- ============================================
-- SECURITY NOTES
-- ============================================
-- 1. All passwords should be hashed with bcrypt (PHP: password_hash())
-- 2. Use prepared statements to prevent SQL injection
-- 3. Implement rate limiting for login attempts
-- 4. Use HTTPS for all data transmission
-- 5. Implement CSRF tokens for all forms
-- 6. Regular backups recommended
-- 7. Sanitize all user inputs
-- 8. Use environment variables for sensitive data
