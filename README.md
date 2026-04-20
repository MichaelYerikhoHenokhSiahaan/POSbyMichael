# Point Of Sale

Point Of Sale is a Laravel 13 web application for managing retail sales, customers, inventory, and warehouse invoices from a single dashboard.

## Overview

This project is built for store operations that need:

- point-of-sale checkout with receipts
- product, category, and customer management
- inventory restocking and stock movement tracking
- warehouse invoice creation and printable warehouse receipts

- transaction filtering and Excel export
- role-based access for developer and admin users

## Tech Stack

- PHP 8.3
- Laravel 13
- MySQL
- Vite 8
- Tailwind CSS 4
- PHPUnit 12

## Main Modules

### Authentication

- Username/password login
- Session-based authentication
- Protected routes under custom POS middleware

### Sales

- Create retail sales with multiple items
- Automatic invoice number generation
- Customer payment and change calculation
- Printable sales receipt
- Recent transaction search, filter, sort, and export

### Warehouse Invoices

- Create warehouse-focused invoices
- Track payment status: `Lunas` or `Belum Lunas`
- Print warehouse invoice receipts
- Record warehouse stock removal movements


### Master Data

- Users
- Categories
- Products
- Customers
- Transaction records

## Roles

The app currently uses two roles:

- `developer`: full access to dashboard, users, categories, products, warehouse, and transaction records
- `admin`: operational access for sales and other non-developer workflows

## Default Seed Data

The default database seeder creates:

- one developer user
- sample categories
- sample retail products
- one sample customer

Default login after seeding:

- Username: `MYHS`
- Password: `udindo123`

## Project Structure

Key folders:

- `app/Http/Controllers` - application workflows
- `app/Models` - Eloquent models
- `database/migrations` - schema definition
- `database/seeders` - initial data
- `resources/views` - Blade UI
- `routes/web.php` - web routes

## Local Setup

### Requirements

Make sure you have installed:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL

### Installation

1. Install PHP dependencies:

```bash
composer install
```

2. Copy environment file:

```bash
copy .env.example .env
```

3. Generate the application key:

```bash
php artisan key:generate
```

4. Configure your database in `.env`.

5. Run migrations:

```bash
php artisan migrate
```

6. Seed initial data:

```bash
php artisan db:seed
```

7. Install frontend dependencies:

```bash
npm install
```

## Running The App

For local development:

```bash
composer run dev
```

This starts:

- Laravel development server
- queue listener
- log watcher
- Vite dev server

If you prefer separate commands:

```bash
php artisan serve
npm run dev
```

## Build Assets

To build production assets:

```bash
npm run build
```

## Testing

Run the test suite with:

```bash
composer test
```

Or:

```bash
php artisan test
```

## Important Routes

Main route groups:

- `/login` - sign in
- `/sales` - POS sales flow
- `/warehouse-invoices` - warehouse invoice management

- `/restock` - stock replenishment
- `/customers` - customer management
- `/dashboard` - developer dashboard

## Notes

- The app redirects `/` to `/sales` after authentication.
- Sales receipt and warehouse invoice receipt pages are printable.
- Some developer-only features are protected by role middleware.
- The default README from Laravel has been replaced with project-specific documentation.
