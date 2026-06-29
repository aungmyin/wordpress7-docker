import React, { useState } from 'react'
import ProductCard from '../components/ProductCard'
import { useProducts } from '../hooks/useCart'

export default function CategoryPage({ categoryId }) {
  const [filterPrice, setFilterPrice] = useState('')
  const [sortBy, setSortBy] = useState('date')

  const params = {
    category: parseInt(categoryId),
    orderby: sortBy === 'price-asc' ? 'price' : sortBy === 'price-desc' ? 'price' : 'date',
    order: sortBy === 'price-asc' ? 'asc' : 'desc',
  }

  if (filterPrice === 'under-50') {
    params.min_price = 0
    params.max_price = 50
  } else if (filterPrice === '50-100') {
    params.min_price = 50
    params.max_price = 100
  } else if (filterPrice === 'over-100') {
    params.min_price = 100
  }

  const { products, loading, error } = useProducts(params)

  const getCategoryName = () => {
    const names = { 17: 'Electronics', 18: 'Office' }
    return names[categoryId] || 'Products'
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <a href="/" className="text-blue-100 hover:text-white text-sm mb-4 inline-block cursor-pointer">
            ← Back to Shop
          </a>
          <h1 className="text-5xl font-bold mb-4">{getCategoryName()}</h1>
          <p className="text-xl text-blue-100">
            Browse our {getCategoryName().toLowerCase()} collection
          </p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Filters & Sorting */}
        <div className="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Sort By */}
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">Sort By</label>
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="date">Newest</option>
              <option value="price-asc">Price: Low to High</option>
              <option value="price-desc">Price: High to Low</option>
            </select>
          </div>

          {/* Price Range */}
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-2">Price Range</label>
            <select
              value={filterPrice}
              onChange={(e) => setFilterPrice(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Prices</option>
              <option value="under-50">Under $50</option>
              <option value="50-100">$50 - $100</option>
              <option value="over-100">Over $100</option>
            </select>
          </div>

          {/* Result Count */}
          <div className="flex items-end">
            <div className="text-sm text-gray-600">
              Showing <strong>{products.length}</strong> products
            </div>
          </div>
        </div>

        {/* Error State */}
        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <p>Error loading products: {error}</p>
          </div>
        )}

        {/* Loading State */}
        {loading && (
          <div className="text-center py-12">
            <p className="text-gray-600">Loading products...</p>
          </div>
        )}

        {/* Empty State */}
        {!loading && products.length === 0 && !error && (
          <div className="text-center py-12">
            <p className="text-gray-600 mb-4">No products found in this category.</p>
            <a href="/" className="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg cursor-pointer">
              Back to Shop
            </a>
          </div>
        )}

        {/* Products Grid */}
        {!loading && products.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
