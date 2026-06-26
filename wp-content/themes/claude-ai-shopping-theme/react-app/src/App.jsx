import React from 'react'
import Navbar from './components/Navbar'

// Register Service Worker for caching
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/wp-content/themes/claude-ai-shopping-theme/sw.js')
      .catch((err) => console.warn('⚠️ Service worker registration failed:', err))
  })
}

export default function App() {
  return React.createElement('div', { style: { minHeight: '100vh', background: '#fff' } },
    React.createElement(Navbar, null),
    React.createElement('div', { style: { padding: '20px', background: '#2196F3', color: 'white' } },
      React.createElement('h1', null, 'TEST WITH NAVBAR'),
      React.createElement('p', null, 'If Navbar renders, we can see it.')
    )
  )
}
