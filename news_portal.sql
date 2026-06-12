-- ============================================================
-- Personalized News Portal — Database Schema
-- Run this in phpMyAdmin or via: mysql -u root -p < news_portal.sql
-- ============================================================

-- (Database creation bypassed for shared hosting environments where users cannot run CREATE DATABASE commands.
-- Please select your database, e.g., 'if0_41775458_newsportal045_db', in phpMyAdmin before importing.)

-- ─────────────────────────────────────────────
-- Table: users
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    is_admin    TINYINT(1)    NOT NULL DEFAULT 0,
    preferences TEXT          NOT NULL DEFAULT '{"keywords":[],"categories":[]}',
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- Table: bookmarks
-- Stores article URL + cached metadata so bookmarks page
-- doesn't need to re-call the API.
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookmarks (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    article_url    TEXT         NOT NULL,
    article_title  TEXT,
    article_image  TEXT,
    article_source VARCHAR(150),
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- Table: admin_posts
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    content    TEXT         NOT NULL,
    image_url  VARCHAR(500),
    category   VARCHAR(50)  NOT NULL DEFAULT 'general',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- Seed Data
-- Default admin: admin@news.com / Admin@123
-- Password hash generated with password_hash('Admin@123', PASSWORD_BCRYPT)
-- ─────────────────────────────────────────────
INSERT INTO users (name, email, password, is_admin, preferences) VALUES
(
    'Admin',
    'admin@news.com',
    '$2y$10$7H8PkDr5/VimofstMBIXeuHihYYMYxi.jNLvSOsdlEUyNEJsZbdGS', -- Admin@123
    1,
    '{"keywords":["technology","AI"],"categories":["technology","science"]}'
);

-- Sample admin post
INSERT INTO admin_posts (title, content, image_url, category) VALUES
(
    'Welcome to the News Portal!',
    'This is your personalized news hub. Set your preferences in your profile to see news tailored to your interests. You can bookmark articles, filter by category, and explore the latest stories from around the world.',
    '',
    'general'
);
