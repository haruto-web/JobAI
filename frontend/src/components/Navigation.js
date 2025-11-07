import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import NotificationBell from './NotificationBell';
import './Navigation.css';

function Navigation({ isLoggedIn, onLogin, onLogout, userType }) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isAdminDropdownOpen, setIsAdminDropdownOpen] = useState(false); // New state for admin dropdown
  const location = useLocation();

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
  };

  const closeMobileMenu = () => {
    setIsMobileMenuOpen(false);
    setIsAdminDropdownOpen(false); // Close dropdown when mobile menu closes
  };

  const toggleAdminDropdown = () => {
    setIsAdminDropdownOpen(!isAdminDropdownOpen);
  };

  const isActive = (path) => {
    return location.pathname === path;
  };

  return (
    <nav className="navigation">
      <div className="nav-container">
        {/* Logo/Brand */}
        <div className="nav-brand">
          <Link to="/" className="brand-link" onClick={closeMobileMenu}>
            <div className="brand-icon">J</div>
            <span className="brand-text">JobAI</span>
          </Link>
        </div>

        {/* Desktop Navigation */}
        <div className="nav-links">
          <Link 
            to="/" 
            className={`nav-link ${isActive('/') ? 'active' : ''}`}
          >
            Home
          </Link>
          <Link 
            to="/about" 
            className={`nav-link ${isActive('/about') ? 'active' : ''}`}
          >
            About
          </Link>
          
          {isLoggedIn && userType !== 'admin' && (
            <>
              <Link 
                to="/jobs" 
                className={`nav-link ${isActive('/jobs') ? 'active' : ''}`}
              >
                Jobs
              </Link>
              <Link
                to="/dashboard"
                className={`nav-link ${isActive('/dashboard') ? 'active' : ''}`}
              >
                Dashboard
              </Link>
              <Link 
                to="/account" 
                className={`nav-link ${isActive('/account') ? 'active' : ''}`}
              >
                Account
              </Link>
            </>
          )}

          {isLoggedIn && userType === 'admin' && (
            <>
              <Link
                to="/admin"
                className={`nav-link ${isActive('/admin') ? 'active' : ''}`}
              >
                Admin Dashboard
              </Link>
              <Link 
                to="/account" 
                className={`nav-link ${isActive('/account') ? 'active' : ''}`}
              >
                Account
              </Link>
            </>
          )}
        </div>

        {/* Auth Section */}
        <div className="nav-auth">
          {isLoggedIn ? (
            <div className="user-menu">
              <NotificationBell isLoggedIn={isLoggedIn} />
              <button onClick={onLogout} className="logout-btn">
                Sign Out
              </button>
            </div>
          ) : (
            <div className="auth-buttons">
              <Link to="/login" className="login-btn">
                Sign In
              </Link>
              <Link to="/register" className="register-btn">
                Get Started
              </Link>
            </div>
          )}
        </div>

        {/* Mobile Menu Button */}
        <button 
          className="mobile-menu-btn"
          onClick={toggleMobileMenu}
          aria-label="Toggle mobile menu"
        >
          <span className={`hamburger ${isMobileMenuOpen ? 'open' : ''}`}>
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>
      </div>

      {/* Mobile Menu */}
      <div className={`mobile-menu ${isMobileMenuOpen ? 'open' : ''}`}>
        <div className="mobile-menu-content">
          <Link 
            to="/" 
            className={`mobile-nav-link ${isActive('/') ? 'active' : ''}`}
            onClick={closeMobileMenu}
          >
            Home
          </Link>
          <Link 
            to="/about" 
            className={`mobile-nav-link ${isActive('/about') ? 'active' : ''}`}
            onClick={closeMobileMenu}
          >
            About
          </Link>
          
          {isLoggedIn && userType !== 'admin' && (
            <>
              <Link 
                to="/jobs" 
                className={`mobile-nav-link ${isActive('/jobs') ? 'active' : ''}`}
                onClick={closeMobileMenu}
              >
                Jobs
              </Link>
              <Link
                to="/dashboard"
                className={`mobile-nav-link ${isActive('/dashboard') ? 'active' : ''}`}
                onClick={closeMobileMenu}
              >
                Dashboard
              </Link>
              <Link 
                to="/account" 
                className={`mobile-nav-link ${isActive('/account') ? 'active' : ''}`}
                onClick={closeMobileMenu}
              >
                Account
              </Link>
              <button onClick={() => { onLogout(); closeMobileMenu(); }} className="mobile-logout-btn">
                Sign Out
              </button>
            </>
          )}

          {isLoggedIn && userType === 'admin' && (
            <>
              <Link
                to="/admin"
                className={`mobile-nav-link ${isActive('/admin') ? 'active' : ''}`}
                onClick={closeMobileMenu}
              >
                Admin Dashboard
              </Link>
              <Link 
                to="/account" 
                className={`mobile-nav-link ${isActive('/account') ? 'active' : ''}`}
                onClick={closeMobileMenu}
              >
                Account
              </Link>
              <button onClick={() => { onLogout(); closeMobileMenu(); }} className="mobile-logout-btn">
                Sign Out
              </button>
            </>
          )}

          {!isLoggedIn && (
            <>
              <Link 
                to="/login" 
                className="mobile-auth-link"
                onClick={closeMobileMenu}
              >
                Sign In
              </Link>
              <Link 
                to="/register" 
                className="mobile-auth-link primary"
                onClick={closeMobileMenu}
              >
                Get Started
              </Link>
            </>
          )}
        </div>
      </div>
    </nav>
  );
}

export default Navigation;
