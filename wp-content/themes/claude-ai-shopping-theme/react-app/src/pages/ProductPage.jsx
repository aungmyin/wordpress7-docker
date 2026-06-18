import React, { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useProduct, useProducts, useCart } from '../hooks/useCart'

export default function ProductPage() {
  const { id } = useParams()
  const { product, loading, error } = useProduct(id)
  const { products: relatedProducts } = useProducts({ per_page: 4 })
  const addToCart = useCart((state) => state.addToCart)
  const [quantity, setQuantity] = useState(1)
  const [isAdding, setIsAdding] = useState(false)
  const [selectedImage, setSelectedImage] = useState(0)

  const handleAddToCart = async () => {
    setIsAdding(true)
    try {
      await addToCart(product.id, quantity)
      alert('✓ Product added to cart!')
      setQuantity(1)
    } catch (error) {
      alert('Failed to add product to cart')
    } finally {
      setIsAdding(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <p className="text-gray-600">Loading product...</p>
      </div>
    )
  }

  if (error || !product) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <p>Product not found</p>
            <Link to="/" className="text-red-600 hover:text-red-800 mt-4 inline-block">
              ← Back to Shopping
            </Link>
          </div>
        </div>
      </div>
    )
  }

  const price = parseFloat(product.price).toFixed(2)
  const regularPrice = product.regular_price ? parseFloat(product.regular_price).toFixed(2) : null
  const discount = regularPrice && regularPrice > price ? Math.round(((regularPrice - price) / regularPrice) * 100) : null
  const imageUrl = 'https://via.placeholder.com/500x500?text=' + encodeURIComponent(product.name)

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Breadcrumb */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center space-x-2 text-sm text-gray-600">
            <Link to="/" className="hover:text-blue-600">Home</Link>
            <span>/</span>
            {product.categories && product.categories.length > 0 ? (
              <>
                <Link to={`/category/${product.categories[0].id}`} className="hover:text-blue-600">
                  {product.categories[0].name}
                </Link>
                <span>/</span>
              </>
            ) : null}
            <span className="text-gray-800 font-semibold">{product.name}</span>
          </div>
        </div>
      </div>

      {/* Product Details */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
          {/* Product Images */}
          <div>
            <div className="relative mb-4">
              <img
                src={imageUrl}
                alt={product.name}
                className="w-full h-auto rounded-lg shadow-lg bg-gray-100"
              />
              {discount && (
                <div className="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-lg font-bold">
                  -{discount}%
                </div>
              )}
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setSelectedImage(0)}
                className="w-20 h-20 bg-gray-200 rounded border-2 border-gray-300 flex items-center justify-center text-sm hover:border-blue-500"
              >
                <img src={imageUrl} alt="thumbnail" className="w-16 h-16 object-cover" />
              </button>
            </div>
          </div>

          {/* Product Info */}
          <div>
            {/* Title & Rating */}
            <h1 className="text-4xl font-bold text-gray-800 mb-2">{product.name}</h1>

            {product.average_rating ? (
              <div className="flex items-center space-x-2 mb-6">
                <div className="flex text-yellow-400">
                  {[...Array(5)].map((_, i) => (
                    <span key={i} className="text-lg">
                      {i < Math.round(product.average_rating) ? '★' : '☆'}
                    </span>
                  ))}
                </div>
                <span className="text-gray-600 text-sm">
                  ({product.review_count || 0} reviews)
                </span>
              </div>
            ) : null}

            {/* Price */}
            <div className="mb-6 pb-6 border-b">
              <div className="flex items-center space-x-4">
                <span className="text-5xl font-bold text-blue-600">${price}</span>
                {regularPrice && regularPrice > price && (
                  <span className="text-2xl text-gray-400 line-through">${regularPrice}</span>
                )}
              </div>
              {discount && (
                <p className="text-green-600 font-semibold mt-2">Save ${(regularPrice - price).toFixed(2)} ({discount}% off)</p>
              )}
            </div>

            {/* Stock Status */}
            <div className="mb-6 pb-6 border-b">
              <div className="flex items-center space-x-2">
                {product.in_stock ? (
                  <>
                    <span className="text-green-600 text-2xl">✓</span>
                    <div>
                      <p className="text-green-600 font-bold text-lg">In Stock</p>
                      {product.stock_quantity && (
                        <p className="text-gray-600 text-sm">{product.stock_quantity} units available</p>
                      )}
                    </div>
                  </>
                ) : (
                  <>
                    <span className="text-red-600 text-2xl">✗</span>
                    <p className="text-red-600 font-bold text-lg">Out of Stock</p>
                  </>
                )}
              </div>
            </div>

            {/* SKU */}
            {product.sku && (
              <div className="mb-6">
                <p className="text-gray-600 text-sm">
                  <span className="font-semibold">SKU:</span> {product.sku}
                </p>
              </div>
            )}

            {/* Quantity & Add to Cart */}
            {product.in_stock && (
              <div className="mb-8 space-y-4">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center border-2 border-gray-300 rounded-lg">
                    <button
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      className="px-5 py-3 text-gray-600 hover:bg-gray-100 text-xl font-semibold"
                    >
                      −
                    </button>
                    <span className="px-8 py-3 font-bold text-lg">{quantity}</span>
                    <button
                      onClick={() => setQuantity(quantity + 1)}
                      className="px-5 py-3 text-gray-600 hover:bg-gray-100 text-xl font-semibold"
                    >
                      +
                    </button>
                  </div>
                  <button
                    onClick={handleAddToCart}
                    disabled={isAdding}
                    className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-4 rounded-lg transition text-lg"
                  >
                    {isAdding ? '⏳ Adding...' : '🛒 Add to Cart'}
                  </button>
                </div>
                <button className="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-4 rounded-lg transition">
                  💔 Add to Wishlist
                </button>
              </div>
            )}

            {/* Product Info Cards */}
            <div className="space-y-3 bg-gray-50 p-6 rounded-lg">
              <h3 className="font-bold text-gray-800 mb-4">Why Choose This Product?</h3>
              <div className="flex items-start space-x-3">
                <span className="text-2xl">📦</span>
                <div>
                  <p className="font-semibold text-gray-800">Free Shipping</p>
                  <p className="text-gray-600 text-sm">On orders over $50</p>
                </div>
              </div>
              <div className="flex items-start space-x-3">
                <span className="text-2xl">💰</span>
                <div>
                  <p className="font-semibold text-gray-800">Money Back Guarantee</p>
                  <p className="text-gray-600 text-sm">30 days, no questions asked</p>
                </div>
              </div>
              <div className="flex items-start space-x-3">
                <span className="text-2xl">🔒</span>
                <div>
                  <p className="font-semibold text-gray-800">Secure Checkout</p>
                  <p className="text-gray-600 text-sm">Multiple payment options</p>
                </div>
              </div>
              <div className="flex items-start space-x-3">
                <span className="text-2xl">💬</span>
                <div>
                  <p className="font-semibold text-gray-800">Expert Support</p>
                  <p className="text-gray-600 text-sm">24/7 customer service</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Description Section */}
        {product.description && (
          <div className="mt-16 bg-white rounded-lg shadow-md p-8">
            <h2 className="text-3xl font-bold text-gray-800 mb-6">Product Description</h2>
            <p className="text-gray-700 whitespace-pre-wrap leading-relaxed text-lg">
              {product.description}
            </p>
          </div>
        )}

        {/* Related Products */}
        {relatedProducts.length > 0 && (
          <div className="mt-16">
            <h2 className="text-3xl font-bold text-gray-800 mb-8">Related Products</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {relatedProducts.slice(0, 4).map((relProduct) => (
                <Link
                  key={relProduct.id}
                  to={`/product/${relProduct.id}`}
                  className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition"
                >
                  <div className="bg-gray-100 h-48 flex items-center justify-center">
                    <img
                      src={'https://via.placeholder.com/300x300?text=' + encodeURIComponent(relProduct.name)}
                      alt={relProduct.name}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  <div className="p-4">
                    <h3 className="font-semibold text-gray-800 text-sm line-clamp-2">{relProduct.name}</h3>
                    <p className="text-blue-600 font-bold mt-2">${parseFloat(relProduct.price).toFixed(2)}</p>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
