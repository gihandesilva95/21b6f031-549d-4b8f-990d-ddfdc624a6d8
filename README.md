## Assessment Reporting System

A Laravel-based CLI application that generates diagnostic, progress, and feedback reports for student assessments.

### Features

CLI Interface: Interactive prompts for user input
Three Report Types: Diagnostic, Progress, and Feedback
Data Processing: JSON file parsing and in-memory processing
Error Handling: Comprehensive validation and error messages
Automated Testing: Feature tests for all functionality
Docker Support: Containerized deployment option


## Setup Instructions

### Prerequisites
- PHP 8.1 or higher
- Composer
- Docker & Docker Compose (optional)

### Installation

1. Clone the repository
```bash
    git clone <repo-url>
    cd assessment-reporting-system
```

2. Install PHP dependencies
```bash
composer install
```

3. setup env file
```bash
cp .env.example .env
```

4. Generate application key
```bash
php artisan key:generate
```

5. Setting up data files
```bash
mkdir -p storage/app/data

Copy sample data JSON files in the data/ directory to storage/app/data directory
```

##  Running the Application

### Direct Laravel Commands

1. Run the application
```bash
php artisan assessment:report
```

2. Run Tests
```bash
php artisan test
```

### Docker Compose

1. Run the application
```bash
docker-compose run app
```

2. Run Tests
```bash
docker-compose run test
```