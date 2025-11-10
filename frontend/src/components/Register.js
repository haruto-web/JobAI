import React, { useState, useEffect } from 'react';
import './Auth.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function Register({ onRegister }) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    user_type: 'jobseeker'
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [avatarPreview, setAvatarPreview] = useState('');
  const [oauthProvider, setOauthProvider] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await onRegister(formData);
    } catch (err) {
      if (err.response && err.response.data && err.response.data.errors) {
        const errors = Object.values(err.response.data.errors).flat();
        const errorMessage = errors.join(' ');
        if (errorMessage.toLowerCase().includes('email') && (errorMessage.toLowerCase().includes('taken') || errorMessage.toLowerCase().includes('exists') || errorMessage.toLowerCase().includes('already'))) {
          setError('This email is already registered. Please sign in or use a different email.');
        } else {
          setError(errorMessage);
        }
      } else if (err.response && err.response.data && err.response.data.message) {
        setError(err.response.data.message);
      } else {
        setError('Registration failed. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  // Read query params and prefill form data (email, name, avatar, provider)
  useEffect(() => {
    try {
      const params = new URLSearchParams(window.location.search);
      const email = params.get('email');
      const name = params.get('name');
      const avatar = params.get('avatar');
      const provider = params.get('provider');

      // If coming from OAuth, generate a random password
      const randomPassword = provider ? Math.random().toString(36).slice(-12) : '';

      setFormData(prev => ({
        ...prev,
        ...(name ? { name } : {}),
        ...(email ? { email } : {}),
        ...(provider ? { 
          password: randomPassword,
          password_confirmation: randomPassword
        } : {})
      }));

      if (avatar) {
        setAvatarPreview(avatar);
      }

      if (provider) {
        setOauthProvider(provider);
      }
    } catch (err) {
      // ignore malformed URLs
    }
  }, []);

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h2>Create Account</h2>
          <p>Join JobAI to find your dream job</p>
        </div>

        <form onSubmit={handleSubmit} className="auth-form">
          {error && <div className="error-message">{error}</div>}

          {avatarPreview && (
            <div className="form-group avatar-preview">
              <img src={avatarPreview} alt="avatar preview" style={{ width: 80, height: 80, borderRadius: '50%' }} />
            </div>
          )}

          {oauthProvider && (
            <div className="oauth-note">
              Signing up with <strong>{oauthProvider}</strong>. You can complete your account details below.
            </div>
          )}

          <div className="oauth-buttons">
            <button
              type="button"
              className="auth-button oauth google"
              aria-label="Continue with Google"
              onClick={() => {
                // Open OAuth flow in a popup and listen for the result via postMessage
                const oauthUrl = `${API_URL.replace('/api', '')}/auth/google`;
                const width = 520;
                const height = 600;
                const left = window.screenX + (window.outerWidth - width) / 2;
                const top = window.screenY + (window.outerHeight - height) / 2;
                const popup = window.open(oauthUrl, 'oauth_popup', `width=${width},height=${height},left=${left},top=${top}`);

                function handleMessage(e) {
                  try {
                    const { type, payload } = e.data || {};
                    if (type === 'oauth_callback' && payload) {
                      const { token, email } = payload;
                      if (token) {
                        localStorage.setItem('token', token);
                        // reload or navigate to dashboard
                        window.location.href = '/dashboard';
                      } else if (email) {
                        // navigate to register with prefilled params
                        const qs = new URLSearchParams(payload).toString();
                        window.location.href = `/register?${qs}`;
                      }
                      window.removeEventListener('message', handleMessage);
                      if (popup && !popup.closed) popup.close();
                    }
                  } catch (err) {
                    // ignore
                  }
                }

                window.addEventListener('message', handleMessage);
              }}
            >
              <span className="google-logo" aria-hidden="true">
                {/* Google G logo (inline SVG) */}
                <svg viewBox="0 0 533.5 544.3" xmlns="http://www.w3.org/2000/svg" focusable="false" width="18" height="18">
                  <path fill="#4285F4" d="M533.5 278.4c0-18.6-1.5-37.1-4.7-54.8H272.1v103.7h147.1c-6.3 33.9-25.6 62.6-54.6 81.8v67h88.2c51.6-47.5 81.7-117.6 81.7-197.7z"/>
                  <path fill="#34A853" d="M272.1 544.3c73.7 0 135.7-24.4 181-66.3l-88.2-67c-24.5 16.4-56 26-92.8 26-71 0-131.2-47.8-152.7-112.1H28.7v70.6C74.2 491 166.5 544.3 272.1 544.3z"/>
                  <path fill="#FBBC05" d="M119.4 325.5c-10.7-31.9-10.7-66.5 0-98.4V156.5H28.7c-39.8 78.6-39.8 172.7 0 251.3l90.7-82.3z"/>
                  <path fill="#EA4335" d="M272.1 108.6c39.9-.6 78.6 14.2 108 40.8l81-81C411 23.6 344.1 0 272.1 0 166.5 0 74.2 53.3 28.7 131.8l90.7 70.6c21.5-64.3 81.7-112.1 152.7-112.1z"/>
                </svg>
              </span>
              <span className="google-text">Continue with Google</span>
            </button>
          </div>

          <div className="form-group">
            <label htmlFor="name">Full Name</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              required
              placeholder="Enter your full name"
              autoComplete="name"
            />
          </div>

          <div className="form-group">
            <label htmlFor="email">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
              placeholder="Enter your email"
              autoComplete="email"
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Password</label>
            <div className="password-input-container">
              <input
                type={showPassword ? 'text' : 'password'}
                id="password"
                name="password"
                value={formData.password}
                onChange={handleChange}
                required
                minLength="8"
                placeholder="Create a password"
                autoComplete="new-password"
              />
              <button
                type="button"
                className="password-toggle"
                onClick={() => setShowPassword(!showPassword)}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? 'Hide' : 'Show'}
              </button>
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="password_confirmation">Confirm Password</label>
            <div className="password-input-container">
              <input
                type={showConfirmPassword ? 'text' : 'password'}
                id="password_confirmation"
                name="password_confirmation"
                value={formData.password_confirmation}
                onChange={handleChange}
                required
                placeholder="Confirm your password"
                autoComplete="new-password"
              />
              <button
                type="button"
                className="password-toggle"
                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                aria-label={showConfirmPassword ? 'Hide password' : 'Show password'}
              >
                {showConfirmPassword ? 'Hide' : 'Show'}
              </button>
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="user_type">I am a:</label>
            <select
              id="user_type"
              name="user_type"
              value={formData.user_type}
              onChange={handleChange}
              required
            >
              <option value="jobseeker">Job Seeker</option>
              <option value="employer">Employer</option>
            </select>
          </div>

          <button
            type="submit"
            className="auth-button primary"
            disabled={loading}
          >
            {loading ? 'Creating Account...' : 'Create Account'}
          </button>
        </form>

        <div className="auth-footer">
          <p>Already have an account? <a href="/login">Sign in</a></p>
        </div>
      </div>
    </div>
  );
}

export default Register;
