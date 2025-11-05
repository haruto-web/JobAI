import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import api from './utils/axios';
import Navigation from './components/Navigation';
import Loading from './components/Loading';
import Login from './components/Login';
import Register from './components/Register';
import Home from './pages/Home';
import About from './pages/About';
import Jobs from './pages/Jobs';
import JobDetails from './pages/JobDetails';
import UserProfile from './pages/UserProfile';
import Account from './pages/Account';
import Dashboard from './pages/Dashboard';
import AdminDashboard from './pages/AdminDashboard';
import ChatBot from './components/ChatBot';
import './App.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [chatOpen, setChatOpen] = useState(false);

  const handleLogin = async (email, password) => {
    try {
      const response = await api.post('/login', { email, password });
      localStorage.setItem('token', response.data.token);
      setUser(response.data.user);
      setIsLoggedIn(true);

      // Redirect admin users to admin dashboard
      if (response.data.user.user_type === 'admin') {
        window.location.href = '/admin';
      }
    } catch (error) {
      console.error('Login failed:', error);
      setIsLoggedIn(false);
      setUser(null);
    }
  };

  const handleRegister = async (userData) => {
    try {
      const response = await api.post('/register', userData);
      localStorage.setItem('token', response.data.token);
      setUser(response.data.user);
      setIsLoggedIn(true);
    } catch (error) {
      console.error('Registration failed:', error);
      if (error.response && error.response.data && error.response.data.errors) {
        throw error;
      } else {
        throw new Error('Registration failed. Please check your inputs.');
      }
    }
  };

  const handleLogout = async () => {
    try {
      await api.post('/logout');
      localStorage.removeItem('token');
      setIsLoggedIn(false);
      setUser(null);
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  useEffect(() => {
    const checkAuth = async () => {
      const token = localStorage.getItem('token');
      if (!token) {
        setLoading(false);
        setIsLoggedIn(false);
        setUser(null);
        return;
      }
      try {
        const response = await api.get('/user');
        setUser(response.data);
        setIsLoggedIn(true);
      } catch (error) {
        if (error.response && error.response.status === 401) {
          console.error('Unauthorized access - redirecting to login');
          setIsLoggedIn(false);
          setUser(null);
          if (window.location.pathname !== '/login') {
            window.location.href = '/login';
          }
        } else {
          console.error('Error fetching user data:', error);
        }
        setIsLoggedIn(false);
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    // Check for Google OAuth callback
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    if (token) {
      localStorage.setItem('token', token);
      window.history.replaceState({}, document.title, window.location.pathname);
      checkAuth();
    } else {
      checkAuth();
    }
  }, []);

  if (loading) {
    return <Loading />;
  }

  return (
    <Router>
      <Navigation
        isLoggedIn={isLoggedIn}
        onLogin={handleLogin}
        onRegister={handleRegister}
        onLogout={handleLogout}
        userType={user?.user_type}
      />
      <div style={{ marginTop: '0' }}>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/about" element={<About />} />
          <Route path="/login" element={isLoggedIn ? <Navigate to={user?.user_type === 'admin' ? '/admin' : '/dashboard'} /> : <Login onLogin={handleLogin} />} />
          <Route path="/register" element={isLoggedIn ? <Navigate to={user?.user_type === 'admin' ? '/admin' : '/dashboard'} /> : <Register onRegister={handleRegister} />} />
          <Route path="/jobs" element={isLoggedIn && user?.user_type !== 'admin' ? <Jobs /> : <Navigate to="/login" />} />
          <Route path="/job/:id" element={isLoggedIn && user?.user_type !== 'admin' ? <JobDetails /> : <Navigate to="/login" />} />
          <Route path="/user/:id" element={isLoggedIn && user?.user_type !== 'admin' ? <UserProfile /> : <Navigate to="/login" />} />
          <Route path="/account" element={isLoggedIn ? <Account isLoggedIn={isLoggedIn} /> : <Navigate to="/login" />} />
          <Route path="/dashboard" element={isLoggedIn && user?.user_type !== 'admin' ? <Dashboard /> : <Navigate to="/login" />} />
          <Route path="/admin" element={isLoggedIn && user?.user_type === 'admin' ? <AdminDashboard /> : <Navigate to="/login" />} />
        </Routes>
      </div>
      {isLoggedIn && <ChatBot isOpen={chatOpen} onToggle={() => setChatOpen(!chatOpen)} />}
    </Router>
  );
}

export default App;
