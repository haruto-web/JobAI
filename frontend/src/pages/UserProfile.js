import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import './UserProfile.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function UserProfile() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchUser = async () => {
      try {
        const response = await axios.get(`${API_URL}/users/${id}`);
        setUser(response.data);
      } catch (error) {
        console.error('Failed to fetch user:', error);
        navigate('/jobs');
      }
    };

    const fetchJobs = async () => {
      try {
        const response = await axios.get(`${API_URL}/jobs`);
        setJobs(response.data.filter(job => job.user_id === parseInt(id)));
      } catch (error) {
        console.error('Failed to fetch jobs:', error);
      }
    };

    fetchUser();
    fetchJobs();
    setLoading(false);
  }, [id, navigate]);

  if (loading) {
    return <div className="user-profile-loading">Loading user profile...</div>;
  }

  if (!user) {
    return <div className="user-profile-error">User not found.</div>;
  }

  return (
    <div className="user-profile-container">
      <div className="user-profile-header">
        <button onClick={() => navigate('/jobs')} className="back-button">
          ← Back to Jobs
        </button>
        <h1>{user.name}'s Profile</h1>
      </div>

      <div className="user-profile-content">
        {user.user_type === 'jobseeker' && (
          <>
            <div className="profile-image">
              {user.profile?.image ? (
                <img src={user.profile.image} alt={`${user.name}'s profile`} />
              ) : (
                <div className="default-avatar">{user.name.charAt(0).toUpperCase()}</div>
              )}
            </div>
            <div className="profile-details">
              <h2>Profile Details</h2>
              <p><strong>Joined:</strong> {new Date(user.created_at).toLocaleDateString()}</p>
              <p><strong>User Type:</strong> Job Seeker</p>
              <h3>Job Seeker Information</h3>
              <p>This user is actively seeking job opportunities.</p>
              <h3>Your Background</h3>
              {user.profile?.bio && <p><strong>Bio:</strong> {user.profile.bio}</p>}
              {user.profile?.skills && user.profile.skills.length > 0 && (
                <p><strong>Skills:</strong> {user.profile.skills.join(', ')}</p>
              )}
              {user.profile?.experience_level && (
                <p><strong>Experience:</strong> {user.profile.experience_level}</p>
              )}
            </div>
          </>
        )}

        {user.user_type === 'employer' && (
          <>
            <div className="profile-details">
              <h2>Employer Profile</h2>
              <p><strong>Name:</strong> {user.name}</p>
              <p><strong>Email:</strong> {user.email}</p>
              <p><strong>User Type:</strong> Employer</p>
              <p><strong>Joined:</strong> {new Date(user.created_at).toLocaleDateString()}</p>
            </div>
            <div className="employer-jobs">
              <h3>Jobs Created by {user.name}</h3>
              {jobs.length > 0 ? (
                <div className="job-list">
                  {jobs.map(job => (
                    <div key={job.id} className="job-item" onClick={() => navigate(`/job/${job.id}`)} style={{ cursor: 'pointer' }}>
                      <h4>{job.title}</h4>
                      <p>{job.company} - {job.location}</p>
                      <p>{job.description?.substring(0, 100)}...</p>
                    </div>
                  ))}
                </div>
              ) : (
                <p>No jobs posted yet.</p>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default UserProfile;
