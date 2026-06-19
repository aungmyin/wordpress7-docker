import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'

export default function ContactPage() {
  const [page, setPage] = useState(null)
  const [pageLoading, setPageLoading] = useState(true)
  const [pageError, setPageError] = useState(null)
  const [contactInfo, setContactInfo] = useState({
    email: 'support@claudeai.shop',
    phone: '+1 (234) 567-890',
    address: '123 Shopping Street, Commerce City, CC 12345',
  })

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
  })
  const [loading, setLoading] = useState(false)
  const [submitted, setSubmitted] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    const fetchPage = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/wp/v2/pages?slug=contact`
        )
        if (!response.ok) {
          throw new Error('Contact page not found')
        }
        const data = await response.json()
        if (data.length > 0) {
          const contactPage = data[0]
          setPage({
            title: contactPage.title.rendered,
            content: contactPage.content.rendered,
          })
          setContactInfo({
            email: contactPage.meta?.contact_email || 'support@claudeai.shop',
            phone: contactPage.meta?.contact_phone || '+1 (234) 567-890',
            address: contactPage.meta?.contact_address || '123 Shopping Street, Commerce City, CC 12345',
          })
        }
      } catch (err) {
        setPageError(err.message)
      } finally {
        setPageLoading(false)
      }
    }

    fetchPage()
  }, [])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      const response = await fetch(
        `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/contact`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
          },
          body: JSON.stringify(formData),
        }
      )

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to send message')
      }

      const result = await response.json()
      setSubmitted(true)
      setFormData({ name: '', email: '', phone: '', subject: '', message: '' })

      setTimeout(() => {
        setSubmitted(false)
      }, 5000)
    } catch (err) {
      setError(err.message || 'Failed to send your message. Please try again.')
      console.error('Contact form error:', err)
    } finally {
      setLoading(false)
    }
  }

  if (pageLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-600">Loading...</p>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-5xl font-bold mb-4">{page?.title || 'Contact Us'}</h1>
          <p className="text-xl text-blue-100">
            Have questions? We'd love to hear from you!
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        {/* Contact Info Cards - 3 Columns */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
          <div className="bg-white rounded-lg shadow-md p-8 text-center">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-2">Email</h3>
            <p className="text-gray-600">
              <a href={`mailto:${contactInfo.email}`} className="text-blue-600 hover:text-blue-800">
                {contactInfo.email}
              </a>
            </p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-8 text-center">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-2">Phone</h3>
            <p className="text-gray-600">
              <a href={`tel:${contactInfo.phone.replace(/\D/g, '')}`} className="text-blue-600 hover:text-blue-800">
                {contactInfo.phone}
              </a>
            </p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-8 text-center">
            <div className="flex justify-center mb-4">
              <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 className="text-xl font-bold text-gray-800 mb-2">Location</h3>
            <p className="text-gray-600 whitespace-pre-line">
              {contactInfo.address}
            </p>
          </div>
        </div>

        {/* Google Map */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">Our Office Location</h2>
          <div className="bg-white rounded-lg shadow-md overflow-hidden">
            <iframe
              title="Office Location Map"
              width="100%"
              height="450"
              style={{ border: 0 }}
              loading="lazy"
              allowFullScreen=""
              referrerPolicy="no-referrer-when-downgrade"
              src={`https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.1234567890!2d-74.0059!3d40.7128!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s${encodeURIComponent(contactInfo.address)}!2s${encodeURIComponent(contactInfo.address)}!5e0!3m2!1sen!2sus!4v1234567890`}
            />
          </div>
          <p className="text-center text-gray-600 mt-4">
            <a
              href={`https://maps.google.com/?q=${encodeURIComponent(contactInfo.address)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="text-blue-600 hover:text-blue-800 underline"
            >
              Open in Google Maps →
            </a>
          </p>
        </div>

        {/* Contact Form */}
        <div className="bg-white rounded-lg shadow-md p-8 max-w-2xl mx-auto">
          <h2 className="text-3xl font-bold text-gray-800 mb-8">Send us an Enquiry</h2>

          {submitted && (
            <div className="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
              <p className="font-semibold">✓ Thank you for your message!</p>
              <p className="text-sm">We'll get back to you as soon as possible.</p>
            </div>
          )}

          {error && (
            <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
              <p className="font-semibold">✗ Error</p>
              <p className="text-sm">{error}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label htmlFor="name" className="block text-gray-700 font-semibold mb-2">
                  Full Name *
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="John Doe"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-gray-700 font-semibold mb-2">
                  Email Address *
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="john@example.com"
                />
              </div>
            </div>

            <div>
              <label htmlFor="phone" className="block text-gray-700 font-semibold mb-2">
                Phone Number
              </label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="+1 (234) 567-890"
              />
            </div>

            <div>
              <label htmlFor="subject" className="block text-gray-700 font-semibold mb-2">
                Subject *
              </label>
              <input
                type="text"
                id="subject"
                name="subject"
                value={formData.subject}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="How can we help?"
              />
            </div>

            <div>
              <label htmlFor="message" className="block text-gray-700 font-semibold mb-2">
                Message *
              </label>
              <textarea
                id="message"
                name="message"
                value={formData.message}
                onChange={handleChange}
                required
                rows="6"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                placeholder="Tell us more about your enquiry..."
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 rounded-lg transition text-lg"
            >
              {loading ? '⏳ Sending...' : '📧 Send Enquiry'}
            </button>
          </form>
        </div>

        {/* CTA Section */}
        <div className="mt-16 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-md p-12 text-center">
          <h2 className="text-3xl font-bold mb-4">Prefer to Browse First?</h2>
          <p className="text-lg mb-8 text-blue-100 max-w-2xl mx-auto">
            Explore our collection of premium products and discover what's perfect for you
          </p>
          <Link
            to="/"
            className="inline-block bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition"
          >
            Continue Shopping
          </Link>
        </div>
      </div>
    </div>
  )
}
