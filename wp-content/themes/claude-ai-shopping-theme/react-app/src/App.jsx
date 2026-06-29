import React from 'react'
import Navbar from './components/Navbar'
import Footer from './components/Footer'
import HomePage from './pages/HomePage'
import ProductPage from './pages/ProductPage'
import CategoryPage from './pages/CategoryPage'
import AboutPage from './pages/AboutPage'
import CartPage from './pages/CartPage'
import CheckoutPage from './pages/CheckoutPage'
import ContactPage from './pages/ContactPage'
import NotFound from './pages/NotFound'
import { CartProvider } from './hooks/useMockCart'

// Register Service Worker for caching
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/wp-content/themes/claude-ai-shopping-theme/sw.js')
      .catch((err) => console.warn('⚠️ Service worker registration failed:', err))
  })
}

// Simple path-based router (without React Router)
function getCurrentPage() {
  const path = window.location.pathname
  const searchParams = new URLSearchParams(window.location.search)

  // Home page
  if (path === '/' || path.endsWith('index.php/')) {
    return { page: 'home', params: { search: searchParams.get('search') } }
  }

  // Product page: /product/123
  const productMatch = path.match(/\/product\/(\d+)/)
  if (productMatch) {
    return { page: 'product', params: { id: productMatch[1] } }
  }

  // Category page: /category/123
  const categoryMatch = path.match(/\/category\/(\d+)/)
  if (categoryMatch) {
    return { page: 'category', params: { id: categoryMatch[1] } }
  }

  // Other pages
  if (path.includes('/about')) return { page: 'about', params: {} }
  if (path.includes('/cart')) return { page: 'cart', params: {} }
  if (path.includes('/checkout')) return { page: 'checkout', params: {} }
  if (path.includes('/contact')) return { page: 'contact', params: {} }

  return { page: 'notfound', params: {} }
}

function PageContent({ currentPage }) {
  switch (currentPage.page) {
    case 'home':
      return <HomePage searchQuery={currentPage.params.search} />
    case 'product':
      return <ProductPage productId={currentPage.params.id} />
    case 'category':
      return <CategoryPage categoryId={currentPage.params.id} />
    case 'about':
      return <AboutPage />
    case 'cart':
      return <CartPage />
    case 'checkout':
      return <CheckoutPage />
    case 'contact':
      return <ContactPage />
    default:
      return <NotFound />
  }
}

export default function App() {
  const currentPage = getCurrentPage()

  return (
    <CartProvider>
      <div className="flex flex-col min-h-screen bg-white">
        <Navbar />
        <main className="flex-1 bg-gray-50">
          <PageContent currentPage={currentPage} />
        </main>
        <Footer />
      </div>
    </CartProvider>
  )
}
