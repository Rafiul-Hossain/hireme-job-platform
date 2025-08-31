# HireMe – Job Posting Platform

A comprehensive job platform where companies can post jobs and job seekers can apply, featuring role-based access control and payment integration.

## 🚀 Features

- **Role-Based Access Control**
  - Admin: Full system access
  - Employee (Recruiter): Manage company jobs and applications
  - Job Seeker: Browse and apply for jobs

- **Secure Authentication**
  - JWT-based authentication
  - Protected routes with role-based middleware

- **Job Management**
  - Create, read, update, and delete job postings
  - Filter and search jobs
  - Application tracking

- **Payment Integration**
  - Secure payment processing
  - Invoice generation
  - Payment status tracking

- **File Uploads**
  - CV/Resume upload (PDF, DOCX)
  - File validation (max 5MB)

## 🛠 Setup

1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/hireme-job-platform.git
   cd hireme-job-platform
   ```

2. Install dependencies
   ```bash
   composer install
   npm install
   ```

3. Configure environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Database setup
   - Configure your `.env` file with database credentials
   ```bash
   php artisan migrate --seed
   ```

5. Storage link
   ```bash
   php artisan storage:link
   ```

6. Start the development server
   ```bash
   php artisan serve
   ```

## ⚙️ Environment Variables

Required environment variables:

```env
APP_NAME=HireMe
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hireme
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your_jwt_secret_here

FILESYSTEM_DISK=public

# Payment Configuration
PAYMENT_GATEWAY=stripe  # or sslcommerz
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

## 📚 API Documentation
“See Postman Documentation.txt for full API testing documentation”
### Authentication
- `POST   /api/register` - Register a new user
- `POST   /api/login` - Login user
- `POST   /api/logout` - Logout user
- `GET    /api/me` - Get current user profile

### Jobs
- `GET    /api/jobs` - List all active jobs
- `POST   /api/jobs` - Create a new job (employer)
- `GET    /api/jobs/{id}` - Get job details
- `PUT    /api/jobs/{id}` - Update job (employer)
- `DELETE /api/jobs/{id}` - Delete job (employer)

### Applications
- `POST   /api/jobs/{job}/applications` - Apply for job (job seeker)
- `GET    /api/me/applications` - My applications (job seeker)
- `GET    /api/applications/{id}` - Get application details
- `GET    /api/employer/applications` - List company's applications (employer)
- `PUT    /api/employer/applications/{id}/status` - Update application status (employer)

### Admin
- `GET    /api/admin/users` - List all users (admin)
- `GET    /api/admin/jobs` - List all jobs (admin)
- `DELETE /api/admin/jobs/{id}` - Delete any job (admin)
- `GET    /api/admin/statistics` - System statistics (admin)

## 💳 Payment Flow

1. **Job Application**
   - User applies for a job with CV
   - System creates a pending application
   - User is redirected to payment gateway

2. **Payment Processing**
   - User completes payment
   - Payment gateway sends webhook
   - System verifies and updates payment status
   - Application status is updated to 'pending_review'

## 📁 File Uploads

- **Accepted Formats**: PDF, DOCX
- **Maximum Size**: 5MB
- **Storage Location**: `storage/app/public/cvs`
- **Access URL**: `/storage/cvs/filename.ext`

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

## 🚀 Deployment

1. Set up your production environment variables
2. Run migrations:
   ```bash
   php artisan migrate --force
   ```
3. Optimize the application:
   ```bash
   php artisan optimize
   ```

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

## 👥 Contributing

Contributions are welcome! Please read our [contributing guidelines](CONTRIBUTING.md) before submitting pull requests.
