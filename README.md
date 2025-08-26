# Community Connect - Volunteer Coordinator Platform

A comprehensive web-based application designed to connect volunteers with organizations and manage volunteer activities efficiently.

**Technology Stack**: Pure HTML, CSS, JavaScript, PHP, MySQL (NO external libraries or frameworks)  
**Database Layer**: MySQLi Procedural (NO PDO or ORM)  
**Design Theme**: Blue and White color scheme  
**Architecture**: Pure PHP/MySQL volunteer coordination platform

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [User Roles](#user-roles)
- [Business Rules](#business-rules)
- [Database Structure](#database-structure)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Contributing](#contributing)

## Overview

The **Community Connect Platform** is a comprehensive web-based application designed to manage and organize volunteer activities effectively. It connects volunteers with organizations, streamlines project management, and facilitates community engagement.

The platform serves as a bridge between three main user groups while ensuring proper access control, data validation, and a seamless user experience.

## Features

### Core Functionality

- User registration and authentication system
- Role-based access control (Admin, Organization, Volunteer)
- Project creation and management
- Volunteer-project assignment system
- Organization membership management
- Project review and approval workflow
- Profile management for volunteers
- Comprehensive admin dashboard
- Announcements system
- Reporting and analytics

### Key Capabilities

- Self-assignment system for volunteers to projects
- Project filtering for assigned tasks
- One organization per volunteer policy
- Guest project suggestion system
- Confirmation messages for all database operations
- Data validation and integrity checks

## User Roles

### 1. Admin

- **System Management**: Complete control over users, organizations, and projects
- **Project Approval**: Review and approve projects submitted by non-registered users
- **User Management**: Create, update, and manage all user accounts
- **Reports**: Generate comprehensive reports and analytics
- **Announcements**: Create and manage system-wide announcements

### 2. Organizations

- **Project Management**: Create and manage volunteer projects
- **Volunteer Oversight**: View registered volunteers and track participation
- **Organization Profile**: Manage organization details and information

### 3. Individual Volunteers

- **Profile Management**: Update personal information and preferences
- **Project Discovery**: Browse available volunteer opportunities
- **Self-Assignment**: Join projects of interest
- **Organization Membership**: Join one organization
- **Task Filtering**: View and manage assigned projects

### 4. Guest Users (Non-logged)

- **Project Suggestions**: Submit project ideas for admin review
- **Public Access**: View approved projects and general information

## Business Rules

### Access Control

- Volunteers must be logged in to view and join projects
- Each volunteer can join **only one** organization at a time
- Volunteers can only edit their own profiles
- Admin approval required for guest-submitted projects

### Project Management

- Admin-created projects are automatically active
- Organization-created projects are immediately available
- Guest-submitted projects require admin review and approval
- One volunteer can sign up **only once per project**
- Multiple volunteers can join the same project (within capacity limits)

### Data Integrity

- All database modifications require confirmation messages
- Required field validation before database operations
- Email format validation and uniqueness
- Date validation for project timelines

## Database Structure

### Users Table

```sql
users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'organization', 'volunteer') NOT NULL,
  organization_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(org_id)
);
```

### Organizations Table

```sql
organizations (
  org_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Projects Table

```sql
projects (
  project_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  location VARCHAR(200),
  start_date DATE,
  end_date DATE,
  requirements TEXT,
  capacity INT DEFAULT 0,
  created_by INT NOT NULL,
  status ENUM('pending','approved','active','completed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);
```

### Volunteer Project Assignments

```sql
volunteer_projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  volunteer_id INT NOT NULL,
  project_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(volunteer_id, project_id),
  FOREIGN KEY (volunteer_id) REFERENCES users(user_id),
  FOREIGN KEY (project_id) REFERENCES projects(project_id)
);
```


## Project Structure

### Core Components

#### 1. Authentication System (`login.php`)

- **Login Form**: Email/password authentication
- **Session Management**: Secure session handling
- **Role-Based Routing**: Redirect to appropriate dashboard
- **Password Security**: Hashed password storage using `password_hash()`

#### 2. Public Interface (`index.php`)

- **Landing Page**: Public-facing home page
- **Project Display**: Show approved volunteer projects
- **Guest Submissions**: Allow project suggestions from non-users
- **Announcements**: Display system announcements
- **Navigation**: Links to login and registration

#### 3. Admin Dashboard (`admin_dashboard.php`)

- **User Management**: Complete CRUD operations for all users
- **Project Oversight**: Manage and approve all projects
- **Organization Management**: Create and manage organizations
- **Assignment Tracking**: View all volunteer-project assignments
- **Content Management**: Post and edit announcements
- **Analytics**: Generate comprehensive reports
- **System Controls**: Confirmation dialogs for all operations

#### 4. Feature Implementation

- **Profile Management**: Volunteer profile editing system
- **Project Discovery**: Browse and filter projects
- **Assignment System**: Self-assignment functionality
- **Organization Membership**: Single organization policy enforcement
- **Validation Layer**: Form validation and data integrity
- **Confirmation System**: User confirmation for all database operations

#### 5. Support System (`help.php`)

- **User Guidance**: Platform usage instructions
- **FAQ System**: Common questions and answers
- **Contact Support**: Admin inquiry system

## Getting Started

### Prerequisites

- PHP 7.4 or higher with MySQLi extension
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser with JavaScript enabled
- **NO external frameworks or libraries required**

### Technology Constraints

- **Database**: MySQLi Procedural functions only (mysqli_connect, mysqli_query, etc.)
- **Frontend**: Pure HTML5, CSS3, Vanilla JavaScript  
- **Backend**: PHP 7.4+ with MySQLi extension
- **Styling**: Blue (#007bff, #0056b3) and White (#ffffff, #f8f9fa) color palette
- **No External Dependencies**: No Bootstrap, jQuery, React, Vue, etc.

### Installation

1. Clone the repository
2. Set up your web server to point to the project directory
3. Create a MySQL database and import the schema
4. Configure database connection settings
5. Set up proper file permissions
6. Access the application through your web browser

### Configuration

- Update database connection parameters
- Configure email settings for notifications
- Set up admin user credentials
- Configure session settings

## Contributing

### Development Guidelines

- Follow PHP coding standards
- Use MySQLi Procedural functions only (NO PDO)
- Implement proper error handling
- Include confirmation dialogs for all database operations
- Validate all user inputs
- Use prepared statements for database queries
- Maintain Blue and White color scheme
- Pure HTML/CSS/JS - NO external frameworks
- Maintain consistent code formatting

### Styling Guidelines

- **Primary Colors**: Blue (#007bff, #0056b3) and White (#ffffff, #f8f9fa)
- **CSS**: Pure CSS3 only - no Bootstrap, Tailwind, etc.
- **JavaScript**: Vanilla JS only - no jQuery, frameworks
- **Responsive**: CSS Grid/Flexbox for layouts
- **Typography**: System fonts or web-safe fonts only

### Testing

- Test all user roles and permissions
- Verify database integrity constraints
- Test form validations
- Ensure proper error handling
- Validate security measures

---

**Note**: This project is designed as a comprehensive full-stack web application using **pure PHP/MySQL with MySQLi Procedural** functions and a **Blue/White theme**. It emphasizes proper software engineering practices, database design, and user experience **without external dependencies or frameworks**.
