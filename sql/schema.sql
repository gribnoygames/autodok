-- АвтоДок: схема БД
-- MySQL/MariaDB, кодировка utf8mb4 (кириллица)

DROP DATABASE IF EXISTS autodok;
CREATE DATABASE autodok
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE autodok;

-- Пользователи портала
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник услуг (используется в select на форме заявки)
CREATE TABLE services (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заявки на обслуживание
-- status: new -> in_progress -> done | cancelled
-- cancel_reason заполняется только при status='cancelled'
CREATE TABLE requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    car_brand      VARCHAR(100) NOT NULL,
    car_plate      VARCHAR(10)  NOT NULL,
    service_id     INT          NOT NULL,
    visit_datetime DATETIME     NOT NULL,
    payment_method ENUM('cash','card') NOT NULL,
    status         ENUM('new','in_progress','done','cancelled')
                   NOT NULL DEFAULT 'new',
    cancel_reason  TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_requests_service
        FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Отзывы (1:1 к заявке, только при status='done')
CREATE TABLE reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT  NOT NULL UNIQUE,
    sentiment  ENUM('positive','negative') NOT NULL,
    text       TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_request
        FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Стартовый набор услуг
INSERT INTO services (name) VALUES
    ('Техническое обслуживание'),
    ('Шиномонтаж'),
    ('Диагностика двигателя'),
    ('Кузовной ремонт');