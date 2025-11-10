import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import SearchBar from '../components/SearchBar';
import './Home.css';

function Home() {
  const [urgentJobs, setUrgentJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [applying, setApplying] = useState(null);
  const [user, setUser] = useState(null);

  const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

  useEffect(() => {
    const fetchUrgentJobs = async () => {
      try {
        const response = await axios.get(`${API_URL}/urgent-jobs`);
        setUrgentJobs(response.data);
      } catch (error) {
        console.error('Failed to fetch urgent jobs:', error);
      } finally {
        setLoading(false);
      }
    };

    const fetchUser = async () => {
      const token = localStorage.getItem('token');
      if (!token) return; // Don't make API call if no token
      
      try {
        const response = await axios.get(`${API_URL}/user`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        setUser(response.data);
      } catch (error) {
        // Clear invalid token on 401
        if (error.response?.status === 401) {
          localStorage.removeItem('token');
        } else {
          console.error('Failed to fetch user:', error);
        }
      }
    };

    fetchUrgentJobs();
    fetchUser();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleApply = async (jobId) => {
    if (!user || user.user_type !== 'jobseeker') {
      alert('Only job seekers can apply for jobs.');
      return;
    }

    // Check if user has a resume before applying
    const hasResume = user.profile && (user.profile.resumes?.length > 0 || user.profile.resume_url);
    if (!hasResume) {
      alert('Please upload a resume before applying for jobs.');
      return;
    }

    setApplying(jobId);
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/applications`, { job_id: jobId }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Application submitted successfully!');
      // Remove the job from urgent jobs list after successful application
      setUrgentJobs(prev => prev.filter(job => job.id !== jobId));
    } catch (error) {
      console.error('Failed to apply:', error);
      if (error.response && error.response.status === 409) {
        alert('You have already applied for this job.');
      } else {
        alert('Failed to apply for the job. Please try again.');
      }
    } finally {
      setApplying(null);
    }
  };

  return (
    <div className="home-container">
      {/* Hero Section */}
      <section className="hero-section">
        <div className="hero-background">
          <div className="hero-gradient"></div>
          <div className="hero-pattern"></div>
        </div>
        <div className="hero-content">
          <div className="hero-text">
            <h1 className="hero-title">
              Find Your Dream Job with<br />
              <span className="text-gradient">AI-Powered</span> Recommendations
            </h1>
            <p className="hero-subtitle">
              Connect with opportunities that match your skills, experience, and career goals. Our intelligent platform uses advanced AI to help you discover the perfect role.
            </p>
            <div className="hero-actions">
              <Link to="/register" className="btn-primary">
                Get Started
              </Link>
              <Link to="/about" className="btn-secondary">
                Learn More
              </Link>
            </div>
          </div>
          <div className="hero-visual">
            <div className="floating-card card-1">
              <div className="card-icon">⚡</div>
              <div className="card-text">Smart Matching</div>
            </div>
            <div className="floating-card card-2">
              <div className="card-icon">🎯</div>
              <div className="card-text">AI Analysis</div>
            </div>
            <div className="floating-card card-3">
              <div className="card-icon">📊</div>
              <div className="card-text">Career Growth</div>
            </div>
          </div>
        </div>
      </section>

      {/* Search Section */}
      <section className="search-section">
        <div className="container">
          <div className="section-header">
            <h2>Search Jobs & Users</h2>
            <p>Find the perfect job or connect with the right people.</p>
          </div>
          <SearchBar />
        </div>
      </section>

      {/* Features Section */}
      <section className="features-section">
        <div className="container">
          <div className="section-header">
            <h2>Why Choose Our Platform?</h2>
            <p>Experience the future of job searching with intelligent matching and personalized recommendations.</p>
          </div>
          
          <div className="features-grid">
            <div className="feature-card">
              <div className="feature-icon">🎯</div>
              <h3>Smart Matching</h3>
              <p>Our AI analyzes your profile and matches you with the most relevant job opportunities based on your skills and preferences.</p>
            </div>
            
            <div className="feature-card">
              <div className="feature-icon">⚡</div>
              <h3>Quick Applications</h3>
              <p>Apply to multiple jobs with just one click. Our streamlined process saves you time and effort.</p>
            </div>
            
            <div className="feature-card">
              <div className="feature-icon">📊</div>
              <h3>Career Insights</h3>
              <p>Get detailed analytics about your job search progress and receive personalized career advice.</p>
            </div>
            
            <div className="feature-card">
              <div className="feature-icon">🔒</div>
              <h3>Secure & Private</h3>
              <p>Your data is protected with enterprise-grade security. We respect your privacy and keep your information safe.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Urgent Jobs Section */}
      {!loading && urgentJobs.length > 0 && (
        <section className="urgent-jobs-section">
          <div className="container">
            <div className="section-header">
              <h2>🚨 Urgent Job Openings</h2>
              <p>These positions need immediate attention! Apply now to secure your spot.</p>
            </div>

            <div className="urgent-jobs-grid">
              {urgentJobs.map(job => (
                <div key={job.id} className="urgent-job-card">
                  <div className="urgent-badge">URGENT</div>
                  <h3>{job.title}</h3>
                  <p className="company">{job.company} - {job.location}</p>
                  <p className="type">{job.type}</p>
                  {job.salary && <p className="salary">${job.salary}</p>}
                  <p className="description">{job.description.substring(0, 150)}...</p>
                  {user && user.user_type === 'jobseeker' && (
                    <button
                      onClick={() => handleApply(job.id)}
                      disabled={applying === job.id}
                      className="apply-urgent-btn"
                    >
                      {applying === job.id ? 'Applying...' : 'Apply Now'}
                    </button>
                  )}
                  {!user && (
                    <Link to="/login" className="login-to-apply-btn">
                      Login to Apply
                    </Link>
                  )}
                  {user && user.user_type === 'employer' && (
                    <p className="employer-note">Only job seekers can apply for jobs.</p>
                  )}
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* CTA Section */}
      <section className="cta-section">
        <div className="container">
          <div className="cta-content">
            <h2>Ready to Transform Your Career?</h2>
            <p>Join thousands of professionals who have found their dream jobs through our platform.</p>
            <div className="cta-actions">
              <Link to="/register" className="btn-primary large">
                Start Your Journey
              </Link>
              <Link to="/jobs" className="btn-outline large">
                Browse Jobs
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer">
        <div className="container">
          <div className="footer-content">
            <div className="footer-brand">
            <div className="footer-logo">
              <span className="footer-icon">J</span>
              <span className="footer-text">JobAI</span>
            </div>
              <p>Connecting talent with opportunity through intelligent matching.</p>
            </div>
            <div className="footer-links">
              <div className="footer-column">
                <h4>Platform</h4>
                <Link to="/jobs">Find Jobs</Link>
                <Link to="/dashboard">Dashboard</Link>
                <Link to="/account">Account</Link>
              </div>
              <div className="footer-column">
                <h4>Company</h4>
                <Link to="/about">About Us</Link>
                <Link to="/contact">Contact</Link>
                <Link to="/careers">Careers</Link>
              </div>
              <div className="footer-column">
                <h4>Support</h4>
                <Link to="/help">Help Center</Link>
                <Link to="/privacy">Privacy Policy</Link>
                <Link to="/terms">Terms of Service</Link>
              </div>
            </div>
          </div>
          <div className="footer-bottom">
            <p>&copy; {new Date().getFullYear()} JobAI. All rights reserved.</p>
            <p>Helping you connect with the right opportunities</p>
          </div>
        </div>
      </footer>
    </div>
  );
}

export default Home;
