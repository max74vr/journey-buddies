-- Compagni di Viaggi - Database Schema
-- MySQL Database for Travel Community Platform

-- Drop existing tables if they exist
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_groups;
DROP TABLE IF EXISTS chat_group_members;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS travel_participants;
DROP TABLE IF EXISTS user_languages;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS travel_posts;
DROP TABLE IF EXISTS featured_stories;
DROP TABLE IF EXISTS users;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_photo VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    verification_document VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    city VARCHAR(100),
    country VARCHAR(100),
    reputation_score DECIMAL(3,2) DEFAULT 0.00,
    total_trips INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_reputation (reputation_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Preferences Table
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    travel_style VARCHAR(50) NOT NULL, -- 'avventura', 'mare', 'città', 'low-cost', 'relax', 'party'
    accommodation_type VARCHAR(50), -- 'hostel', 'hotel', 'camper', 'airbnb'
    food_preference VARCHAR(50), -- 'vegan', 'vegetarian', 'omnivore', 'halal', 'kosher'
    budget_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    smoking BOOLEAN DEFAULT FALSE,
    pets BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_travel_style (travel_style)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Languages Table
CREATE TABLE user_languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    language_code VARCHAR(10) NOT NULL, -- 'it', 'en', 'es', 'fr', etc.
    language_name VARCHAR(50) NOT NULL,
    proficiency ENUM('basic', 'intermediate', 'fluent', 'native') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_language (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Badges Table
CREATE TABLE user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_type VARCHAR(50) NOT NULL, -- 'early_adopter', 'travel_master', 'verified', 'social_butterfly', etc.
    badge_name VARCHAR(100) NOT NULL,
    badge_icon VARCHAR(255),
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Travel Posts Table (Bacheca Viaggi)
CREATE TABLE travel_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    destination VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    travel_type VARCHAR(50) NOT NULL, -- 'avventura', 'mare', 'smart-working', 'città', etc.
    budget_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    estimated_cost DECIMAL(10,2),
    max_participants INT DEFAULT 5,
    current_participants INT DEFAULT 1,
    accommodation_type VARCHAR(50),
    is_flexible BOOLEAN DEFAULT TRUE, -- date flessibili?
    status ENUM('planning', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_destination (destination),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_creator (creator_id),
    INDEX idx_travel_type (travel_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Travel Participants Table
CREATE TABLE travel_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    travel_post_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'left') DEFAULT 'pending',
    join_message TEXT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (travel_post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (travel_post_id, user_id),
    INDEX idx_travel_post (travel_post_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews Table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    travel_post_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_id INT NOT NULL,
    punctuality_score TINYINT CHECK (punctuality_score BETWEEN 1 AND 5),
    group_spirit_score TINYINT CHECK (group_spirit_score BETWEEN 1 AND 5),
    respect_score TINYINT CHECK (respect_score BETWEEN 1 AND 5),
    adaptability_score TINYINT CHECK (adaptability_score BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (travel_post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (travel_post_id, reviewer_id, reviewed_id),
    INDEX idx_reviewed (reviewed_id),
    INDEX idx_travel_post (travel_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Groups Table
CREATE TABLE chat_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    travel_post_id INT,
    group_name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (travel_post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_travel_post (travel_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Group Members Table
CREATE TABLE chat_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (chat_group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (chat_group_id, user_id),
    INDEX idx_chat_group (chat_group_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages Table
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_group_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (chat_group_id) REFERENCES chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_group (chat_group_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Featured Stories Table
CREATE TABLE featured_stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    travel_post_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    story TEXT NOT NULL,
    cover_image VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (travel_post_id) REFERENCES travel_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_featured (is_featured),
    INDEX idx_travel_post (travel_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
