import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'

export default function AboutPage() {
  const [page, setPage] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchPage = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/page/about`
        )
        if (!response.ok) {
          throw new Error('Page not found')
        }
        const data = await response.json()
        setPage(data)
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }

    fetchPage()
  }, [])

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-600">Loading...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 className="text-5xl font-bold mb-4">About</h1>
          </div>
        </div>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div className="bg-yellow-100 border border-yellow-400 text-yellow-800 px-6 py-4 rounded-lg">
            <p className="font-semibold mb-2">ℹ️ About Page Not Created</p>
            <p className="mb-3">To edit the About page, please create a WordPress page with:</p>
            <ul className="list-disc ml-5 space-y-1 mb-4">
              <li><strong>Title:</strong> "About" (or your preferred title)</li>
              <li><strong>Slug:</strong> "about"</li>
              <li><strong>Content:</strong> Add your about page content</li>
            </ul>
            <p className="text-sm">Once created and published, it will automatically appear here!</p>
          </div>
          <Link
            to="/"
            className="inline-block mt-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg"
          >
            Back to Shop
          </Link>
        </div>
      </div>
    )
  }

  if (!page) {
    return null
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-5xl font-bold mb-4">{page.title}</h1>
          <p className="text-xl text-blue-100">
            Learn more about our company
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="bg-white rounded-lg shadow-md p-8">
          {/* Display WordPress Page Content */}
          <div
            className="prose prose-lg max-w-none"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        </div>

        {/* CTA Section */}
        <div className="mt-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-md p-12 text-center">
          <h2 className="text-3xl font-bold mb-4">Ready to Shop?</h2>
          <p className="text-lg mb-8 text-blue-100">
            Explore our collection of premium products
          </p>
          <Link
            to="/"
            className="inline-block bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition"
          >
            Start Shopping Now
          </Link>
        </div>
      </div>
    </div>
  )
}
