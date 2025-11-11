import React from 'react';
import './About.css';

function About() {
  return (
    <div className="about-container">
      <section className="about-hero">
        <h1>About AI-Powered Job Recommendation</h1>
        <p>Revolutionizing the way you find your dream job with intelligent AI technology.</p>
      </section>

      <section className="about-content">
        <div className="about-section">
          <h2>Our Mission</h2>
          <p>We connect talented individuals with their ideal career opportunities using advanced AI algorithms that understand your skills, preferences, and career goals.</p>
        </div>

        <div className="about-section">
          <h2>How It Works</h2>
          <p>Our AI analyzes your profile, matches you with relevant job postings, and provides personalized recommendations to help you land your next great opportunity.</p>
        </div>

        <div className="about-section">
          <h2>Features</h2>
          <ul>
            <li>Personalized job recommendations</li>
            <li>Resume optimization tips</li>
            <li>Career guidance and insights</li>
            <li>Employer-job seeker matching</li>
          </ul>
        </div>
      </section>

      <section className="team-section">
        <h2>Our Team</h2>
        <div className="team-members">
          <div className="team-member">
            <img src="/guarino.png" alt="Jaycee Thea Guarino" />
            <p>Jaycee Thea Guarino</p>
          </div>
          <div className="team-member">
            <img src="/kurt.jpg" alt="Kurt Santos" />
            <p>Kurt Santos</p>
          </div>
          <div className="team-member">
            <img src="/jop.jpg" alt="Joseph Cahindi" />
            <p>Joseph Cahindi</p>
          </div>
          <div className="team-member">
            <img src="/berna.jpg" alt="Denielle Bernardino" />
            <p>Denielle Bernardino</p>
          </div>
          <div className="team-member">
            <img src="/ven.jpg" alt="Ven Andrew Mirasol" />
            <p>Ven Andrew Mirasol</p>
          </div>
        </div>
      </section>

      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} AI-Powered Job Recommendation</p>
        <p>Helping you connect with the right opportunities</p>
      </footer>
    </div>
  );
}

export default About;
