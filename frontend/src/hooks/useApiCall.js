import { useState } from 'react';
import axios from '../utils/axios';

export const useApiCall = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const apiCall = async (url, method = 'GET', data = null) => {
    setLoading(true);
    setError(null);
    try {
      const response = await axios({
        method,
        url,
        data,
      });
      setLoading(false);
      return response.data;
    } catch (err) {
      setLoading(false);
      setError(err.response?.data?.message || err.message);
      throw err;
    }
  };

  return { apiCall, loading, error };
};
