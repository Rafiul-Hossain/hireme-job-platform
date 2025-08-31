# HireMe Job Platform API Documentation

## Authentication
All API endpoints except registration and login require authentication using JWT tokens. Include the token in the `Authorization` header:

```
Authorization: Bearer {your_jwt_token}
```

## Base URL
```
https://api.hireme.test/api
```

## Authentication Endpoints

### 1. Register User
Register a new user account.

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "job_seeker"
}
```

**Parameters:**
- `name` (required): Full name of the user
- `email` (required): Valid email address
- `password` (required): Password (min 8 characters)
- `password_confirmation` (required): Must match password
- `role` (required): One of: job_seeker, employee, admin

**Response (Success - 201 Created):**
```json
{
    "message": "Registration successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "job_seeker",
        "created_at": "2023-08-30T16:00:00.000000Z",
        "updated_at": "2023-08-30T16:00:00.000000Z"
    },
    "token": "eyJ0eXAiO..."
}
```

### 2. Login User
Authenticate a user and get JWT token.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (Success - 200 OK):**
```json
{
    "message": "Login successful",
    "token": "eyJ0eXAiO...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "job_seeker"
    }
}
```

### 3. Logout User
Invalidate the current JWT token.

**Endpoint:** `POST /api/logout`

**Response (Success - 200 OK):**
```json
{
    "message": "Successfully logged out"
}
```

## Job Endpoints

### 1. List Jobs
Get a paginated list of all jobs.

**Endpoint:** `GET /api/jobs`

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 10)
- `search` (optional): Search term for job title/description
- `category` (optional): Filter by job category
- `company_id` (optional): Filter by company

**Response (Success - 200 OK):**
```json
{
    "jobs": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "title": "Senior PHP Developer",
                "description": "We are looking for...",
                "requirements": ["5+ years PHP experience", "Laravel"],
                "salary_range": "60,000 - 80,000",
                "location": "Dhaka, Bangladesh",
                "type": "full-time",
                "company": {
                    "id": 1,
                    "name": "Tech Corp",
                    "logo_url": "https://example.com/logo.png"
                },
                "created_at": "2023-08-30T16:00:00.000000Z",
                "updated_at": "2023-08-30T16:00:00.000000Z"
            }
        ],
        "total": 50,
        "per_page": 10,
        "last_page": 5
    }
}
```

### 2. Create Job
Create a new job posting (Employee only).

**Endpoint:** `POST /api/jobs`

**Request Body:**
```json
{
    "title": "Senior PHP Developer",
    "description": "We are looking for...",
    "requirements": ["5+ years PHP experience", "Laravel"],
    "salary_range": "60,000 - 80,000",
    "location": "Dhaka, Bangladesh",
    "type": "full-time",
    "category": "software-development"
}
```

**Response (Success - 201 Created):**
```json
{
    "message": "Job created successfully",
    "job": {
        "id": 1,
        "title": "Senior PHP Developer",
        "description": "We are looking for...",
        "requirements": ["5+ years PHP experience", "Laravel"],
        "salary_range": "60,000 - 80,000",
        "location": "Dhaka, Bangladesh",
        "type": "full-time",
        "company_id": 1,
        "created_at": "2023-08-30T16:00:00.000000Z",
        "updated_at": "2023-08-30T16:00:00.000000Z"
    }
}
```

### 3. Get Job Details
Get detailed information about a specific job.

**Endpoint:** `GET /api/jobs/{id}`

**Response (Success - 200 OK):**
```json
{
    "job": {
        "id": 1,
        "title": "Senior PHP Developer",
        "description": "We are looking for...",
        "requirements": ["5+ years PHP experience", "Laravel"],
        "salary_range": "60,000 - 80,000",
        "location": "Dhaka, Bangladesh",
        "type": "full-time",
        "company": {
            "id": 1,
            "name": "Tech Corp",
            "description": "Leading tech company...",
            "logo_url": "https://example.com/logo.png",
            "website": "https://example.com"
        },
        "created_at": "2023-08-30T16:00:00.000000Z",
        "updated_at": "2023-08-30T16:00:00.000000Z"
    }
}
```

### 4. Update Job
Update an existing job posting (Employee only).

**Endpoint:** `PUT /api/jobs/{id}`

**Request Body:**
```json
{
    "title": "Updated PHP Developer",
    "description": "Updated description...",
    "requirements": ["Updated requirements"],
    "salary_range": "65,000 - 85,000",
    "location": "Dhaka, Bangladesh",
    "type": "full-time"
}
```

**Response (Success - 200 OK):**
```json
{
    "message": "Job updated successfully",
    "job": {
        "id": 1,
        "title": "Updated PHP Developer",
        "description": "Updated description...",
        "requirements": ["Updated requirements"],
        "salary_range": "65,000 - 85,000",
        "location": "Dhaka, Bangladesh",
        "type": "full-time",
        "company_id": 1,
        "created_at": "2023-08-30T16:00:00.000000Z",
        "updated_at": "2023-08-30T16:35:00.000000Z"
    }
}
```

### 5. Delete Job
Delete a job posting (Employee only).

**Endpoint:** `DELETE /api/jobs/{id}`

**Response (Success - 200 OK):**
```json
{
    "message": "Job deleted successfully"
}
```

## Application Endpoints

### 1. Apply for a Job
Submit an application for a job posting.

**Endpoint:** `POST /api/jobs/{job}/applications`

**Request Headers:**
- `Content-Type: multipart/form-data`
- `Accept: application/json`
- `Authorization: Bearer {token}`

**Request Body (multipart/form-data):**
- `cover_letter` (required): Cover letter content (50-2000 characters)
- `cv` (required): CV file (PDF, DOC, DOCX, max 5MB)

**Response (Success - 201 Created):**
```json
{
    "message": "Application submitted successfully",
    "application": {
        "id": 1,
        "user_id": 1,
        "job_id": 1,
        "cover_letter": "I'm very interested in this position...",
        "cv_path": "cvs/filename.pdf",
        "status": "pending",
        "created_at": "2023-08-30T16:00:00.000000Z",
        "updated_at": "2023-08-30T16:00:00.000000Z",
        "job": {
            "id": 1,
            "title": "Senior Developer",
            "company_id": 1,
            "description": "Job description...",
            "requirements": "Requirements...",
            "location": "Remote",
            "salary": 100000,
            "type": "full_time",
            "status": "active",
            "deadline": "2023-12-31T23:59:59.000000Z",
            "created_at": "2023-08-30T15:00:00.000000Z",
            "updated_at": "2023-08-30T15:00:00.000000Z"
        }
    }
}
```

**Response (Already Applied - 400 Bad Request):**
```json
{
    "message": "You have already applied to this job"
}
```

**Response (Validation Error - 422 Unprocessable Entity):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "cover_letter": ["The cover letter must be at least 50 characters."],
        "cv": ["The cv must be a file of type: pdf, doc, docx."]
    }
}
```

