import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import './ChatBot.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function ChatBot({ isOpen, onToggle }) {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [skills, setSkills] = useState('');
  const [loading, setLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploadingResume, setUploadingResume] = useState(false);
  const messagesEndRef = useRef(null);
  const fileInputRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  // Load chat history on component mount
  useEffect(() => {
    const loadChatHistory = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;

        const response = await axios.get(`${API_URL}/ai/chat-history`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        const historyMessages = response.data.messages.map(msg => ({
          role: msg.role,
          text: msg.message
        }));

        if (historyMessages.length === 0) {
          setMessages([{ role: 'bot', text: 'Hi! I\'m your AI career advisor. Share your skills or upload your resume to get personalized job recommendations!' }]);
        } else {
          setMessages(historyMessages);
        }
      } catch (error) {
        console.error('Failed to load chat history:', error);
        setMessages([{ role: 'bot', text: 'Hi! I\'m your AI career advisor. Share your skills or upload your resume to get personalized job recommendations!' }]);
      }
    };

    if (isOpen) {
      loadChatHistory();
    }
  }, [isOpen]);

  const handleSend = async () => {
    if (!input.trim() || loading) return;

    const userMessage = input.trim();
    setMessages(prev => [...prev, { role: 'user', text: userMessage }]);
    setInput('');
    setLoading(true);

    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(`${API_URL}/ai/chat`, {
        message: userMessage
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      setMessages(prev => [...prev, { role: 'bot', text: response.data.response }]);
    } catch (error) {
      console.error('Chat error:', error);
      setMessages(prev => [...prev, {
        role: 'bot',
        text: 'Sorry, I\'m having trouble connecting right now. Please try again later.'
      }]);
    } finally {
      setLoading(false);
    }
  };

  const handleSkillsSubmit = async () => {
    if (!skills.trim() || loading) return;

    const skillsList = skills.trim();
    setMessages(prev => [...prev, { role: 'user', text: `My skills: ${skillsList}` }]);
    setSkills('');
    setLoading(true);

    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(`${API_URL}/ai/skill-chat`, {
        skills: skillsList.split(',').map(s => s.trim()),
        limit: 5
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const suggestions = response.data.suggestions;
      let botResponse = 'Based on your skills, here are some job suggestions:\n\n';
      if (suggestions.length === 0) {
        botResponse = 'I couldn\'t find any matches for your skills in our current job listings. Here are some general recommendations:\n\n1. Consider expanding your skill set with related technologies\n2. Look for entry-level positions in your field\n3. Check back later as new jobs are posted regularly\n\nTry searching our job listings page for more options!';
      } else {
        suggestions.forEach((job, index) => {
          botResponse += `${index + 1}. ${job.title}\n   ${job.description}\n   Match: ${job.confidence}%\n\n`;
        });
        botResponse += 'Would you like me to help you apply to any of these jobs or provide more details?';
      }

      setMessages(prev => [...prev, { role: 'bot', text: botResponse }]);
    } catch (error) {
      console.error('Skills error:', error);
      setMessages(prev => [...prev, {
        role: 'bot',
        text: 'Sorry, I couldn\'t process your skills right now. Please try again later.'
      }]);
    } finally {
      setLoading(false);
    }
  };

  const handleResumeUpload = async () => {
    if (!selectedFile || uploadingResume) return;

    setMessages(prev => [...prev, { role: 'user', text: `Uploaded resume: ${selectedFile.name}` }]);
    setUploadingResume(true);
    setLoading(true);

    try {
      const token = localStorage.getItem('token');
      const formData = new FormData();
      formData.append('resume', selectedFile);

      const response = await axios.post(`${API_URL}/ai/resume-chat`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'multipart/form-data'
        }
      });

      const analysis = response.data.analysis;
      const suggestions = response.data.suggestions;

      let botResponse = `${response.data.message}\n\n`;

      // Add analysis summary
      if (analysis.summary) {
        botResponse += `📄 Resume Summary: ${analysis.summary}\n\n`;
      }

      if (analysis.skills && analysis.skills.length > 0) {
        botResponse += `🛠️ Extracted Skills: ${analysis.skills.join(', ')}\n\n`;
      }

      if (analysis.experience_years) {
        botResponse += `📅 Experience: ${analysis.experience_years}\n\n`;
      }

      botResponse += '💼 Job Suggestions:\n\n';
      if (suggestions.length === 0) {
        botResponse += 'I couldn\'t find any matches for your resume in our current job listings. Here are some general recommendations:\n\n1. Consider expanding your skill set with related technologies\n2. Look for entry-level positions in your field\n3. Check back later as new jobs are posted regularly\n\nTry searching our job listings page for more options!';
      } else {
        suggestions.forEach((job, index) => {
          botResponse += `${index + 1}. ${job.title}\n   ${job.description}\n   Match: ${job.confidence || job.match_score || 'N/A'}%\n\n`;
        });
        botResponse += 'Would you like me to help you apply to any of these jobs or provide more details?';
      }

      setMessages(prev => [...prev, { role: 'bot', text: botResponse }]);
      setSelectedFile(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (error) {
      console.error('Resume upload error:', error);
      setMessages(prev => [...prev, {
        role: 'bot',
        text: 'Sorry, I couldn\'t analyze your resume right now. Please try again later.'
      }]);
    } finally {
      setUploadingResume(false);
      setLoading(false);
    }
  };

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      if (!allowedTypes.includes(file.type)) {
        alert('Please select a PDF or Word document (.doc, .docx)');
        return;
      }
      // Validate file size (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
      }
      setSelectedFile(file);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  if (!isOpen) {
    return (
      <div className="chatbot-fab" onClick={onToggle} title="Open AI Chat">
        💬
      </div>
    );
  }

  return (
    <div className="chatbot-container">
      <div className="chatbot-header">
        <h3>AI Career Advisor</h3>
        <button className="chatbot-close" onClick={onToggle}>×</button>
      </div>

      <div className="chatbot-messages">
        {messages.map((msg, index) => (
          <div key={index} className={`message ${msg.role}`}>
            <div className="message-content">
              {msg.text}
            </div>
          </div>
        ))}
        {loading && (
          <div className="message bot">
            <div className="message-content typing">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </div>

      <div className="chatbot-input-section">
        <div className="chatbot-input">
          <textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyPress={handleKeyPress}
            placeholder="Ask me about jobs, skills, career advice..."
            disabled={loading}
            rows={1}
          />
          <button
            onClick={handleSend}
            disabled={!input.trim() || loading}
            className="send-button"
          >
            {loading ? '...' : 'Send'}
          </button>
        </div>

        <div className="chatbot-skills">
          <input
            type="text"
            value={skills}
            onChange={(e) => setSkills(e.target.value)}
            placeholder="Enter your skills (comma-separated)"
            disabled={loading}
          />
          <button
            onClick={handleSkillsSubmit}
            disabled={!skills.trim() || loading}
            className="skills-button"
          >
            Get Job Suggestions
          </button>
        </div>

        <div className="chatbot-resume">
          <input
            ref={fileInputRef}
            type="file"
            accept=".pdf,.doc,.docx"
            onChange={handleFileSelect}
            disabled={loading}
            style={{ display: 'none' }}
            id="resume-upload"
          />
          <label htmlFor="resume-upload" className="resume-upload-label">
            {selectedFile ? selectedFile.name : 'Choose Resume (PDF/DOC)'}
          </label>
          <button
            onClick={handleResumeUpload}
            disabled={!selectedFile || uploadingResume || loading}
            className="resume-button"
          >
            {uploadingResume ? 'Analyzing...' : 'Analyze Resume'}
          </button>
        </div>
      </div>
    </div>
  );
}

export default ChatBot;
