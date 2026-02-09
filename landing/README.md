# JobAI Landing Page

## Unified Login/Register Portal

This landing page serves as the entry point for all users (jobseekers, employers, and admins).

### Features:
- Single login/register interface
- Automatic redirection based on user type:
  - Jobseekers → http://localhost:3001
  - Employers → http://localhost:3002
  - Admins → http://localhost:3003

### How to Use:

1. Open `index.html` in your browser
2. Or serve it with a simple HTTP server:
   ```bash
   cd landing
   python -m http.server 8080
   ```
3. Access at: http://localhost:8080

### User Flow:
1. User registers/logs in on landing page
2. System checks user_type from backend
3. Redirects to appropriate application
4. Token is passed via URL parameter
