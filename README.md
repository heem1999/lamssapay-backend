# NFCPay Backend API

The backend for NFCPay is built with **Laravel 12** and provides a robust RESTful API for the mobile application and web dashboard. It handles user authentication, wallet management, transaction processing, and merchant services.

## 🛠️ Technology Stack

- **Framework:** Laravel 12.x
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum (Token-based)
- **API Style:** RESTful with JSON responses
- **PHP Version:** 8.2+

## 🚀 Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- MySQL

### Installation

1.  **Clone the repository** (if you haven't already):
    ```bash
    git clone <repository-url>
    cd nfcPay/backend
    ```

2.  **Install Dependencies:**
    ```bash
    composer install
    ```

3.  **Environment Configuration:**
    Copy the example environment file and configure your database credentials.
    ```bash
    cp .env.example .env
    ```
    Edit `.env` and set your database details:
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nfcpay_dev
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4.  **Generate Application Key:**
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations & Seed Database:**
    This will create the necessary tables and populate them with initial test data.
    ```bash
    php artisan migrate:fresh --seed
    ```

6.  **Serve the Application:**
    ```bash
    php artisan serve
    ```
    The API will be available at `http://127.0.0.1:8000`.

## 📚 API Documentation

The API is versioned. The current version is **v1**.

- **Base URL:** `http://127.0.0.1:8000/api/v1`
- **Authentication:** Bearer Token (Sanctum)

### Key Endpoints

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/auth/login` | Login and retrieve an access token |
| `POST` | `/auth/register` | Register a new user account |
| `GET` | `/me` | Get current user profile |
| `GET` | `/wallets` | List user wallets |
| `GET` | `/transactions` | Get transaction history |

For full documentation, please refer to [docs/API_DOCUMENTATION.md](../docs/API_DOCUMENTATION.md).

## 🧪 Testing

Run the test suite to ensure everything is working correctly:

```bash
php artisan test
```

## 📂 Project Structure

- `app/Http/Controllers/Api` - API Controllers
- `app/Models` - Eloquent Models
- `app/Services` - Business Logic Services
- `database/migrations` - Database Schema Definitions
- `routes/v1.php` - API V1 Route Definitions
