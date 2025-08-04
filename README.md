# Windsurf Ecommerce Builder

A full-stack mobile-first e-commerce web application built with Laravel 10, MySQL, and modern frontend technologies.

## 🚀 Features

- **Mobile-First Design**: Responsive layout using TailwindCSS
- **Product Management**: Categories, products with images, reviews
- **Shopping Cart & Wishlist**: Persistent cart and wishlist functionality
- **User Authentication**: Registration, login, email verification, password reset
- **Secure Checkout**: Stripe and PayPal integration
- **Order Tracking**: Complete order management and tracking system
- **Admin Panel**: Product CRUD and order management
- **Newsletter**: Email subscription with MailChimp integration
- **Instagram Feed**: Display business Instagram posts
- **REST API**: Full API for mobile app or headless frontend
- **Testing**: PHPUnit feature tests and browser tests

## 🛠 Tech Stack

- **Backend**: Laravel 10, PHP 8.1+, MySQL
- **Frontend**: Blade templates, Vue.js components, Alpine.js
- **Styling**: TailwindCSS
- **JavaScript**: Swiper.js for carousels, Axios for AJAX
- **Payment**: Stripe, PayPal
- **Authentication**: Laravel Sanctum
- **Testing**: PHPUnit, Laravel Dusk, Pest

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+
- Redis (optional, for caching and queues)

## 🏗 Installation

### 1. Clone and Setup

```bash
git clone <repository-url> windsurf-ecommerce
cd windsurf-ecommerce
composer install
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=windsurf_ecommerce
DB_USERNAME=your_username
DB_PASSWORD=your_password

STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key

PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 4. Build Assets

```bash
npm run dev
# or for production
npm run build
```

### 5. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` to see your application.

## 🗄 Database Schema

### Core Tables
- `users` - User accounts and authentication
- `categories` - Product categories with nested support
- `products` - Product catalog with pricing and inventory
- `product_images` - Product image gallery
- `cart_items` - Shopping cart items
- `wishlist_items` - User wishlist items
- `orders` - Order records
- `order_items` - Order line items
- `reviews` - Product reviews and ratings
- `newsletter_subscribers` - Email newsletter subscriptions

## 🎨 Frontend Structure

### Pages
- **Home**: Category grid, product carousels, newsletter signup
- **Shop**: Product listing with filters and pagination
- **Product Detail**: Product info, reviews, related products
- **Cart**: Shopping cart management
- **Wishlist**: Saved items
- **Checkout**: Secure payment processing
- **Account**: Order history and profile management
- **FAQ**: Frequently asked questions
- **404**: Custom error page

### Components
- Navigation with search modal
- Product cards and carousels
- Cart/wishlist buttons with AJAX
- Payment forms (Stripe/PayPal)
- Instagram feed integration
- Newsletter signup form

## 🧪 Testing

### Run Tests

```bash
# PHPUnit tests
php artisan test

# Browser tests (requires Chrome/Chromium)
php artisan dusk

# Pest tests
./vendor/bin/pest
```

### Test Coverage
- Authentication flows
- Cart and wishlist operations
- Checkout process
- API endpoints
- Frontend interactions

## 🚀 Deployment

### Production Setup

1. Set environment to production:
```bash
APP_ENV=production
APP_DEBUG=false
```

2. Optimize application:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

3. Build production assets:
```bash
npm run build
```

### Docker Deployment (Optional)

```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

## 📚 API Documentation

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Products
- `GET /api/products` - List products with filters
- `GET /api/products/{id}` - Get product details
- `GET /api/categories` - List categories

### Cart & Wishlist
- `GET /api/cart` - Get cart items
- `POST /api/cart` - Add item to cart
- `PUT /api/cart/{id}` - Update cart item
- `DELETE /api/cart/{id}` - Remove cart item
- `GET /api/wishlist` - Get wishlist items
- `POST /api/wishlist` - Add item to wishlist

### Orders
- `POST /api/checkout` - Process checkout
- `GET /api/orders` - Get user orders
- `GET /api/orders/{id}` - Get order details

## 🔧 Configuration

### Payment Gateways
Configure Stripe and PayPal credentials in `.env` file.

### Email Configuration
Set up SMTP settings for transactional emails (order confirmations, password resets).

### Instagram Integration
Add Instagram Graph API credentials for feed integration.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For support and questions, please open an issue in the GitHub repository or contact the development team.

---

Built with ❤️ using Laravel and modern web technologies.
