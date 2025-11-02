import React, { useState, useEffect } from 'react';
import axios from 'axios';
import SearchBar from '../components/SearchBar';
import './Jobs.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function Jobs() {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [applying, setApplying] = useState(null);
  const [user, setUser] = useState(null);
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploading, setUploading] = useState(false);

  const [replacingIndex, setReplacingIndex] = useState(null);
  const [replacingFile, setReplacingFile] = useState(null);

  // Job creation states for employers
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [jobForm, setJobForm] = useState({
    title: '',
    company: '',
    location: '',
    type: 'full-time',
    salary: '',
    description: '',
  });
  const [creating, setCreating] = useState(false);

  useEffect(() => {
    const fetchJobs = async () => {
      try {
        const response = await axios.get(`${API_URL}/jobs`);
        setJobs(response.data);
      } catch (error) {
        console.error('Failed to fetch jobs:', error);
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

    fetchJobs();
    fetchUser();
  }, []);

  const handleApply = async (jobId) => {
    // Check if resume is selected
    if (!selectedFile) {
      alert('Please select a resume file before applying for jobs.');
      return;
    }

    setApplying(jobId);
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('job_id', jobId);
      formData.append('resume', selectedFile);

      await axios.post(`${API_URL}/applications`, formData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setSelectedFile(null);
      alert('Application submitted successfully with your resume!');
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

  const handleAddResume = async () => {
    if (!selectedFile) return;

    setUploading(true);
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('resume', selectedFile);
      formData.append('action', 'add');

      const response = await axios.post(`${API_URL}/user/resume`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setUser(response.data);
      setSelectedFile(null);
      alert('Resume added successfully!');
      // Redirect to Account page after successful upload
      window.location.href = '/account';
    } catch (error) {
      console.error('Failed to add resume:', error);
      alert('Failed to add resume. Please try again.');
    } finally {
      setUploading(false);
    }
  };



  const handleReplaceResume = async (index) => {
    if (!replacingFile) return;

    setUploading(true);
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('action', 'replace');
      formData.append('index', index);
      formData.append('resume', replacingFile);

      const response = await axios.post(`${API_URL}/user/resume`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setUser(response.data);
      setReplacingIndex(null);
      setReplacingFile(null);
      alert('Resume replaced successfully!');
    } catch (error) {
      console.error('Failed to replace resume:', error);
      alert('Failed to replace resume. Please try again.');
    } finally {
      setUploading(false);
    }
  };

  const handleDeleteResume = async (index) => {
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('index', index);

      const response = await axios.post(`${API_URL}/user/resume`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setUser(response.data);
      alert('Resume deleted successfully!');
    } catch (error) {
      console.error('Failed to delete resume:', error);
      alert('Failed to delete resume. Please try again.');
    }
  };

  const handleCreateJob = async () => {
    if (!jobForm.title.trim() || !jobForm.company.trim() || !jobForm.location.trim()) return;

    setCreating(true);
    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(`${API_URL}/jobs`, jobForm, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setJobs([response.data, ...jobs]);
      setJobForm({
        title: '',
        company: '',
        location: '',
        type: 'full-time',
        salary: '',
        description: '',
      });
      setShowCreateForm(false);
      alert('Job posted successfully!');
    } catch (error) {
      console.error('Failed to create job:', error);
      alert('Failed to create job. Please try again.');
    } finally {
      setCreating(false);
    }
  };



  if (loading) {
    return <div>Loading jobs...</div>;
  }

  return (
    <div className="jobs-container">
      <section className="jobs-hero">
        <h1>Find Your Perfect Job</h1>
        <p>Explore personalized job recommendations powered by AI technology.</p>
        <SearchBar />
      </section>

      <section className="jobs-content">
        <div className="jobs-section">
          <h2>All Posted Jobs</h2>
          <p>Browse all available job opportunities.</p>

          {user && user.user_type === 'employer' && (
            <div style={{ marginBottom: '20px' }}>
              <button
                onClick={() => setShowCreateForm(!showCreateForm)}
                className="upload-btn"
                style={{ marginBottom: '10px' }}
              >
                {showCreateForm ? 'Cancel' : 'Post New Job'}
              </button>

              {showCreateForm && (
                <div className="job-form" style={{ marginBottom: '20px', padding: '20px', border: '1px solid #ddd', borderRadius: '5px' }}>
                  <h3>Create New Job Posting</h3>
                  <input
                    type="text"
                    placeholder="Job Title"
                    value={jobForm.title}
                    onChange={(e) => setJobForm({...jobForm, title: e.target.value})}
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  />
                  <input
                    type="text"
                    placeholder="Company"
                    value={jobForm.company}
                    onChange={(e) => setJobForm({...jobForm, company: e.target.value})}
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  />
                  <input
                    type="text"
                    placeholder="Location"
                    value={jobForm.location}
                    onChange={(e) => setJobForm({...jobForm, location: e.target.value})}
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  />
                  <select
                    value={jobForm.type}
                    onChange={(e) => setJobForm({...jobForm, type: e.target.value})}
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  >
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                    <option value="freelance">Freelance</option>
                  </select>
                  <input
                    type="number"
                    placeholder="Salary (optional)"
                    value={jobForm.salary}
                    onChange={(e) => setJobForm({...jobForm, salary: e.target.value})}
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  />
                  <textarea
                    placeholder="Job Description"
                    value={jobForm.description}
                    onChange={(e) => setJobForm({...jobForm, description: e.target.value})}
                    rows="4"
                    style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  />
                  <button
                    onClick={handleCreateJob}
                    disabled={creating || !jobForm.title.trim() || !jobForm.company.trim() || !jobForm.location.trim()}
                    className="upload-btn"
                  >
                    {creating ? 'Posting...' : 'Post Job'}
                  </button>
                </div>
              )}
            </div>
          )}

          <div className="job-cards">
            {jobs.length > 0 ? jobs.map(job => (
              <div key={job.id} className="job-card">
                <h3>{job.title}</h3>
                <p>{job.company} - {job.location} - {job.type}</p>
                {job.salary && <p>Salary: ${job.salary}</p>}
                {job.description && <p>{job.description}</p>}
                {user && user.user_type === 'jobseeker' && (
                  <div style={{ marginTop: '10px' }}>
                    <input
                      type="file"
                      accept=".pdf,.doc,.docx"
                      onChange={(e) => setSelectedFile(e.target.files[0])}
                      style={{ marginBottom: '5px', width: '100%' }}
                    />
                    <button
                      onClick={() => handleApply(job.id)}
                      disabled={applying === job.id || !selectedFile}
                      className="apply-btn"
                    >
                      {applying === job.id ? 'Applying...' : 'Apply with Resume'}
                    </button>
                  </div>
                )}
              </div>
            )) : (
              <p>No jobs available.</p>
            )}
          </div>
        </div>

        {user && user.user_type === 'jobseeker' && (
          <div className="jobs-section">
            <h2>Matched Jobs for You</h2>
            <p>Jobs that match your profile based on our skill analysis.</p>
            <div className="job-cards">
              {jobs.filter(job => job.match_score && job.match_score > 0).length > 0 ? (
                jobs.filter(job => job.match_score && job.match_score > 0).map(job => (
                  <div key={job.id} className="job-card">
                    <h3>{job.title}</h3>
                    <p>{job.company} - {job.location} - {job.type}</p>
                    {job.salary && <p>Salary: ${job.salary}</p>}
                    {job.match_score !== undefined && (
                      <span className="match-score">{job.match_score}% match</span>
                    )}
                    <div style={{ marginTop: '10px' }}>
                      <input
                        type="file"
                        accept=".pdf,.doc,.docx"
                        onChange={(e) => setSelectedFile(e.target.files[0])}
                        style={{ marginBottom: '5px', width: '100%' }}
                      />
                      <button
                        onClick={() => handleApply(job.id)}
                        disabled={applying === job.id || !selectedFile}
                        className="apply-btn"
                      >
                        {applying === job.id ? 'Applying...' : 'Apply with Resume'}
                      </button>
                    </div>
                  </div>
                ))
              ) : (
                <p>No matched jobs available. Upload a resume to get personalized matches.</p>
              )}
            </div>
          </div>
        )}

        {/* Resume management moved to Dashboard for jobseekers */}

        <div className="jobs-section">
          <h2>How We Match Jobs</h2>
          <p>We use advanced algorithms to consider your skills, experience, location preferences, and career goals to find the best fits.</p>
        </div>
      </section>

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation</p>
        <p>Helping you connect with the right opportunities</p>
      </footer>
    </div>
  );
}

export default Jobs;
