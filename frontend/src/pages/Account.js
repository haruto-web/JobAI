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
    experience_level: '',
    years_of_experience: '',
    education_attainment: ''
  });
  const [editingProfile, setEditingProfile] = useState(false);
  const [showChangePassword, setShowChangePassword] = useState(false);
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  });
  const [passwordMessage, setPasswordMessage] = useState('');
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
              experience_level: response.data.profile.experience_level || '',
              years_of_experience: response.data.profile.years_of_experience || '',
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
      console.error('Error response:', error.response?.data);
      const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Failed to upload profile image';
      alert(errorMsg);
    }
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();
    setPasswordMessage('');

    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/user/change-password`, passwordData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setPasswordMessage('Password changed successfully!');
      setPasswordData({ current_password: '', new_password: '', new_password_confirmation: '' });
      setTimeout(() => {
        setShowChangePassword(false);
        setPasswordMessage('');
      }, 2000);
    } catch (error) {
      const errorMsg = error.response?.data?.message || 'Failed to change password';
      setPasswordMessage(errorMsg);
    }
  };

  const handleSaveProfile = async () => {
    try {
      const token = localStorage.getItem('token');
      const dataToSend = {};
      
      if (profileData.bio && profileData.bio.trim()) dataToSend.bio = profileData.bio;
      if (profileData.skills && profileData.skills.length > 0) {
        dataToSend.skills = profileData.skills.filter(s => s.trim() !== '');
      }
      if (profileData.experience_level && profileData.experience_level.trim()) dataToSend.experience_level = profileData.experience_level;
      if (profileData.years_of_experience && profileData.years_of_experience !== '') dataToSend.years_of_experience = parseInt(profileData.years_of_experience);
      if (profileData.education_attainment && profileData.education_attainment.trim()) dataToSend.education_attainment = profileData.education_attainment;
      
      await axios.put(`${API_URL}/user/profile`, dataToSend, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setUser({ ...user, profile: profileData });
      setEditingProfile(false);
      alert('Profile updated successfully!');
    } catch (error) {
      console.error('Failed to update profile:', error);
      const errorMsg = error.response?.data?.errors 
        ? Object.values(error.response.data.errors).flat().join('\n')
        : error.response?.data?.message || 'Failed to update profile. Please try again.';
      alert(errorMsg);
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
                  src={user.profile_image.startsWith('http') ? user.profile_image : `${API_URL.replace('/api', '')}/storage/${user.profile_image}`}
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
                    <label>Experience Level:</label>
                    <select
                      value={profileData.experience_level}
                      onChange={(e) => setProfileData({ ...profileData, experience_level: e.target.value })}
                    >
                      <option value="">Select experience level</option>
                      <option value="entry_level">Entry Level</option>
                      <option value="beginner">Beginner</option>
                      <option value="intermediate">Intermediate</option>
                      <option value="experienced">Experienced</option>
                      <option value="expert_senior">Expert / Senior</option>
                    </select>
                    <label>Years of Experience:</label>
                    <input
                      type="number"
                      value={profileData.years_of_experience}
                      onChange={(e) => setProfileData({ ...profileData, years_of_experience: e.target.value })}
                      placeholder="Years of experience"
                      min="0"
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
                        {user.profile.experience_level && <p><strong>Experience Level:</strong> {user.profile.experience_level}</p>}
                        {user.profile.years_of_experience && <p><strong>Years of Experience:</strong> {user.profile.years_of_experience}</p>}
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

          <div className="password-section">
            <h3>Security</h3>
            <button onClick={() => setShowChangePassword(true)} className="change-password-btn">
              Change Password
            </button>
          </div>
        </div>
      </section>

      {showChangePassword && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3>Change Password</h3>
              <button onClick={() => setShowChangePassword(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleChangePassword}>
              <div className="form-group">
                <label>Current Password</label>
                <input
                  type="password"
                  value={passwordData.current_password}
                  onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                  required
                  placeholder="Enter current password"
                />
              </div>
              <div className="form-group">
                <label>New Password</label>
                <input
                  type="password"
                  value={passwordData.new_password}
                  onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
                  required
                  minLength="8"
                  placeholder="Enter new password (min 8 characters)"
                />
              </div>
              <div className="form-group">
                <label>Confirm New Password</label>
                <input
                  type="password"
                  value={passwordData.new_password_confirmation}
                  onChange={(e) => setPasswordData({ ...passwordData, new_password_confirmation: e.target.value })}
                  required
                  placeholder="Confirm new password"
                />
              </div>
              {passwordMessage && (
                <div className={`message ${passwordMessage.includes('success') ? 'success' : 'error'}`}>
                  {passwordMessage}
                </div>
              )}
              <div className="modal-actions">
                <button type="button" onClick={() => setShowChangePassword(false)} className="cancel-btn">
                  Cancel
                </button>
                <button type="submit" className="save-btn">
                  Change Password
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation</p>
        <p>Helping you connect with the right opportunities</p>
      </footer>
    </div>
  );
}

export default Account;
