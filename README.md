# Monthly Expenses Tracker

## Overview
Monthly Expenses Tracker is a web-based application developed using PHP and MySQL. It allows users to manage and track their personal expenses with secure authentication. The application supports monthly tracking, category-wise analysis, and historical expense viewing.

---

## Features
- User registration and login system
- Session-based authentication
- Add, view, and delete expenses
- Monthly expense tracking
- Category-wise expense breakdown
- View previous month expense history
- Automatic personal database creation per user
- Logout functionality

---

## Tech Stack
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL (installed separately)
- Visualization: Chart.js
- Server: PHP built-in development server

---

## Project Structure
expenses-tracker/
- login.php
- register.php
- index.php
- history.php
- logout.php
- style.css

---

## Database Setup

Create the main database:

CREATE DATABASE users_db;

USE users_db;

Create users table:

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL
);

---

## Setup Instructions

1. Clone the repository
   git clone https://github.com/your-username/expenses-tracker.git

2. Open the project in VS Code

3. Ensure MySQL server is running (installed separately)

4. Create database and table using phpMyAdmin or MySQL CLI

5. Run the PHP server from project directory
   php -S localhost:8000

6. Open in browser
   http://localhost:8000/login.php

---

## How It Works
- User registers and data is stored in users_db
- Login creates a session for the user
- A personal database is created for each user
- Expenses are stored in user-specific database
- Dashboard shows monthly summary and category breakdown
- History page displays previous month expenses

---

## Future Enhancements
- Cloud database integration
- Improved UI/UX design
- Export reports to PDF or Excel
- Budget limit notifications
- Mobile responsive design improvements

## Screenshots
Sample screenshots are provided in screenshots/ folder
