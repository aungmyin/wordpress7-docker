import React from 'react'
import { useCart } from '../hooks/useCart'

export default function ProductCard({ product }) {
  const addToCart = useCart((state) => state.addToCart)
  const [isAdding, setIsAdding] = React.useState(false)

  const handleAddToCart = async (e) => {
    e.preventDefault()
    setIsAdding(true)
    try {
      await addToCart(product.id, 1)
      alert('Product added to cart!')
    } catch (error) {
      alert('Failed to add product to cart')
    } finally {
      setIsAdding(false)
    }
  }

  const imageUrl =
    product.images && product.images.length > 0
      ? product.images[0].src
      : 'https://via.placeholder.com/300x300?text=No+Image'

  const price = parseFloat(product.price).toFixed(2)
  const regularPrice = product.regular_price ? parseFloat(product.regular_price).toFixed(2) : null
  const discount =
    regularPrice && regularPrice > price
      ? Math.round((1 - price / regularPrice) * 100)
      : null

  return (
    <a href={`/product/${product.id}`} className="block cursor-pointer">
      <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        {/* Image Container */}
        <div className="relative bg-gray-100 overflow-hidden">
          <img
            src={imageUrl}
            alt={product.name}
            loading="lazy"
            className="w-full h-64 object-cover hover:scale-110 transition-transform duration-300"
          />
          {discount && (
            <div className="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-lg text-sm font-bold">
              -{discount}%
            </div>
          )}
          {!product.in_stock && (
            <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
              <span className="text-white font-bold text-lg">Out of Stock</span>
            </div>
          )}
        </div>

        {/* Product Info */}
        <div className="p-4">
          <h3 className="text-lg font-semibold text-gray-800 truncate">
            {product.name}
          </h3>

          {/* Price */}
          <div className="mt-2 flex items-baseline space-x-2">
            <span className="text-2xl font-bold text-blue-600">${price}</span>
            {regularPrice && regularPrice > price && (
              <span className="text-sm text-gray-500 line-through">
                ${regularPrice}
              </span>
            )}
          </div>

          {/* Rating */}
          {product.average_rating && (
            <div className="mt-2 flex items-center space-x-2">
              <div className="flex text-yellow-400">
                {[...Array(5)].map((_, i) => (
                  <span key={i} className="text-lg">
                    {i < Math.round(product.average_rating) ? '★' : '☆'}
                  </span>
                ))}
              </div>
              <span className="text-sm text-gray-600">
                ({product.review_count || 0})
              </span>
            </div>
          )}

          {/* Add to Cart Button */}
          <button
            onClick={handleAddToCart}
            disabled={!product.in_stock || isAdding}
            className="mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-2 rounded-lg transition-colors"
          >
            {isAdding ? 'Adding...' : product.in_stock ? 'Add to Cart' : 'Out of Stock'}
          </button>
        </div>
      </div>
    </a>
  )
}
