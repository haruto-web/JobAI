import React, { useState, useEffect } from 'react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement } from 'chart.js';
import { Pie, Bar } from 'react-chartjs-2';
import { useApiCall } from '../hooks/useApiCall';
import './AdminDashboard.css';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement);

function AdminDashboard() {
  const [dashboardData, setDashboardData] = useState(null);
  const [activeTab, setActiveTab] = useState('overview');
  const [users, setUsers] = useState([]);
  const [jobs, setJobs] = useState([]);
  const [applications, setApplications] = useState([]);
  const [payments, setPayments] = useState([]);
  const [selectedJob, setSelectedJob] = useState(null);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [jobToReject, setJobToReject] = useState(null);
  const { apiCall, loading, error } = useApiCall();

  const fetchDashboard = async () => {
    try {
      const data = await apiCall('/dashboard');
      setDashboardData(data);
    } catch (err) {
      if (!err.silent && err.response?.status !== 401) {
        console.error('Failed to fetch dashboard:', err);
      }
    }
  };

  const fetchUsers = async () => {
    try {
      const data = await apiCall('/admin/users');
      setUsers(data.data);
    } catch (err) {
      console.error('Failed to fetch users:', err);
    }
  };

  const fetchJobs = async () => {
    try {
      const data = await apiCall('/admin/jobs');
      setJobs(data.data);
    } catch (err) {
      console.error('Failed to fetch jobs:', err);
    }
  };

  const fetchApplications = async () => {
    try {
      const data = await apiCall('/admin/applications');
      setApplications(data.data);
    } catch (err) {
      console.error('Failed to fetch applications:', err);
    }
  };

  const fetchPayments = async () => {
    try {
      const data = await apiCall('/admin/payments');
      setPayments(data.data);
    } catch (err) {
      console.error('Failed to fetch payments:', err);
    }
  };

  const handleApproveJob = async (jobId) => {
    if (!window.confirm('Are you sure you want to approve this job?')) return;
    try {
      await apiCall(`/admin/jobs/${jobId}/approve`, 'POST');
      alert('Job approved successfully');
      fetchJobs();
    } catch (err) {
      alert('Failed to approve job: ' + (err.response?.data?.message || err.message));
    }
  };

  const handleRejectJob = (jobId) => {
    setJobToReject(jobId);
    setShowRejectModal(true);
  };

  const confirmRejectJob = async () => {
    if (!rejectReason.trim()) {
      alert('Please provide a reason for rejection');
      return;
    }
    try {
      await apiCall(`/admin/jobs/${jobToReject}/reject`, 'POST', { reason: rejectReason });
      alert('Job rejected successfully');
      setShowRejectModal(false);
      setRejectReason('');
      setJobToReject(null);
      fetchJobs();
    } catch (err) {
      alert('Failed to reject job: ' + (err.response?.data?.message || err.message));
    }
  };

  const handleViewJobDetails = (job) => {
    setSelectedJob(job);
  };

  const handleDeleteJob = async (jobId) => {
    if (!window.confirm('Are you sure you want to delete this job? This action cannot be undone.')) return;
    try {
      await apiCall(`/admin/jobs/${jobId}`, 'DELETE');
      alert('Job deleted successfully');
      fetchJobs();
    } catch (err) {
      alert('Failed to delete job: ' + (err.response?.data?.message || err.message));
    }
  };

  useEffect(() => {
    fetchDashboard();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (activeTab === 'users') fetchUsers();
    if (activeTab === 'jobs') fetchJobs();
    if (activeTab === 'applications') fetchApplications();
    if (activeTab === 'payments') fetchPayments();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeTab]);

  if (loading) {
    return <div>Loading admin dashboard...</div>;
  }

  if (error) {
    return <div>Error: {error}</div>;
  }

  if (!dashboardData) {
    return <div>Failed to load admin dashboard.</div>;
  }

  return (
    <div className="dashboard-container">
      <section className="dashboard-hero">
        <h1>Admin Dashboard</h1>
        <p>Manage users, jobs, applications, and payments</p>
      </section>

      <section className="dashboard-content">
        <div className="admin-tabs">
          <button
            className={activeTab === 'overview' ? 'active' : ''}
            onClick={() => setActiveTab('overview')}
          >
            Overview
          </button>
          <button
            className={activeTab === 'users' ? 'active' : ''}
            onClick={() => setActiveTab('users')}
          >
            Users
          </button>
          <button
            className={activeTab === 'jobs' ? 'active' : ''}
            onClick={() => setActiveTab('jobs')}
          >
            Jobs
          </button>
          <button
            className={activeTab === 'applications' ? 'active' : ''}
            onClick={() => setActiveTab('applications')}
          >
            Applications
          </button>
          <button
            className={activeTab === 'payments' ? 'active' : ''}
            onClick={() => setActiveTab('payments')}
          >
            Payments
          </button>
        </div>

        {activeTab === 'overview' && (
          <div className="admin-overview">
            <div className="summary-cards">
              <div className="summary-card">
                <h3>Total Users</h3>
                <p>{dashboardData.summary.total_users}</p>
              </div>
              <div className="summary-card">
                <h3>Total Jobs</h3>
                <p>{dashboardData.summary.total_jobs}</p>
              </div>
              <div className="summary-card">
                <h3>Total Applications</h3>
                <p>{dashboardData.summary.total_applications}</p>
              </div>
              <div className="summary-card">
                <h3>Total Payments</h3>
                <p>${dashboardData.summary.total_payment_amount}</p>
              </div>
            </div>

            <div className="charts-container">
              <div className="chart-card">
                <h3>User Types Distribution</h3>
                <Pie data={{
                  labels: ['Jobseekers', 'Employers', 'Admins'],
                  datasets: [{
                    data: [
                      dashboardData.summary.jobseekers || 0,
                      dashboardData.summary.employers || 0,
                      dashboardData.summary.admins || 0
                    ],
                    backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
                  }]
                }} />
              </div>
              <div className="chart-card">
                <h3>Application Status</h3>
                <Bar data={{
                  labels: ['Pending', 'Accepted', 'Rejected'],
                  datasets: [{
                    label: 'Applications',
                    data: [
                      dashboardData.summary.pending_applications || 0,
                      dashboardData.summary.accepted_applications || 0,
                      dashboardData.summary.rejected_applications || 0
                    ],
                    backgroundColor: ['#FFCE56', '#36A2EB', '#FF6384']
                  }]
                }} />
              </div>
            </div>

            <div className="recent-activity">
              <h3>Recent Users</h3>
              <div className="recent-list">
                {dashboardData.recent_users.map(user => (
                  <div key={user.id} className="recent-item">
                    <p><strong>{user.name}</strong> ({user.user_type}) - {user.email}</p>
                    <small>{user.created_at}</small>
                  </div>
                ))}
              </div>

              <h3>Recent Jobs</h3>
              <div className="recent-list">
                {dashboardData.recent_jobs.map(job => (
                  <div key={job.id} className="recent-item">
                    <p><strong>{job.title}</strong> at {job.company} by {job.user}</p>
                    <small>{job.created_at}</small>
                  </div>
                ))}
              </div>

              <h3>Recent Applications</h3>
              <div className="recent-list">
                {dashboardData.recent_applications.map(app => (
                  <div key={app.id} className="recent-item">
                    <p><strong>{app.user_name}</strong> applied for <strong>{app.job_title}</strong></p>
                    <small>Status: {app.status} - {app.created_at}</small>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'users' && (
          <div className="admin-table">
            <h3>Users Management</h3>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Type</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map(user => (
                  <tr key={user.id}>
                    <td>{user.id}</td>
                    <td>{user.name}</td>
                    <td>{user.email}</td>
                    <td>{user.user_type}</td>
                    <td>{new Date(user.created_at).toLocaleDateString()}</td>
                    <td>
                      <button>Edit</button>
                      <button>Delete</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {activeTab === 'jobs' && (
          <div className="admin-table">
            <h3>Job Listings Management</h3>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Title</th>
                  <th>Company</th>
                  <th>Employer</th>
                  <th>Status</th>
                  <th>Urgent</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {jobs.map(job => (
                  <tr key={job.id}>
                    <td>{job.id}</td>
                    <td>{job.title}</td>
                    <td>{job.company}</td>
                    <td>{job.user?.name || 'Unknown'}</td>
                    <td>
                      <span className={`status-${job.status}`}>
                        {job.status.replace('_', ' ').toUpperCase()}
                      </span>
                    </td>
                    <td>{job.urgent ? 'Yes' : 'No'}</td>
                    <td>{new Date(job.created_at).toLocaleDateString()}</td>
                    <td>
                      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '5px' }}>
                        {(job.status === 'pending_approval' || job.status === 'draft') && (
                          <>
                            <button
                              onClick={() => handleApproveJob(job.id)}
                              style={{ backgroundColor: '#28a745', color: 'white', border: 'none', padding: '5px 10px', borderRadius: '3px', cursor: 'pointer' }}
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => handleRejectJob(job.id)}
                              style={{ backgroundColor: '#dc3545', color: 'white', border: 'none', padding: '5px 10px', borderRadius: '3px', cursor: 'pointer' }}
                            >
                              Reject
                            </button>
                          </>
                        )}
                        <button
                          onClick={() => handleViewJobDetails(job)}
                          style={{ backgroundColor: '#007bff', color: 'white', border: 'none', padding: '5px 10px', borderRadius: '3px', cursor: 'pointer' }}
                        >
                          View Details
                        </button>
                        <button
                          onClick={() => handleDeleteJob(job.id)}
                          style={{ backgroundColor: '#dc3545', color: 'white', border: 'none', padding: '5px 10px', borderRadius: '3px', cursor: 'pointer' }}
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            {selectedJob && (
              <div className="job-details-modal" style={{
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                backgroundColor: 'white',
                padding: '20px',
                borderRadius: '5px',
                boxShadow: '0 0 10px rgba(0,0,0,0.3)',
                zIndex: 1000,
                maxWidth: '600px',
                maxHeight: '80vh',
                overflowY: 'auto'
              }}>
                <h3>Job Details</h3>
                <p><strong>Title:</strong> {selectedJob.title}</p>
                <p><strong>Company:</strong> {selectedJob.company}</p>
                <p><strong>Location:</strong> {selectedJob.location}</p>
                <p><strong>Type:</strong> {selectedJob.type}</p>
                <p><strong>Salary:</strong> {selectedJob.salary ? `$${selectedJob.salary}` : 'Not specified'}</p>
                <p><strong>Description:</strong> {selectedJob.description}</p>
                <p><strong>Employer:</strong> {selectedJob.user?.name} ({selectedJob.user?.email})</p>
                <p><strong>Status:</strong> {selectedJob.status}</p>
                <p><strong>Created:</strong> {new Date(selectedJob.created_at).toLocaleString()}</p>
                <button
                  onClick={() => setSelectedJob(null)}
                  style={{ marginTop: '10px', padding: '5px 10px', backgroundColor: '#6c757d', color: 'white', border: 'none', borderRadius: '3px', cursor: 'pointer' }}
                >
                  Close
                </button>
              </div>
            )}

            {showRejectModal && (
              <div className="reject-modal" style={{
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                backgroundColor: 'white',
                padding: '20px',
                borderRadius: '5px',
                boxShadow: '0 0 10px rgba(0,0,0,0.3)',
                zIndex: 1000,
                maxWidth: '400px'
              }}>
                <h3>Reject Job Post</h3>
                <p>Provide a reason for rejection:</p>
                <textarea
                  value={rejectReason}
                  onChange={(e) => setRejectReason(e.target.value)}
                  rows="4"
                  style={{ width: '100%', marginBottom: '10px', padding: '8px' }}
                  placeholder="Enter rejection reason..."
                />
                <div style={{ display: 'flex', gap: '10px' }}>
                  <button
                    onClick={confirmRejectJob}
                    style={{ backgroundColor: '#dc3545', color: 'white', border: 'none', padding: '8px 16px', borderRadius: '3px', cursor: 'pointer' }}
                  >
                    Reject
                  </button>
                  <button
                    onClick={() => setShowRejectModal(false)}
                    style={{ backgroundColor: '#6c757d', color: 'white', border: 'none', padding: '8px 16px', borderRadius: '3px', cursor: 'pointer' }}
                  >
                    Cancel
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        {activeTab === 'applications' && (
          <div className="admin-table">
            <h3>Applications Management</h3>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Job</th>
                  <th>Applicant</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {applications.map(app => (
                  <tr key={app.id}>
                    <td>{app.id}</td>
                    <td>{app.job?.title || 'Unknown'}</td>
                    <td>{app.user?.name || 'Unknown'}</td>
                    <td>{app.status}</td>
                    <td>{new Date(app.created_at).toLocaleDateString()}</td>
                    <td>
                      <button>Edit</button>
                      <button>Delete</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {activeTab === 'payments' && (
          <div className="admin-table">
            <h3>Payments Management</h3>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Application</th>
                  <th>Amount</th>
                  <th>Description</th>
                  <th>Processed At</th>
                </tr>
              </thead>
              <tbody>
                {payments.map(payment => (
                  <tr key={payment.id}>
                    <td>{payment.id}</td>
                    <td>{payment.application?.job?.title || 'N/A'}</td>
                    <td>${payment.amount}</td>
                    <td>{payment.description}</td>
                    <td>{new Date(payment.processed_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation - Admin Panel</p>
        <p>Manage your platform effectively</p>
      </footer>
    </div>
  );
}

export default AdminDashboard;
