import React from 'react'
import Navbar from './components/Navbar'
import HomePage from './pages/HomePage'
import { CartProvider } from './hooks/useCart'

console.log('🎯 App.jsx loaded')

// Register Service Worker for caching
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/wp-content/themes/claude-ai-shopping-theme/sw.js')
      .catch((err) => console.warn('⚠️ Service worker registration failed:', err))
  })
}

export default function App() {
  return (
    <CartProvider>
      <div className="flex flex-col min-h-screen bg-white">
        <Navbar />
        <main className="flex-1 bg-gray-50">
          <HomePage />
        </main>
      </div>
    </CartProvider>
  )
}
