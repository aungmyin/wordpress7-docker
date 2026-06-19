import React, { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useCart } from '../hooks/useCart'

export default function Navbar() {
  const navigate = useNavigate()
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
  const cartCount = useCart((state) => state.count)

  const handleSearch = (e) => {
    e.preventDefault()
    if (searchQuery.trim()) {
      navigate(`/?search=${encodeURIComponent(searchQuery)}`)
      setSearchQuery('')
      setIsMobileMenuOpen(false)
    }
  }

  return (
    <nav className="bg-white shadow-md sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link to="/" className="flex items-center">
            <span className="text-2xl font-bold text-blue-600">Claude AI</span>
            <span className="text-sm text-gray-600 ml-1">Shopping</span>
          </Link>

          {/* Search Bar - Desktop */}
          <form onSubmit={handleSearch} className="hidden md:flex flex-1 mx-8">
            <div className="w-full">
              <input
                type="text"
                placeholder="Search products..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </form>

          {/* Menu Links & Cart */}
          <div className="flex items-center space-x-4">
            <Link
              to="/"
              className="text-gray-700 hover:text-blue-600 transition hidden sm:inline"
            >
              Home
            </Link>

            {/* Categories Dropdown */}
            <div className="relative group hidden sm:inline-block">
              <button className="text-gray-700 hover:text-blue-600 transition flex items-center">
                Categories
                <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
              </button>
              <div className="absolute hidden group-hover:block bg-white shadow-lg rounded-lg py-2 w-48 z-10">
                <Link
                  to="/category/17"
                  className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  Electronics
                </Link>
                <Link
                  to="/category/18"
                  className="block px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  Office
                </Link>
              </div>
            </div>

            <Link
              to="/about"
              className="text-gray-700 hover:text-blue-600 transition hidden sm:inline"
            >
              About
            </Link>

            <Link
              to="/contact"
              className="text-gray-700 hover:text-blue-600 transition hidden sm:inline"
            >
              Contact
            </Link>

            {/* Cart Icon */}
            <Link to="/cart" className="relative">
              <svg
                className="w-6 h-6 text-gray-700 hover:text-blue-600 transition"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              {cartCount > 0 && (
                <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {cartCount}
                </span>
              )}
            </Link>

            {/* Mobile Menu Button */}
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="md:hidden p-2"
            >
              <svg
                className="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {isMobileMenuOpen && (
          <div className="md:hidden pb-4">
            <form onSubmit={handleSearch} className="mb-4">
              <input
                type="text"
                placeholder="Search products..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </form>
            <Link to="/" onClick={() => setIsMobileMenuOpen(false)} className="block py-2 text-gray-700 hover:text-blue-600">
              Home
            </Link>
            <Link to="/about" onClick={() => setIsMobileMenuOpen(false)} className="block py-2 text-gray-700 hover:text-blue-600">
              About
            </Link>
            <Link to="/contact" onClick={() => setIsMobileMenuOpen(false)} className="block py-2 text-gray-700 hover:text-blue-600">
              Contact
            </Link>
            <div className="py-2 border-t">
              <p className="font-semibold text-gray-700 py-2">Categories</p>
              <Link
                to="/category/17"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block pl-4 py-2 text-gray-600 hover:text-blue-600"
              >
                Electronics
              </Link>
              <Link
                to="/category/18"
                onClick={() => setIsMobileMenuOpen(false)}
                className="block pl-4 py-2 text-gray-600 hover:text-blue-600"
              >
                Office
              </Link>
            </div>
          </div>
        )}
      </div>
    </nav>
  )
}
