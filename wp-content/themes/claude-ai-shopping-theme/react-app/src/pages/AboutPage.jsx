import React from 'react'
import { Link } from 'react-router-dom'

export default function AboutPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-5xl font-bold mb-4">About Claude AI Shopping</h1>
          <p className="text-xl text-blue-100">
            Discover our story, mission, and commitment to excellence
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        {/* Company Story */}
        <div className="mb-16">
          <div className="bg-white rounded-lg shadow-md p-8">
            <h2 className="text-3xl font-bold text-gray-800 mb-6">Our Story</h2>
            <p className="text-gray-700 mb-4 leading-relaxed">
              Claude AI Shopping was founded with a simple vision: to provide customers with
              the best quality products at competitive prices. We believe that online shopping
              should be easy, enjoyable, and rewarding.
            </p>
            <p className="text-gray-700 mb-4 leading-relaxed">
              Starting as a small startup, we've grown into a trusted online retailer serving
              thousands of customers worldwide. Our commitment to quality, customer service,
              and innovation has been the driving force behind our success.
            </p>
            <p className="text-gray-700 leading-relaxed">
              Today, we're proud to offer a curated selection of products across multiple
              categories, all backed by our guarantee of customer satisfaction.
            </p>
          </div>
        </div>

        {/* Mission & Values */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
          {/* Mission */}
          <div className="bg-white rounded-lg shadow-md p-8">
            <div className="flex items-center mb-4">
              <div className="flex-shrink-0">
                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                  <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 ml-4">Our Mission</h3>
            </div>
            <p className="text-gray-700 leading-relaxed">
              To empower customers with access to high-quality products, exceptional customer
              service, and a seamless shopping experience that exceeds expectations.
            </p>
          </div>

          {/* Values */}
          <div className="bg-white rounded-lg shadow-md p-8">
            <div className="flex items-center mb-4">
              <div className="flex-shrink-0">
                <div className="flex items-center justify-center h-12 w-12 rounded-md bg-green-500 text-white">
                  <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 ml-4">Our Values</h3>
            </div>
            <p className="text-gray-700 leading-relaxed">
              Quality, integrity, and customer-first thinking guide everything we do. We believe
              in transparency, fair pricing, and building long-term relationships with our customers.
            </p>
          </div>
        </div>

        {/* Why Choose Us */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">Why Choose Us?</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Quality Products */}
            <div className="bg-white rounded-lg shadow-md p-8 text-center">
              <div className="flex justify-center mb-4">
                <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Premium Quality</h3>
              <p className="text-gray-600">
                We carefully curate every product to ensure it meets our high standards for
                quality and durability.
              </p>
            </div>

            {/* Best Prices */}
            <div className="bg-white rounded-lg shadow-md p-8 text-center">
              <div className="flex justify-center mb-4">
                <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Competitive Prices</h3>
              <p className="text-gray-600">
                We offer the best value for your money with frequent discounts and exclusive
                deals for our customers.
              </p>
            </div>

            {/* Customer Service */}
            <div className="bg-white rounded-lg shadow-md p-8 text-center">
              <div className="flex justify-center mb-4">
                <svg className="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Expert Support</h3>
              <p className="text-gray-600">
                Our dedicated customer support team is ready to help you with any questions or
                concerns, anytime.
              </p>
            </div>
          </div>
        </div>

        {/* Team Section */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">Our Team</h2>
          <div className="bg-white rounded-lg shadow-md p-8">
            <p className="text-gray-700 mb-4 leading-relaxed">
              Our team consists of passionate professionals dedicated to bringing you the best
              shopping experience. From product sourcing to customer service, every member of our
              team is committed to excellence.
            </p>
            <p className="text-gray-700 mb-4 leading-relaxed">
              We believe in continuous improvement and regularly seek feedback from our customers
              to enhance our services and offerings.
            </p>
            <div className="mt-6 p-4 bg-blue-50 border-l-4 border-blue-600 rounded">
              <p className="text-blue-800 font-semibold">
                💡 Did you know? Our platform uses AI-powered recommendations to help you find
                products tailored to your preferences!
              </p>
            </div>
          </div>
        </div>

        {/* CTA Section */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-md p-12 text-center">
          <h2 className="text-3xl font-bold mb-4">Ready to Shop?</h2>
          <p className="text-lg mb-8 text-blue-100">
            Explore our collection of premium products across Electronics and Office categories
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
