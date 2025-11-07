import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import SearchBar from '../components/SearchBar';
import './Dashboard.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function Dashboard() {
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const [jobForm, setJobForm] = useState({
    title: '',
    description: '',
    summary: '',
    qualifications: '',
    company: '',
    location: '',
    type: 'full-time',
    salary: '',
    requirements: []
  });
  const [creatingJob, setCreatingJob] = useState(false);
  const [payoutForm, setPayoutForm] = useState({ applicationId: null, amount: '', description: '' });
  const [showPayoutForm, setShowPayoutForm] = useState(false);
  const [processingPayout, setProcessingPayout] = useState(false);
  const [moneyForm, setMoneyForm] = useState({ amount: '', description: '' });
  const [showMoneyForm, setShowMoneyForm] = useState(false);
  const [processingMoney, setProcessingMoney] = useState(false);
  const [moneyAction, setMoneyAction] = useState('');
  const [cancelForm, setCancelForm] = useState({ applicationId: null, reason: '' });
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancellingApplication, setCancellingApplication] = useState(false);

  // Resume upload for accepted jobs
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploadingResume, setUploadingResume] = useState(false);
  const [editingJob, setEditingJob] = useState(null); // State to hold the job being edited
  const [showEditModal, setShowEditModal] = useState(false); // State to control edit modal visibility


  const handleToggleUrgent = async (jobId, currentUrgent) => {
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/jobs/${jobId}`, { urgent: !currentUrgent }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert(`Job ${!currentUrgent ? 'marked as' : 'removed from'} urgent successfully!`);
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to toggle urgent:', error);
      alert('Failed to update job. Please try again.');
    }
  };

  const handleDeleteJob = async (jobId) => {
    if (!window.confirm('Are you sure you want to delete this job?')) {
      return;
    }
    try {
      const token = localStorage.getItem('token');
      await axios.delete(`${API_URL}/jobs/${jobId}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Job deleted successfully!');
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to delete job:', error);
      alert('Failed to delete job. Please try again.');
    }
  };

  const handleEditJob = (job) => {
    setEditingJob({
      ...job,
      requirements: job.requirements || [],
      summary: job.summary || '',
      qualifications: job.qualifications || ''
    }); // Initialize missing fields
    setShowEditModal(true);
  };

  const handleUpdateJob = async (e) => {
    e.preventDefault();
    if (!editingJob) return;

    setCreatingJob(true); // Re-using creatingJob state for loading
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/jobs/${editingJob.id}`, editingJob, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Job updated successfully!');
      setShowEditModal(false);
      setEditingJob(null);
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to update job:', error);
      alert('Failed to update job. Please try again.');
    } finally {
      setCreatingJob(false);
    }
  };

  const handleEditFormChange = (e) => {
    const { name, value } = e.target;
    setEditingJob(prev => ({ ...prev, [name]: value }));
  };

  const fetchDashboard = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        navigate('/login');
        return;
      }
      const response = await axios.get(`${API_URL}/dashboard`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to fetch dashboard:', error);
      if (error.response && error.response.status === 401) {
        navigate('/login');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboard();
  }, [navigate]);

  const handleApplicationAction = async (applicationId, status) => {
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/applications/${applicationId}`, { status }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      if (status === 'rejected') {
        alert('Application rejected and removed successfully!');
      } else {
        alert(`Application ${status} successfully!`);
      }
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to update application:', error);
      alert('Failed to update application. Please try again.');
    }
  };

  const handleJobFormChange = (e) => {
    const { name, value } = e.target;
    setJobForm(prev => ({ ...prev, [name]: value }));
  };

  const handleCreateJob = async (e) => {
    e.preventDefault();
    setCreatingJob(true);
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/jobs`, jobForm, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Job created successfully!');
      setJobForm({
        title: '',
        description: '',
        summary: '',
        qualifications: '',
        company: '',
        location: '',
        type: 'full-time',
        salary: '',
        requirements: []
      });
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to create job:', error);
      alert('Failed to create job. Please try again.');
    } finally {
      setCreatingJob(false);
    }
  };

  const handlePayout = async (applicationId) => {
    if (!payoutForm.amount || !payoutForm.description) return;

    setProcessingPayout(true);
    try {
      const token = localStorage.getItem('token');
      await axios.post(`${API_URL}/payments`, {
        application_id: applicationId,
        amount: parseFloat(payoutForm.amount),
        description: payoutForm.description
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Payout processed successfully!');
      setPayoutForm({ applicationId: null, amount: '', description: '' });
      setShowPayoutForm(false);
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to process payout:', error);
      alert('Failed to process payout. Please try again.');
    } finally {
      setProcessingPayout(false);
    }
  };

  const handleMoneyAction = async () => {
    if (!moneyForm.amount || !moneyForm.description) return;

    setProcessingMoney(true);
    try {
      const token = localStorage.getItem('token');
      const amount = moneyAction === 'add' ? parseFloat(moneyForm.amount) : -parseFloat(moneyForm.amount);
      await axios.post(`${API_URL}/manage-money`, {
        amount: amount,
        description: moneyForm.description
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert(`${moneyAction === 'add' ? 'Money added' : 'Money reduced'} successfully!`);
      setMoneyForm({ amount: '', description: '' });
      setShowMoneyForm(false);
      setMoneyAction('');
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to manage money:', error);
      alert('Failed to manage money. Please try again.');
    } finally {
      setProcessingMoney(false);
    }
  };

  // Resume upload for accepted jobs
  const handleUploadResumeForJob = async (projectId) => {
    if (!selectedFile) return;
    setUploadingResume(true);
    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('resume', selectedFile);
      formData.append('project_id', projectId);

      await axios.post(`${API_URL}/user/upload-resume-for-job`, formData, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setSelectedFile(null);
      alert('Resume uploaded successfully for the job!');
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to upload resume for job:', error);
      alert('Failed to upload resume for job. Please try again.');
    } finally {
      setUploadingResume(false);
    }
  };

  const handleCancelApplication = async () => {
    if (!cancelForm.reason.trim()) return;

    setCancellingApplication(true);
    try {
      const token = localStorage.getItem('token');
      await axios.put(`${API_URL}/applications/${cancelForm.applicationId}/cancel`, {
        cancel_reason: cancelForm.reason
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      alert('Application cancelled successfully!');
      setCancelForm({ applicationId: null, reason: '' });
      setShowCancelModal(false);
      fetchDashboard(); // Refresh dashboard data
    } catch (error) {
      console.error('Failed to cancel application:', error);
      alert('Failed to cancel application. Please try again.');
    } finally {
      setCancellingApplication(false);
    }
  };



  if (loading) {
    return <div>Loading dashboard...</div>;
  }

  if (!dashboardData) {
    return <div>Failed to load dashboard.</div>;
  }

  return (
    <div className="dashboard-container">
      <section className="dashboard-hero">
        <h1>Dashboard</h1>
        <p>Welcome back! Here's your overview.</p>
        <SearchBar />
      </section>

      <section className="dashboard-content">
        {dashboardData.user_type === 'jobseeker' ? (
          <div className="jobseeker-dashboard">
            <details className="dashboard-section" open>
              <summary>Job Seeker Dashboard</summary>
              <div className="section-content">
                <p>Manage your job applications and track your progress</p>
              </div>
            </details>

            <details className="dashboard-section" open>
              <summary>Your Applications</summary>
              <div className="section-content">
                {dashboardData.applications.length > 0 ? (
                  <div className="applications-list">
                    {dashboardData.applications.map(app => (
                      <div key={app.id} className="application-card">
                        <h3>{app.job.title}</h3>
                        <p>Company: {app.job.company}</p>
                        <p>Location: {app.job.location}</p>
                        <p>Status: <span className={`status-${app.status}`}>{app.status}</span></p>
                        <p>Applied on: {new Date(app.created_at).toLocaleDateString()}</p>
                        {app.status === 'pending' && (
                          <button
                            className="action-btn cancel"
                            onClick={() => {
                              setCancelForm({ applicationId: app.id, reason: '' });
                              setShowCancelModal(true);
                            }}
                          >
                            ❌ Cancel Application
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p>You haven't applied to any jobs yet.</p>
                )}
              </div>
            </details>

            {dashboardData.profile && (
              <details className="dashboard-section">
                <summary>Your Profile</summary>
                <div className="section-content">
                  {dashboardData.profile.bio && <p><strong>Bio:</strong> {dashboardData.profile.bio}</p>}
                  {dashboardData.profile.skills && dashboardData.profile.skills.length > 0 && (
                    <p><strong>Skills:</strong> {dashboardData.profile.skills.join(', ')}</p>
                  )}
                  {dashboardData.profile.experience_level && <p><strong>Experience Level:</strong> {dashboardData.profile.experience_level}</p>}
                  {dashboardData.profile.years_of_experience && <p><strong>Years of Experience:</strong> {dashboardData.profile.years_of_experience}</p>}
                </div>
              </details>
            )}

            <details className="dashboard-section">
              <summary>Accepted Jobs (Working On)</summary>
              <div className="section-content">
                {dashboardData.incoming_projects && dashboardData.incoming_projects.length > 0 ? (
                  <div className="projects-list">
                    {dashboardData.incoming_projects.map(project => (
                      <div key={project.id} className="project-card">
                        <h3>{project.job.title}</h3>
                        <p>Company: {project.job.company}</p>
                        <p>Location: {project.job.location}</p>
                        <p>Status: {project.status}</p>
                        <div className="resume-upload-section">
                          <h4>Upload Resume for this Job</h4>
                          <input
                            type="file"
                            accept=".pdf,.doc,.docx"
                            onChange={(e) => setSelectedFile(e.target.files[0])}
                            disabled={uploadingResume}
                          />
                          <button
                            onClick={() => handleUploadResumeForJob(project.id)}
                            disabled={uploadingResume || !selectedFile}
                            className="upload-resume-btn"
                          >
                            {uploadingResume ? 'Uploading...' : 'Upload Resume'}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p>No accepted jobs yet.</p>
                )}
              </div>
            </details>

            <details className="dashboard-section">
              <summary>Earnings & Transactions</summary>
              <div className="section-content">
                <p>Total Earnings: ${dashboardData.total_earnings}</p>
                {dashboardData.transactions.length > 0 ? (
                  <div className="transactions-list">
                    {dashboardData.transactions.map(tx => (
                      <div key={tx.id} className="transaction-card">
                        <p>{tx.description}</p>
                        <p>Amount: ${tx.amount} ({tx.type})</p>
                        <p>Date: {tx.date}</p>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p>No transactions yet.</p>
                )}
              </div>
            </details>
          </div>
        ) : (
          <div className="employer-dashboard">
            <div className="dashboard-section">
              <h2>Employer Dashboard</h2>
              <p>Manage your job postings and review applications</p>
            </div>

            {/* Analytics & Insights Section */}
            {dashboardData.analytics && (
              <div className="dashboard-section">
                <h2>📊 Analytics & Insights</h2>
                <p>Get an overview of your hiring performance</p>

                <div className="analytics-grid">
                  <div className="analytics-card">
                    <h3>Total Job Posts</h3>
                    <div className="metric">{dashboardData.analytics.total_job_posts}</div>
                  </div>

                  <div className="analytics-card">
                    <h3>Active Jobs</h3>
                    <div className="metric">{dashboardData.analytics.active_jobs}</div>
                    <small>Last 30 days</small>
                  </div>

                  <div className="analytics-card">
                    <h3>Closed Jobs</h3>
                    <div className="metric">{dashboardData.analytics.closed_jobs}</div>
                    <small>Older than 30 days</small>
                  </div>

                  <div className="analytics-card">
                    <h3>Total Applications</h3>
                    <div className="metric">{dashboardData.total_applications}</div>
                  </div>
                </div>

                {/* Applications per Job */}
                {dashboardData.analytics.applications_per_job && dashboardData.analytics.applications_per_job.length > 0 && (
                  <div className="analytics-subsection">
                    <h3>Applications per Job</h3>
                    <div className="applications-per-job">
                      {dashboardData.analytics.applications_per_job.map((job, index) => (
                        <div key={index} className="job-app-item">
                          <span className="job-title">{job.job_title}</span>
                          <span className="app-count">{job.applications_count} applications</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Application Trends Chart */}
                {dashboardData.analytics.application_trends && dashboardData.analytics.application_trends.length > 0 && (
                  <div className="analytics-subsection">
                    <h3>Application Trends (Last 7 Days)</h3>
                    <div className="trends-chart">
                      {dashboardData.analytics.application_trends.map((trend, index) => (
                        <div key={index} className="trend-bar">
                          <div className="trend-date">{new Date(trend.date).toLocaleDateString()}</div>
                          <div className="trend-bar-fill" style={{ height: `${Math.max(trend.applications * 20, 10)}px` }}>
                            <span className="trend-count">{trend.applications}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Recent Activity Feed */}
                {dashboardData.analytics.recent_activities && dashboardData.analytics.recent_activities.length > 0 && (
                  <div className="analytics-subsection">
                    <h3>Recent Activity Feed</h3>
                    <div className="activity-feed">
                      {dashboardData.analytics.recent_activities.map((activity, index) => (
                        <div key={index} className="activity-item">
                          <div className="activity-icon">
                            {activity.type === 'application' ? '📝' : '💰'}
                          </div>
                          <div className="activity-content">
                            <p>{activity.message}</p>
                            <small>{new Date(activity.date).toLocaleString()}</small>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}

            <div className="dashboard-section">
              <h2>Create New Job</h2>
              <form onSubmit={handleCreateJob} className="job-form">
                <div className="form-group">
                  <label htmlFor="title">Job Title</label>
                  <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Job Title"
                    value={jobForm.title}
                    onChange={handleJobFormChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="description">Job Description</label>
                  <textarea
                    id="description"
                    name="description"
                    placeholder="Job Description"
                    value={jobForm.description}
                    onChange={handleJobFormChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="summary">Job Summary</label>
                  <textarea
                    id="summary"
                    name="summary"
                    placeholder="Brief summary of the job"
                    value={jobForm.summary}
                    onChange={handleJobFormChange}
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="qualifications">Qualifications</label>
                  <textarea
                    id="qualifications"
                    name="qualifications"
                    placeholder="Required qualifications and skills"
                    value={jobForm.qualifications}
                    onChange={handleJobFormChange}
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="company">Company</label>
                  <input
                    type="text"
                    id="company"
                    name="company"
                    placeholder="Company"
                    value={jobForm.company}
                    onChange={handleJobFormChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="location">Location</label>
                  <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="Location"
                    value={jobForm.location}
                    onChange={handleJobFormChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="type">Job Type</label>
                  <select id="type" name="type" value={jobForm.type} onChange={handleJobFormChange}>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="contract">Contract</option>
                  </select>
                </div>
                <div className="form-group">
                  <label htmlFor="salary">Salary (optional)</label>
                  <input
                    type="number"
                    id="salary"
                    name="salary"
                    placeholder="Salary (optional)"
                    value={jobForm.salary}
                    onChange={handleJobFormChange}
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="urgent">Mark as Urgent</label>
                  <input
                    type="checkbox"
                    id="urgent"
                    name="urgent"
                    checked={jobForm.urgent}
                    onChange={(e) => setJobForm(prev => ({ ...prev, urgent: e.target.checked }))}
                  />
                </div>
                <button type="submit" disabled={creatingJob} className="create-job-btn">
                  {creatingJob ? 'Creating...' : 'Create Job'}
                </button>
              </form>
            </div>

            <div className="dashboard-section">
              <h2>Your Job Postings</h2>
              <p>Active Jobs: {dashboardData.active_jobs}</p>
              {dashboardData.jobs.length > 0 ? (
                <div className="jobs-list">
                  {dashboardData.jobs.map(job => (
                    <div key={job.id} className="job-card">
                      <h3>{job.title}</h3>
                      <p><strong>Description:</strong> {job.description}</p>
                      <p><strong>Summary:</strong> {job.summary || 'Not provided'}</p>
                      <p><strong>Qualifications:</strong> {job.qualifications || 'Not provided'}</p>
                      <p><strong>Company:</strong> {job.company}</p>
                      <p><strong>Location:</strong> {job.location}</p>
                      <p><strong>Type:</strong> {job.type}</p>
                      <p><strong>Salary:</strong> {job.salary ? `$${job.salary}` : 'Not specified'}</p>
                      <p><strong>Requirements:</strong> {job.requirements && job.requirements.length > 0 ? job.requirements.join(', ') : 'None'}</p>
                      <p><strong>Status:</strong>
                        <span className={`status-${job.status}`}>
                          {job.status.replace('_', ' ').toUpperCase()}
                        </span>
                      </p>
                      <p><strong>Urgent:</strong> {job.urgent ? 'Yes' : 'No'}</p>
                      {job.status === 'rejected' && job.rejection_reason && (
                        <div className="rejection-notice" style={{ backgroundColor: '#fee', padding: '10px', borderRadius: '5px', marginTop: '10px' }}>
                          <p><strong>❌ Rejection Reason:</strong> {job.rejection_reason}</p>
                        </div>
                      )}
                      {job.status === 'pending_approval' && (
                        <div className="pending-notice" style={{ backgroundColor: '#fff3cd', padding: '10px', borderRadius: '5px', marginTop: '10px' }}>
                          <p><strong>⏳ Pending Approval:</strong> Your job is awaiting admin approval. It will be visible to job seekers once approved.</p>
                        </div>
                      )}
                      {job.status === 'approved' && (
                        <div className="approved-notice" style={{ backgroundColor: '#d4edda', padding: '10px', borderRadius: '5px', marginTop: '10px' }}>
                          <p><strong>✅ Approved:</strong> Your job is now live and visible to job seekers on the Jobs page.</p>
                        </div>
                      )}
                      <p>Applications: {job.applications ? job.applications.length : 0}</p>
                      <button
                        onClick={() => handleToggleUrgent(job.id, job.urgent)}
                        className="urgent-toggle-btn"
                      >
                        {job.urgent ? 'Remove Urgent' : 'Mark as Urgent'}
                      </button>
                      <button
                        onClick={() => handleEditJob(job)}
                        className="edit-job-btn"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleDeleteJob(job.id)}
                        className="delete-job-btn"
                      >
                        Delete
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <p>You haven't posted any jobs yet.</p>
              )}
            </div>

            <div className="dashboard-section">
              <h2>Hired Workers</h2>
              {dashboardData.working_on_jobs && dashboardData.working_on_jobs.length > 0 ? (
                <div className="working-jobs-list">
                  {dashboardData.working_on_jobs.map(app => (
                    <div key={app.id} className="working-job-card">
                      <h3>{app.job.title}</h3>
                      <p>Worker: {app.user.name}</p>
                      <p>Status: {app.status}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <p>No hired workers yet.</p>
              )}
            </div>

            <div className="dashboard-section">
              <h2>Job Applications</h2>
              <p>Total Applications: {dashboardData.total_applications}</p>
              {dashboardData.applications.length > 0 ? (
                <div className="applications-list">
                  {dashboardData.applications.map(app => (
                    <div key={app.id} className="application-card enhanced">
                      <div className="application-header">
                        <h3>{app.job.title}</h3>
                        <span className={`status-badge ${app.status}`}>{app.status}</span>
                      </div>
                      
                      <div className="applicant-info">
                        <h4>👤 Applicant: {app.user.name}</h4>
                        <p>📧 Email: <a href={`/user/${app.user.id}`} className="email-link">{app.user.email}</a></p>
                        
                        {/* Resume Information */}
                        {app.user.profile && (
                          <div className="resume-section">
                            <h5>📄 Resume & Profile</h5>
                            {app.resume_path ? (
                              <div className="resume-links">
                                <a
                                  href={`${API_URL}/storage/${app.resume_path}`}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="resume-link"
                                >
                                  📎 View Submitted Resume
                                </a>
                              </div>
                            ) : app.user.profile.resumes && app.user.profile.resumes.length > 0 ? (
                              <div className="resume-links">
                                {app.user.profile.resumes.map((resume, index) => (
                                  <a
                                    key={index}
                                    href={`${API_URL}/storage/${resume.url}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="resume-link"
                                  >
                                    📎 {resume.name}
                                  </a>
                                ))}
                              </div>
                            ) : app.user.profile.resume_url ? (
                              <a
                                href={`${API_URL}/storage/${app.user.profile.resume_url}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="resume-link"
                              >
                                📎 View Resume
                              </a>
                            ) : (
                              <p className="no-resume">No resume uploaded</p>
                            )}
                          </div>
                        )}

                        {/* AI Analysis Information */}
                        {app.user.profile && app.user.profile.ai_analysis && (
                          <div className="ai-analysis-section">
                            <h5>🤖 AI Analysis</h5>
                            <div className="ai-details">
                              {app.user.profile.resume_summary && (
                                <div className="ai-item">
                                  <strong>Summary:</strong>
                                  <p>{app.user.profile.resume_summary}</p>
                                </div>
                              )}
      {app.user.profile.experience_level && (
        <div className="ai-item">
          <strong>Experience Level:</strong>
          <span>{app.user.profile.experience_level}</span>
        </div>
      )}
      {app.user.profile.years_of_experience && (
        <div className="ai-item">
          <strong>Years of Experience:</strong>
          <span>{app.user.profile.years_of_experience}</span>
        </div>
      )}
                              {app.user.profile.education_attainment && (
                                <div className="ai-item">
                                  <strong>Education Attainment:</strong>
                                  <span>{app.user.profile.education_attainment}</span>
                                </div>
                              )}
                              {app.user.profile.skills && app.user.profile.skills.length > 0 && (
                                <div className="ai-item">
                                  <strong>Skills:</strong>
                                  <div className="skills-tags">
                                    {app.user.profile.skills.map((skill, index) => (
                                      <span key={index} className="skill-tag">{skill}</span>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </div>
                          </div>
                        )}

                        {/* Cover Letter */}
                        {app.cover_letter && (
                          <div className="cover-letter-section">
                            <h5>💌 Cover Letter</h5>
                            <div className="cover-letter-content">
                              {app.cover_letter}
                            </div>
                          </div>
                        )}
                      </div>

                      <div className="application-actions">
                        <button
                          className="action-btn accept"
                          onClick={() => handleApplicationAction(app.id, 'accepted')}
                        >
                          ✅ Accept
                        </button>
                        <button
                          className="action-btn reject"
                          onClick={() => handleApplicationAction(app.id, 'rejected')}
                        >
                          ❌ Reject
                        </button>
                        {app.status === 'accepted' && (
                          <button
                            className="action-btn payout"
                            onClick={() => {
                              setPayoutForm({ applicationId: app.id, amount: '', description: '' });
                              setShowPayoutForm(true);
                            }}
                          >
                            💰 Pay Worker
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p>No applications yet.</p>
              )}
            </div>

            <div className="dashboard-section">
              <h2>Payments & Transactions</h2>
              <p>Employer's Money: ${dashboardData.total_spent}</p>
              <div className="money-actions">
                <button
                  className="action-btn add-money"
                  onClick={() => {
                    setMoneyAction('add');
                    setShowMoneyForm(true);
                  }}
                >
                  ➕ Add Money
                </button>
                <button
                  className="action-btn reduce-money"
                  onClick={() => {
                    setMoneyAction('reduce');
                    setShowMoneyForm(true);
                  }}
                >
                  ➖ Reduce Money
                </button>
              </div>
              {dashboardData.transactions.length > 0 ? (
                <div className="transactions-list">
                  {dashboardData.transactions.map(tx => (
                    <div key={tx.id} className="transaction-card">
                      <p>{tx.description}</p>
                      <p>Amount: ${tx.amount} ({tx.type})</p>
                      <p>Date: {tx.date}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <p>No transactions yet.</p>
              )}
            </div>
          </div>
        )}
      </section>



      {/* Payout Modal */}
      {showPayoutForm && (
        <div className="modal-overlay" onClick={() => setShowPayoutForm(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>Process Payout</h3>
            <div className="form-group">
              <label htmlFor="payout-amount">Amount ($)</label>
              <input
                type="number"
                id="payout-amount"
                placeholder="Enter amount"
                value={payoutForm.amount}
                onChange={(e) => setPayoutForm({...payoutForm, amount: e.target.value})}
                min="0"
                step="0.01"
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="payout-description">Description</label>
              <input
                type="text"
                id="payout-description"
                placeholder="Payment description"
                value={payoutForm.description}
                onChange={(e) => setPayoutForm({...payoutForm, description: e.target.value})}
                required
              />
            </div>
            <div className="modal-actions">
              <button
                className="cancel-btn"
                onClick={() => setShowPayoutForm(false)}
              >
                Cancel
              </button>
              <button
                className="submit-btn"
                onClick={() => handlePayout(payoutForm.applicationId)}
                disabled={processingPayout || !payoutForm.amount || !payoutForm.description}
              >
                {processingPayout ? 'Processing...' : 'Process Payout'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Money Management Modal */}
      {showMoneyForm && (
        <div className="modal-overlay" onClick={() => setShowMoneyForm(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>{moneyAction === 'add' ? 'Add Money' : 'Reduce Money'}</h3>
            <div className="form-group">
              <label htmlFor="money-amount">Amount ($)</label>
              <input
                type="number"
                id="money-amount"
                placeholder="Enter amount"
                value={moneyForm.amount}
                onChange={(e) => setMoneyForm({...moneyForm, amount: e.target.value})}
                min="0"
                step="0.01"
                required
              />
            </div>
            <div className="form-group">
              <label htmlFor="money-description">Description</label>
              <input
                type="text"
                id="money-description"
                placeholder="Transaction description"
                value={moneyForm.description}
                onChange={(e) => setMoneyForm({...moneyForm, description: e.target.value})}
                required
              />
            </div>
            <div className="modal-actions">
              <button
                className="cancel-btn"
                onClick={() => setShowMoneyForm(false)}
              >
                Cancel
              </button>
              <button
                className="submit-btn"
                onClick={() => handleMoneyAction()}
                disabled={processingMoney || !moneyForm.amount || !moneyForm.description}
              >
                {processingMoney ? 'Processing...' : (moneyAction === 'add' ? 'Add Money' : 'Reduce Money')}
              </button>
            </div>
          </div>
        </div>
      )}

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation</p>
        <p>Helping you connect with the right opportunities</p>
      </footer>

      {/* Edit Job Modal */}
      {showEditModal && editingJob && (
        <div className="modal-overlay" onClick={() => setShowEditModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>Edit Job</h3>
            <form onSubmit={handleUpdateJob} className="job-form">
              <div className="form-group">
                <label htmlFor="edit-title">Job Title</label>
                <input
                  type="text"
                  id="edit-title"
                  name="title"
                  placeholder="Job Title"
                  value={editingJob.title}
                  onChange={handleEditFormChange}
                  required
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-summary">Job Summary</label>
                <textarea
                  id="edit-summary"
                  name="summary"
                  placeholder="Brief summary of the job"
                  value={editingJob.summary}
                  onChange={handleEditFormChange}
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-description">Job Description</label>
                <textarea
                  id="edit-description"
                  name="description"
                  placeholder="Job Description"
                  value={editingJob.description}
                  onChange={handleEditFormChange}
                  required
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-qualifications">Qualifications</label>
                <textarea
                  id="edit-qualifications"
                  name="qualifications"
                  placeholder="Required qualifications and skills"
                  value={editingJob.qualifications}
                  onChange={handleEditFormChange}
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-company">Company</label>
                <input
                  type="text"
                  id="edit-company"
                  name="company"
                  placeholder="Company"
                  value={editingJob.company}
                  onChange={handleEditFormChange}
                  required
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-location">Location</label>
                <input
                  type="text"
                  id="edit-location"
                  name="location"
                  placeholder="Location"
                  value={editingJob.location}
                  onChange={handleEditFormChange}
                  required
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-type">Job Type</label>
                <select id="edit-type" name="type" value={editingJob.type} onChange={handleEditFormChange}>
                  <option value="full-time">Full-time</option>
                  <option value="part-time">Part-time</option>
                  <option value="contract">Contract</option>
                </select>
              </div>
              <div className="form-group">
                <label htmlFor="edit-salary">Salary (optional)</label>
                <input
                  type="number"
                  id="edit-salary"
                  name="salary"
                  placeholder="Salary (optional)"
                  value={editingJob.salary}
                  onChange={handleEditFormChange}
                />
              </div>
              <div className="form-group">
                <label htmlFor="edit-urgent">Mark as Urgent</label>
                <input
                  type="checkbox"
                  id="edit-urgent"
                  name="urgent"
                  checked={editingJob.urgent}
                  onChange={(e) => setEditingJob(prev => ({ ...prev, urgent: e.target.checked }))}
                />
              </div>
              <div className="modal-actions">
                <button
                  type="button"
                  className="cancel-btn"
                  onClick={() => setShowEditModal(false)}
                >
                  Cancel
                </button>
                <button type="submit" disabled={creatingJob} className="submit-btn">
                  {creatingJob ? 'Updating...' : 'Update Job'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Cancel Application Modal */}
      {showCancelModal && (
        <div className="modal-overlay" onClick={() => setShowCancelModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <h3>Cancel Application</h3>
            <div className="form-group">
              <label htmlFor="cancel-reason">Reason for Cancellation</label>
              <textarea
                id="cancel-reason"
                placeholder="Please provide a reason for cancelling this application"
                value={cancelForm.reason}
                onChange={(e) => setCancelForm({ ...cancelForm, reason: e.target.value })}
                required
              />
            </div>
            <div className="modal-actions">
              <button
                className="cancel-btn"
                onClick={() => setShowCancelModal(false)}
              >
                Cancel
              </button>
              <button
                className="submit-btn"
                onClick={() => handleCancelApplication()}
                disabled={cancellingApplication || !cancelForm.reason.trim()}
              >
                {cancellingApplication ? 'Cancelling...' : 'Cancel Application'}
              </button>
            </div>
          </div>
        </div>
      )}
      </div>
  );
}

export default Dashboard;

    // Floating Chat UI: render at bottom-right
    /* We'll mount chat markup via a small component-like block inserted into DOM by React return. */
