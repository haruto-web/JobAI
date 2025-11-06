import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import './SearchBar.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

function SearchBar() {
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [searchType, setSearchType] = useState('jobs');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);

  const handleSearch = async () => {
    if (!query.trim()) return;

    setLoading(true);
    try {
      const endpoint = searchType === 'jobs' ? 'jobs/search' : 'users/search';
      const response = await axios.get(`${API_URL}/${endpoint}`, {
        params: { q: query.trim() }
      });
      setResults(response.data[searchType] || []);
    } catch (error) {
      console.error('Search failed:', error);
      setResults([]);
    } finally {
      setLoading(false);
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter') {
      handleSearch();
    }
  };

  return (
    <div className="search-bar-container">
      <div className="search-bar">
        <select
          name="searchType"
          value={searchType}
          onChange={(e) => setSearchType(e.target.value)}
          className="search-type-select"
        >
          <option value="jobs">Search Jobs</option>
          <option value="users">Search Users</option>
        </select>
        <input
          type="text"
          name="query"
          placeholder={`Search ${searchType}...`}
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyPress={handleKeyPress}
          className="search-input"
        />
        <button
          onClick={handleSearch}
          disabled={loading || !query.trim()}
          className="search-button"
        >
          {loading ? 'Searching...' : 'Search'}
        </button>
      </div>

      {results.length > 0 && (
        <div className="search-results">
          <h3>Search Results ({results.length})</h3>
          <div className="results-list">
            {results.map((item, index) => (
              <div
                key={index}
                className="result-item"
                onClick={() => {
                  if (searchType === 'jobs') {
                    navigate(`/job/${item.id}`);
                  } else {
                    navigate(`/user/${item.id}`);
                  }
                }}
                style={{ cursor: 'pointer' }}
              >
                {searchType === 'jobs' ? (
                  <>
                    <h4>{item.title}</h4>
                    <p>{item.company} - {item.location}</p>
                    <p>{item.description?.substring(0, 100)}...</p>
                    {item.salary && <p>Salary: ${item.salary}</p>}
                  </>
                ) : (
                  <>
                    <h4>{item.name}</h4>
                    <p>{item.email}</p>
                    <p>Type: {item.user_type}</p>
                    {item.profile && (
                      <div>
                        {item.profile.bio && <p>Bio: {item.profile.bio}</p>}
                        {item.profile.skills && item.profile.skills.length > 0 && (
                          <p>Skills: {item.profile.skills.join(', ')}</p>
                        )}
                        {item.profile.experience_level && (
                          <p>Experience: {item.profile.experience_level}</p>
                        )}
                      </div>
                    )}
                  </>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {query.trim() && !loading && results.length === 0 && (
        <div className="no-results">
          No {searchType} found for "{query}"
        </div>
      )}
    </div>
  );
}

export default SearchBar;