### 2. List Applications
Get user's job applications.

**Endpoint:** `GET /api/applications`

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 10)
- `status` (optional): Filter by status

**Response (Success - 200 OK):**
```json
{
    "applications": {
        "current_page": 1,
        "data": [
            {
                "id": 123,
                "job": {
                    "id": 1,
                    "title": "Senior PHP Developer",
                    "company": {
                        "name": "Tech Corp",
                        "logo_url": "https://example.com/logo.png"
                    }
                },
                "status": "pending_payment",
                "applied_at": "2023-08-30T16:30:00.000000Z",
                "created_at": "2023-08-30T16:30:00.000000Z",
                "updated_at": "2023-08-30T16:30:00.000000Z"
            }
        ],
        "total": 5,
        "per_page": 10,
        "last_page": 1
    }
}
```

### 3. Update Application Status
Update application status (Employee only).

**Endpoint:** `PUT /api/applications/{id}/status`

**Request Body:**
```json
{
    "status": "shortlisted",
    "remarks": "Good fit for the position"
}
```

**Response (Success - 200 OK):**
```json
{
    "message": "Application status updated",
    "application": {
        "id": 123,
        "status": "shortlisted",
        "remarks": "Good fit for the position",
        "updated_at": "2023-08-30T16:35:00.000000Z"
    }
}
```

## Payment Endpoints

### 1. Process Payment
Process a new payment for a job application.

**Endpoint:** `POST /api/payments`

