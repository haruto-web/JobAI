import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from 'axios';
import './JobDetails.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function JobDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [job, setJob] = useState(null);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [applying, setApplying] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);

  useEffect(() => {
    const fetchJob = async () => {
      try {
        const response = await axios.get(`${API_URL}/jobs/${id}`);
        setJob(response.data);
      } catch (error) {
        console.error('Failed to fetch job:', error);
        navigate('/jobs');
      } finally {
        setLoading(false);
      }
    };

    const fetchUser = async () => {
      try {
        const token = localStorage.getItem('token');
        if (token) {
          const response = await axios.get(`${API_URL}/user`, {
            headers: { Authorization: `Bearer ${token}` }
          });
          setUser(response.data);
        }
      } catch (error) {
        console.error('Failed to fetch user:', error);
      }
    };

    fetchJob();
    fetchUser();
  }, [id, navigate]);

  const handleApply = async () => {
    if (!selectedFile) {
      alert('Please select a resume file before applying.');
      return;
    }

    setApplying(true);
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('job_id', id);
      formData.append('resume', selectedFile);

      await axios.post(`${API_URL}/applications`, formData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setSelectedFile(null);
      alert('Application submitted successfully!');
      navigate('/dashboard');
    } catch (error) {
      console.error('Failed to apply:', error);
      if (error.response && error.response.status === 409) {
        alert('You have already applied for this job.');
      } else {
        alert('Failed to apply for the job. Please try again.');
      }
    } finally {
      setApplying(false);
    }
  };

  if (loading) {
    return <div className="job-details-loading">Loading job details...</div>;
  }

  if (!job) {
    return <div className="job-details-error">Job not found.</div>;
  }

  return (
    <div className="job-details-container">
      <div className="job-details-header">
        <button onClick={() => navigate('/jobs')} className="back-button">
          ← Back to Jobs
        </button>
        <h1>{job.title}</h1>
        <div className="job-meta">
          <span className="company">{job.company}</span>
          <span className="location">{job.location}</span>
          <span className="type">{job.type}</span>
          {job.salary && <span className="salary">${job.salary}</span>}
        </div>
      </div>

      <div className="job-details-content">
        <div className="job-section">
          <h2>Job Description</h2>
          <p>{job.description}</p>
        </div>

        {job.summary && (
          <div className="job-section">
            <h2>Summary</h2>
            <p>{job.summary}</p>
          </div>
        )}

        {job.qualifications && (
          <div className="job-section">
            <h2>Qualifications</h2>
            <p>{job.qualifications}</p>
          </div>
        )}

        {job.requirements && job.requirements.length > 0 && (
          <div className="job-section">
            <h2>Requirements</h2>
            <ul>
              {job.requirements.map((req, index) => (
                <li key={index}>{req}</li>
              ))}
            </ul>
          </div>
        )}

        {user && user.user_type === 'jobseeker' && (
          <div className="job-apply-section">
            <h2>Apply for this Job</h2>
            <div className="apply-form">
              <input
                type="file"
                accept=".pdf,.doc,.docx"
                onChange={(e) => setSelectedFile(e.target.files[0])}
                className="resume-input"
              />
              <button
                onClick={handleApply}
                disabled={applying || !selectedFile}
                className="apply-button"
              >
                {applying ? 'Applying...' : 'Apply with Resume'}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default JobDetails;
