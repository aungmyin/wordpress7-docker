import React, { useState, useEffect } from 'react'


export default function AboutPage() {
  const [page, setPage] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchPage = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/wp/v2/pages?slug=about`
        )
        if (!response.ok) {
          throw new Error('Page not found')
        }
        const data = await response.json()
        if (data.length > 0) {
          const pageData = data[0]
          setPage({
            title: pageData.title.rendered,
            content: pageData.content.rendered,
            heroTitle: pageData.meta?.about_hero_title || pageData.title.rendered,
            heroSubtitle: pageData.meta?.about_hero_subtitle || 'Learn more about our company',
            mission: pageData.meta?.about_mission || '',
            vision: pageData.meta?.about_vision || '',
            values: pageData.meta?.about_values || '',
          })
        }
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
            <p className="text-sm">Once created and published, scroll down to "About Page Sections" to add Mission, Vision, and Values!</p>
          </div>
          <a
            href="/"
            className="inline-block mt-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg"
          >
            Back to Shop
          </a>
        </div>
      </div>
    )
  }

  if (!page) {
    return null
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section - Using Custom Fields */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-5xl font-bold mb-4">{page.heroTitle}</h1>
          <p className="text-xl text-blue-100">
            {page.heroSubtitle}
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="bg-white rounded-lg shadow-md p-8 mb-12">
          {/* Display WordPress Page Content */}
          <div
            className="prose prose-lg max-w-none"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        </div>

        {/* 3-Column Section: Mission, Vision, Values */}
        {(page.mission || page.vision || page.values) && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            {/* Mission Card */}
            {page.mission && (
              <div className="bg-white rounded-lg shadow-md p-8">
                <div className="flex justify-center mb-4">
                  <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h3 className="text-2xl font-bold text-gray-800 mb-4 text-center">Our Mission</h3>
                <p className="text-gray-600 text-center whitespace-pre-wrap leading-relaxed">
                  {page.mission}
                </p>
              </div>
            )}

            {/* Vision Card */}
            {page.vision && (
              <div className="bg-white rounded-lg shadow-md p-8">
                <div className="flex justify-center mb-4">
                  <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </div>
                <h3 className="text-2xl font-bold text-gray-800 mb-4 text-center">Our Vision</h3>
                <p className="text-gray-600 text-center whitespace-pre-wrap leading-relaxed">
                  {page.vision}
                </p>
              </div>
            )}

            {/* Values Card */}
            {page.values && (
              <div className="bg-white rounded-lg shadow-md p-8">
                <div className="flex justify-center mb-4">
                  <svg className="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 className="text-2xl font-bold text-gray-800 mb-4 text-center">Our Values</h3>
                <p className="text-gray-600 text-center whitespace-pre-wrap leading-relaxed">
                  {page.values}
                </p>
              </div>
            )}
          </div>
        )}

        {/* CTA Section */}
        <div className="mt-12 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-md p-12 text-center">
          <h2 className="text-3xl font-bold mb-4">Ready to Shop?</h2>
          <p className="text-lg mb-8 text-blue-100">
            Explore our collection of premium products
          </p>
          <a
            href="/"
            className="inline-block bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition"
          >
            Start Shopping Now
          </a>
        </div>
      </div>
    </div>
  )
}
