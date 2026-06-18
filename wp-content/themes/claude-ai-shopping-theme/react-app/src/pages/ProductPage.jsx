import React from 'react'
import { useParams } from 'react-router-dom'
import { useProduct, useCart } from '../hooks/useCart'

export default function ProductPage() {
  const { id } = useParams()
  const { product, loading, error } = useProduct(id)
  const addToCart = useCart((state) => state.addToCart)
  const [quantity, setQuantity] = React.useState(1)
  const [isAdding, setIsAdding] = React.useState(false)

  const handleAddToCart = async () => {
    setIsAdding(true)
    try {
      await addToCart(product.id, quantity)
      alert('Product added to cart!')
      setQuantity(1)
    } catch (error) {
      alert('Failed to add product to cart')
    } finally {
      setIsAdding(false)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center py-16">
        <p className="text-gray-600">Loading product...</p>
      </div>
    )
  }

  if (error || !product) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
          <p>Product not found</p>
        </div>
      </div>
    )
  }

  const price = parseFloat(product.price).toFixed(2)
  const imageUrl =
    product.images && product.images.length > 0
      ? product.images[0].src
      : 'https://via.placeholder.com/500x500?text=No+Image'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Product Image */}
        <div>
          <img
            src={imageUrl}
            alt={product.name}
            className="w-full h-auto rounded-lg shadow-lg"
          />
        </div>

        {/* Product Details */}
        <div>
          <h1 className="text-3xl font-bold text-gray-800 mb-4">{product.name}</h1>

          {/* Price */}
          <div className="mb-6">
            <span className="text-4xl font-bold text-blue-600">${price}</span>
            {product.regular_price && parseFloat(product.regular_price) > parseFloat(price) && (
              <span className="text-lg text-gray-500 line-through ml-3">
                ${parseFloat(product.regular_price).toFixed(2)}
              </span>
            )}
          </div>

          {/* Rating */}
          {product.average_rating && (
            <div className="mb-6 flex items-center space-x-2">
              <div className="flex text-yellow-400">
                {[...Array(5)].map((_, i) => (
                  <span key={i} className="text-xl">
                    {i < Math.round(product.average_rating) ? '★' : '☆'}
                  </span>
                ))}
              </div>
              <span className="text-gray-600">
                ({product.review_count || 0} reviews)
              </span>
            </div>
          )}

          {/* Description */}
          {product.description && (
            <div className="mb-6">
              <h2 className="text-xl font-semibold text-gray-800 mb-2">Description</h2>
              <p className="text-gray-700 whitespace-pre-wrap">{product.description}</p>
            </div>
          )}

          {/* Stock Status */}
          <div className="mb-6">
            {product.in_stock ? (
              <p className="text-green-600 font-semibold">✓ In Stock</p>
            ) : (
              <p className="text-red-600 font-semibold">Out of Stock</p>
            )}
          </div>

          {/* Quantity & Add to Cart */}
          {product.in_stock && (
            <div className="mb-6 flex items-center space-x-4">
              <div className="flex items-center border border-gray-300 rounded-lg">
                <button
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                  className="px-4 py-2 text-gray-600 hover:bg-gray-100"
                >
                  −
                </button>
                <span className="px-6 py-2 font-semibold">{quantity}</span>
                <button
                  onClick={() => setQuantity(quantity + 1)}
                  className="px-4 py-2 text-gray-600 hover:bg-gray-100"
                >
                  +
                </button>
              </div>
              <button
                onClick={handleAddToCart}
                disabled={isAdding}
                className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 rounded-lg transition"
              >
                {isAdding ? 'Adding...' : 'Add to Cart'}
              </button>
            </div>
          )}

          {/* Shipping Info */}
          <div className="border-t border-gray-200 pt-6">
            <div className="space-y-3 text-sm text-gray-700">
              <div className="flex items-start">
                <span className="font-semibold mr-3">✓</span>
                <span>Free shipping on orders over $50</span>
              </div>
              <div className="flex items-start">
                <span className="font-semibold mr-3">✓</span>
                <span>30-day money back guarantee</span>
              </div>
              <div className="flex items-start">
                <span className="font-semibold mr-3">✓</span>
                <span>Secure checkout with multiple payment options</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
