import React, { useState, useEffect } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import ProductCard from '../components/ProductCard'
import { useProducts } from '../hooks/useCart'

export default function HomePage() {
  const [searchParams] = useSearchParams()
  const searchQuery = searchParams.get('search') || ''

  const [page, setPage] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [categories, setCategories] = useState([])
  const [categoriesLoading, setCategoriesLoading] = useState(true)

  const { products, loading: productsLoading } = useProducts({
    per_page: 12,
    search: searchQuery,
  })

  useEffect(() => {
    const fetchPage = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/page/home`
        )
        if (!response.ok) {
          throw new Error('Home page not found')
        }
        const data = await response.json()
        setPage(data)
      } catch (err) {
        setError(err.message)
        console.error('Error fetching home page:', err)
      } finally {
        setLoading(false)
      }
    }

    const fetchCategories = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/categories`
        )
        if (!response.ok) {
          throw new Error('Categories not found')
        }
        const data = await response.json()
        setCategories(data)
      } catch (err) {
        console.error('Error fetching categories:', err)
      } finally {
        setCategoriesLoading(false)
      }
    }

    fetchPage()
    fetchCategories()
  }, [])

  // Extract first paragraph as subtitle
  const getSubtitle = (content) => {
    const match = content.match(/<p>(.*?)<\/p>/)
    return match ? match[1] : ''
  }

  // Extract main heading as title
  const getTitle = (content) => {
    const match = content.match(/<h1>(.*?)<\/h1>/)
    return match ? match[1] : 'Welcome'
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-600">Loading...</p>
      </div>
    )
  }

  const homeTitle = page ? getTitle(page.content) : 'Welcome to Claude AI Shopping'
  const homeSubtitle = page ? getSubtitle(page.content) : 'Discover amazing products'

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Banner - Editable from WordPress */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-5xl md:text-6xl font-bold mb-6">
              {homeTitle}
            </h1>
            <p className="text-xl md:text-2xl text-blue-100 mb-8 max-w-2xl mx-auto">
              {homeSubtitle}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/"
                className="inline-block bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition"
              >
                Shop Now
              </Link>
              <Link
                to="/about"
                className="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition"
              >
                Learn More
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        {/* Features Section - Hidden during search */}
        {!searchQuery && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
          <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">Premium Quality</h3>
            <p className="text-gray-600">Carefully curated products that meet our high standards</p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">Best Prices</h3>
            <p className="text-gray-600">Competitive pricing with frequent discounts and deals</p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-3">Expert Support</h3>
            <p className="text-gray-600">Dedicated customer support ready to help anytime</p>
          </div>
        </div>
        )}

        {/* Featured Products / Search Results Section */}
        <div className="mb-16">
          {searchQuery ? (
            <>
              <h2 className="text-4xl font-bold text-gray-800 mb-2 text-center">
                Search Results for "{searchQuery}"
              </h2>
              <p className="text-gray-600 text-center mb-12">
                {products.length > 0
                  ? `Found ${products.length} product${products.length !== 1 ? 's' : ''}`
                  : 'No products found matching your search'}
              </p>
            </>
          ) : (
            <>
              <h2 className="text-4xl font-bold text-gray-800 mb-2 text-center">Featured Products</h2>
              <p className="text-gray-600 text-center mb-12">Discover our best-selling items</p>
            </>
          )}

          {productsLoading ? (
            <div className="text-center py-12">
              <p className="text-gray-600">Loading products...</p>
            </div>
          ) : products.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-600">No products available</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
              {products.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}

          <div className="text-center space-x-4">
            {searchQuery && (
              <Link
                to="/"
                className="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-lg transition"
              >
                ← Back to Home
              </Link>
            )}
            {!searchQuery && (
              <Link
                to="/"
                className="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition"
              >
                View All Products
              </Link>
            )}
          </div>
        </div>

        {/* Categories Section - Hidden during search */}
        {!searchQuery && (
        <div className="bg-white rounded-lg shadow-md p-12 mb-16">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">Shop by Category</h2>

          {categoriesLoading ? (
            <div className="text-center py-8">
              <p className="text-gray-600">Loading categories...</p>
            </div>
          ) : categories.length === 0 ? (
            <div className="text-center py-8">
              <p className="text-gray-600">No categories available</p>
            </div>
          ) : (
            <div className={`grid gap-6 ${categories.length <= 2 ? 'md:grid-cols-2' : categories.length <= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-3'}`}>
              {categories.map((category, index) => {
                const colors = [
                  'from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700',
                  'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700',
                  'from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700',
                  'from-pink-500 to-pink-600 hover:from-pink-600 hover:to-pink-700',
                  'from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700',
                  'from-red-500 to-red-600 hover:from-red-600 hover:to-red-700',
                ]
                const colorClass = colors[index % colors.length]

                const icons = [
                  'M13 10V3L4 14h7v7l9-11h-7z',
                  'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                  'M3 12a9 9 0 018.9-9 9 9 0 1-8.9 9z',
                  'M7 16a4 4 0 11-8 0 4 4 0 018 0zM16 8a2 2 0 11-4 0 2 2 0 014 0z',
                  'M12 6.253v13m0-13C6.596 6.253 2 10.849 2 16.5S6.596 26.747 12 26.747s10-4.596 10-10.247S17.404 6.253 12 6.253z',
                  'M12 6.253v13m0-13C6.596 6.253 2 10.849 2 16.5S6.596 26.747 12 26.747s10-4.596 10-10.247S17.404 6.253 12 6.253z',
                ]
                const iconPath = icons[index % icons.length]

                return (
                  <Link
                    key={category.id}
                    to={`/category/${category.id}`}
                    className={`bg-gradient-to-r ${colorClass} text-white p-8 rounded-lg text-center transition transform hover:scale-105`}
                  >
                    <svg className="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={iconPath} />
                    </svg>
                    <h3 className="text-2xl font-bold">{category.name}</h3>
                    {category.description && (
                      <p className="opacity-90 mt-2">{category.description}</p>
                    )}
                    <p className="text-sm opacity-75 mt-1">({category.count} products)</p>
                  </Link>
                )
              })}
            </div>
          )}
        </div>
        )}

        {/* Newsletter Section - Hidden during search */}
        {!searchQuery && (
        <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg shadow-md p-12 text-center">
          <h2 className="text-3xl font-bold mb-4">Stay Updated</h2>
          <p className="text-lg mb-6 max-w-2xl mx-auto">
            Subscribe to our newsletter for exclusive deals and new product announcements
          </p>
          <div className="flex flex-col sm:flex-row gap-3 justify-center max-w-md mx-auto">
            <input
              type="email"
              placeholder="Enter your email"
              className="flex-1 px-4 py-3 rounded-lg text-gray-800 focus:outline-none"
            />
            <button className="bg-white text-purple-600 hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition">
              Subscribe
            </button>
          </div>
        </div>
        )}
      </div>
    </div>
  )
}
