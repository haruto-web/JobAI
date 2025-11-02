import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './Account.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function Account({ isLoggedIn }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [userType, setUserType] = useState('jobseeker');
  const [selectedFile, setSelectedFile] = useState(null);
  const [profileData, setProfileData] = useState({
    bio: '',
    skills: [],
    education_attainment: ''
  });
  const [editingProfile, setEditingProfile] = useState(false);
  // AI analysis and resume management moved to the Dashboard page

  useEffect(() => {
    if (isLoggedIn) {
      const fetchUser = async () => {
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`${API_URL}/user?t=${Date.now()}`, {
            headers: { Authorization: `Bearer ${token}` }
          });
          setUser(response.data);
          setUserType(response.data.user_type);
            if (response.data.profile) {
            setProfileData({
              bio: response.data.profile.bio || '',
              skills: response.data.profile.skills || [],
              education_attainment: response.data.profile.education_attainment || ''
            });
          }
        } catch (error) {
          console.error('Failed to fetch user:', error);
        } finally {
          setLoading(false);
        }
      };
      fetchUser();
    } else {
      setLoading(false);
    }
  }, [isLoggedIn]);

  // AI analysis is available on the Dashboard page for jobseekers

  const handleSaveUserType = async () => {
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/user`, { user_type: userType }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('User type updated successfully!');
    } catch (error) {
      console.error('Failed to update user type:', error);
      alert('Failed to update user type.');
    }
  };

  const handleImageUpload = async () => {
    if (!selectedFile) return;

    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('profile_image', selectedFile);

      const response = await axios.post(`${API_URL}/user/profile-image`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'multipart/form-data',
        },
      });
      setUser(response.data);
      setSelectedFile(null);
      // Auto-refresh the page to show the updated profile image
      window.location.reload();
    } catch (error) {
      console.error('Failed to upload image:', error);
      alert('Failed to upload profile image. Please try again.');
    }
  };

  const handleSaveProfile = async () => {
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/user/profile`, profileData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setUser({ ...user, profile: profileData });
      setEditingProfile(false);
      alert('Profile updated successfully!');
    } catch (error) {
      console.error('Failed to update profile:', error);
      alert('Failed to update profile. Please try again.');
    }
  };



  if (loading) {
    return <div>Loading profile...</div>;
  }

  if (!isLoggedIn) {
    return (
      <div className="account-container">
        <section className="account-hero">
          <h1>Please Log In</h1>
          <p>You need to be logged in to view your profile.</p>
        </section>
      </div>
    );
  }

  return (
    <div className="account-container">
      <section className="account-hero">
        <h1>Your Profile</h1>
        <p>Manage your account and preferences</p>
      </section>

      <section className="profile-content">
        <div className="profile-card">
          <div className="profile-header">
            <div className="profile-avatar">
              {user.profile_image ? (
                <img
                  src={`http://localhost:8000/storage/${user.profile_image}`}
                  alt="Profile"
                  style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%' }}
                />
              ) : (
                <span>{user && user.name ? user.name.charAt(0).toUpperCase() : '?'}</span>
              )}
            </div>
            <div className="profile-info">
              <h2>{user.name}</h2>
              <p>{user.email}</p>
            </div>
            <div className="profile-upload">
              <input
                type="file"
                accept="image/*"
                onChange={(e) => setSelectedFile(e.target.files[0])}
                id="profile-image-input"
                style={{ display: 'none' }}
              />
              <label htmlFor="profile-image-input" className="upload-btn">
                Choose File
              </label>
              <button onClick={handleImageUpload} disabled={!selectedFile} className="upload-btn">
                {selectedFile ? 'Upload Profile Image' : 'Select Image First'}
              </button>
              {selectedFile && (
                <p className="selected-file-text">Selected file: {selectedFile.name}</p>
              )}
            </div>
          </div>

          <div className="user-type-section">
            <h3>User Type</h3>
            <div className="user-type-toggle">
              <button
                className={`type-btn ${userType === 'jobseeker' ? 'active' : ''}`}
                onClick={() => setUserType('jobseeker')}
                type="button"
              >
                Job Seeker
              </button>
              <button
                className={`type-btn ${userType === 'employer' ? 'active' : ''}`}
                onClick={() => setUserType('employer')}
                type="button"
              >
                Employer
              </button>
            </div>
            <p className="user-type-desc">
              {userType === 'jobseeker'
                ? 'You are currently set as a job seeker. You can browse and apply for jobs.'
                : 'You are currently set as an employer. You can post jobs and find candidates.'
              }
            </p>
            <button onClick={handleSaveUserType} className="save-btn" type="button">
              Save Changes
            </button>
          </div>

          <div className="profile-details">
            <h3>Profile Details</h3>
            <div className="detail-item">
              <strong>Joined:</strong> {new Date(user.created_at).toLocaleDateString()}
            </div>
            {/* <div className="detail-item">
              <strong>Email Verified:</strong> {user.email_verified_at ? 'Yes' : 'No'}
            </div> */}
            <div className="detail-item">
              <strong>User Type:</strong> {userType === 'jobseeker' ? 'Job Seeker' : 'Employer'}
            </div>
          </div>

          {userType === 'jobseeker' && (
            <div className="jobseeker-info">
              <h3>Job Seeker Information</h3>
              <p>View your applications and earnings in the <a href="/dashboard">Dashboard</a>.</p>
              <div className="user-background">
                <h4>Your Background</h4>
                {editingProfile ? (
                  <div className="edit-profile-form">
                    <label>Bio:</label>
                    <textarea
                      value={profileData.bio}
                      onChange={(e) => setProfileData({ ...profileData, bio: e.target.value })}
                      placeholder="Tell us about yourself..."
                      rows="4"
                    />
                    <label>Skills (comma-separated):</label>
                    <input
                      type="text"
                      value={profileData.skills.join(', ')}
                      onChange={(e) => setProfileData({ ...profileData, skills: e.target.value.split(',').map(s => s.trim()) })}
                      placeholder="Skills that you have"
                    />
                    <label>Education Attainment:</label>
                    <select
                      value={profileData.education_attainment}
                      onChange={(e) => setProfileData({ ...profileData, education_attainment: e.target.value })}
                    >
                      <option value="">Select education level</option>
                      <option value="high_school">High School</option>
                      <option value="associate">Associate Degree</option>
                      <option value="bachelor">Bachelor's Degree</option>
                      <option value="master">Master's Degree</option>
                      <option value="phd">PhD</option>
                    </select>
                    <div className="form-buttons">
                      <button onClick={handleSaveProfile} className="save-btn">Save</button>
                      <button onClick={() => setEditingProfile(false)} className="cancel-btn">Cancel</button>
                    </div>
                  </div>
                ) : (
                  <div>
                    {user.profile ? (
                      <div>
                        {user.profile.bio && <p><strong>Bio:</strong> {user.profile.bio}</p>}
                        {user.profile.skills && user.profile.skills.length > 0 && (
                          <p><strong>Skills:</strong> {user.profile.skills.join(', ')}</p>
                        )}
                        {user.profile.education_attainment && <p><strong>Education Attainment:</strong> {user.profile.education_attainment}</p>}



                      </div>
                    ) : (
                      <p>No profile information available. Update your profile to improve job matches.</p>
                    )}
                    <button onClick={() => setEditingProfile(true)} className="edit-profile-btn">Edit Profile</button>
                  </div>
                )}
              </div>
            </div>
          )}

          {userType === 'employer' && (
            <div className="employer-info">
              <h3>Employer Information</h3>
              <p>Manage your jobs and applications in the <a href="/dashboard">Dashboard</a>.</p>
            </div>
          )}
        </div>
      </section>

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation</p>
        <p>Helping you connect with the right opportunities</p>
      </footer>
    </div>
  );
}

export default Account;
