# Payment Integration Guide

This document provides information about the payment integration in the HireMe Job Platform.

## Overview

The payment system allows job seekers to pay a fee when applying for jobs. The system currently supports two payment methods:
- Stripe
- SSLCommerz

## Setup Instructions

### 1. Environment Variables

Add the following environment variables to your `.env` file:

```bash
# Payment Configuration
PAYMENT_AMOUNT=100.00
PAYMENT_CURRENCY=BDT

# Stripe Configuration (if using Stripe)
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

# SSLCommerz Configuration (if using SSLCommerz)
SSLCOMMERZ_STORE_ID=your_store_id
SSLCOMMERZ_STORE_PASSWORD=your_store_password
SSLCOMMERZ_MODE=sandbox
```

### 2. Database Migrations

Run the database migrations to create the necessary tables:

```bash
php artisan migrate
```

## API Endpoints

### Process Payment
- **URL:** `POST /api/payments`
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`
  - `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "application_id": 1,
    "payment_method": "stripe"
}
```

### Get Payment History
- **URL:** `GET /api/payments`
- **Headers:**
  - `Accept: application/json`
  - `Authorization: Bearer {token}`

### Get Payment Details
- **URL:** `GET /api/payments/{id}`
- **Headers:**
  - `Accept: application/json`
  - `Authorization: Bearer {token}`

## Testing with Postman

1. Import the Postman collection and environment files from the `docs` directory:
   - `HireMe-API.postman_collection.json`
   - `HireMe-API-Env.postman_environment.json`

2. Update the environment variables in Postman:
   - `base_url`: Your application URL (e.g., http://localhost:8000)
   - `test_user_email`: Test user email
   - `test_user_password`: Test user password

3. Execute the requests in order:
   1. Login to get an authentication token
   2. Process a payment
   3. View payment history
   4. View payment details

## Webhook Setup (Production)

For production, set up webhook endpoints to receive payment confirmations:

### Stripe Webhook
```bash
stripe listen --forward-to your-app-url/api/webhook/stripe
```

### SSLCommerz Webhook
Configure the following webhook URL in your SSLCommerz merchant panel:
```
POST https://your-app-url/api/webhook/sslcommerz
```

## Error Handling

The API returns standard HTTP status codes along with error messages in the following format:

```json
{
    "message": "Error message",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

## Security Considerations

- Always use HTTPS in production
- Store sensitive information (API keys, secrets) in environment variables
- Implement rate limiting for API endpoints
- Validate all input data
- Use proper error handling and logging

## Support

For any issues or questions, please contact the development team.