**Request Body:**
```json
{
    "application_id": 123,
    "payment_method": "stripe"
}
```

**Parameters:**
- `application_id` (required): ID of the application to pay for
- `payment_method` (required): Payment method (stripe or sslcommerz)

**Response (Success - 201 Created):**
```json
{
    "message": "Payment processed successfully",
    "payment": {
        "id": 1,
        "user_id": 1,
        "application_id": 123,
        "amount": "100.00",
        "currency": "BDT",
        "payment_method": "stripe",
        "status": "completed",
        "transaction_id": "TXN-20230830-ABC123",
        "paid_at": "2023-08-30T16:35:13.000000Z",
        "created_at": "2023-08-30T16:35:13.000000Z",
        "updated_at": "2023-08-30T16:35:13.000000Z",
        "invoice": {
            "id": "INV-20230830-ABC123",
            "user": "John Doe",
            "amount": "100.00",
            "currency": "BDT",
            "time": "2023-08-30 16:35:13",
            "status": "completed"
        },
        "application": {
            "id": 123,
            "job_id": 1,
            "user_id": 1,
            "status": "paid",
            "applied_at": "2023-08-30T16:30:00.000000Z",
            "created_at": "2023-08-30T16:30:00.000000Z",
            "updated_at": "2023-08-30T16:35:13.000000Z"
        }
    }
}
```

### 2. Get Payment History
Get the authenticated user's payment history.

**Endpoint:** `GET /api/payments`

**Query Parameters:**
- `page` (optional): Page number for pagination (default: 1)
- `per_page` (optional): Number of items per page (default: 10)

**Response (Success - 200 OK):**
```json
{
    "payments": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "application_id": 123,
                "amount": "100.00",
                "currency": "BDT",
                "payment_method": "stripe",
                "status": "completed",
                "transaction_id": "TXN-20230830-ABC123",
                "paid_at": "2023-08-30T16:35:13.000000Z",
                "created_at": "2023-08-30T16:35:13.000000Z",
                "updated_at": "2023-08-30T16:35:13.000000Z",
                "invoice": {
                    "id": "INV-20230830-ABC123",
                    "user": "John Doe",
                    "amount": "100.00",
                    "currency": "BDT",
                    "time": "2023-08-30 16:35:13",
                    "status": "completed"
                },
                "application": {
                    "id": 123,
                    "job_id": 1,
                    "job": {
                        "id": 1,
                        "title": "Senior PHP Developer",
                        "company_id": 1,
                        "created_at": "2023-08-30T16:00:00.000000Z",
                        "updated_at": "2023-08-30T16:00:00.000000Z"
                    }
                }
            }
        ],
        "first_page_url": "http://api.hireme.test/api/payments?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://api.hireme.test/api/payments?page=1",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://api.hireme.test/api/payments?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": null,
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": null,
        "path": "http://api.hireme.test/api/payments",
        "per_page": 10,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    },
    "total_paid": "100.00"
}
```

### 3. Get Payment Details
Get details of a specific payment.

**Endpoint:** `GET /api/payments/{id}`

**URL Parameters:**
- `id` (required): ID of the payment to retrieve

**Response (Success - 200 OK):**
```json
{
    "payment": {
        "id": 1,
        "user_id": 1,
        "application_id": 123,
        "amount": "100.00",
        "currency": "BDT",
        "payment_method": "stripe",
        "status": "completed",
        "transaction_id": "TXN-20230830-ABC123",
        "paid_at": "2023-08-30T16:35:13.000000Z",
        "created_at": "2023-08-30T16:35:13.000000Z",
        "updated_at": "2023-08-30T16:35:13.000000Z"
    },
    "invoice": {
        "id": "INV-20230830-ABC123",
        "user": "John Doe",
        "amount": "100.00",
        "currency": "BDT",
        "time": "2023-08-30 16:35:13",
        "status": "completed"
    }
}
```

## Error Responses

### 400 Bad Request
```json
{
    "message": "Payment already processed for this application",
    "payment": {
        // Payment details
    }
}
```

### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
    "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
    "message": "No query results for model [App\\Models\\Payment] 123"
}
```

### 422 Unprocessable Entity (Validation Error)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "application_id": ["The application id field is required."],
        "payment_method": ["The selected payment method is invalid."]
    }
}
```
