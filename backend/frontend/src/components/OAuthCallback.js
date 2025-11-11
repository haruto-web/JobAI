import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

function OAuthCallback() {
  const navigate = useNavigate();

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    const email = params.get('email');
    const name = params.get('name');
    const avatar = params.get('avatar');
    const provider = params.get('provider');

    const payload = { token, email, name, avatar, provider };

    // If opened as a popup by the main app, postMessage back and close
    if (window.opener && !window.opener.closed) {
      try {
        window.opener.postMessage({ type: 'oauth_callback', payload }, '*');
      } catch (err) {
        // ignore
      }
      // close the popup after a short delay to allow message to be delivered
      setTimeout(() => {
        window.close();
      }, 300);
      return;
    }

    // Otherwise, handle in the main window: if token present, store and navigate to dashboard
    if (token) {
      localStorage.setItem('token', token);
      // navigate to dashboard
      navigate('/dashboard', { replace: true });
      return;
    }

    // If email present (user needs to register), forward to register with params
    if (email) {
      const qs = new URLSearchParams({ email, name, avatar, provider }).toString();
      navigate(`/register?${qs}`, { replace: true });
      return;
    }

    // Fallback: go to home
    navigate('/', { replace: true });
  }, [navigate]);

  return (
    <div style={{ padding: 24, textAlign: 'center' }}>
      <h3>Completing sign-in...</h3>
      <p>If this page does not close automatically, you can close this window and continue in the main app.</p>
    </div>
  );
}

export default OAuthCallback;
