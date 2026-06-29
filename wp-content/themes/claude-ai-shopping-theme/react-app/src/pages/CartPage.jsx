import React, { useEffect } from 'react'

import { useCart } from '../hooks/useCart'

export default function CartPage() {
  const { items, total, count, loading, error, getCart, updateCart, removeFromCart } =
    useCart()

  useEffect(() => {
    getCart()
  }, [])

  if (loading && items.length === 0) {
    return (
      <div className="flex justify-center items-center py-16">
        <p className="text-gray-600">Loading cart...</p>
      </div>
    )
  }

  if (items.length === 0) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 className="text-3xl font-bold text-gray-800 mb-8">Shopping Cart</h1>
        <div className="bg-white rounded-lg shadow-md p-12 text-center">
          <svg
            className="w-16 h-16 text-gray-400 mx-auto mb-4"
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
          <h2 className="text-xl font-semibold text-gray-700 mb-2">Your cart is empty</h2>
          <p className="text-gray-600 mb-6">Start shopping to add items to your cart</p>
          <a href="/" className="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
            Continue Shopping
          </a>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 className="text-3xl font-bold text-gray-800 mb-8">Shopping Cart ({count})</h1>

        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-8">
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Cart Items */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
              {items.map((item) => (
                <div
                  key={item.key}
                  className="border-b last:border-b-0 p-6 flex gap-6 hover:bg-gray-50 transition"
                >
                  {/* Product Image */}
                  <div className="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                    {item.product_image ? (
                      <img
                        src={item.product_image}
                        alt={item.product_name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-400">
                        No Image
                      </div>
                    )}
                  </div>

                  {/* Product Info */}
                  <div className="flex-1">
                    <h3 className="font-semibold text-gray-800 mb-2">{item.product_name}</h3>
                    <p className="text-blue-600 font-bold">${parseFloat(item.price).toFixed(2)}</p>
                    <p className="text-sm text-gray-600 mt-1">
                      Subtotal: ${parseFloat(item.total).toFixed(2)}
                    </p>
                  </div>

                  {/* Quantity & Actions */}
                  <div className="flex items-center gap-4">
                    {/* Quantity Control */}
                    <div className="flex items-center border border-gray-300 rounded-lg">
                      <button
                        onClick={() =>
                          updateCart(item.key, Math.max(1, item.quantity - 1))
                        }
                        disabled={loading}
                        className="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                      >
                        −
                      </button>
                      <span className="px-4 py-1 font-semibold">{item.quantity}</span>
                      <button
                        onClick={() => updateCart(item.key, item.quantity + 1)}
                        disabled={loading}
                        className="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                      >
                        +
                      </button>
                    </div>

                    {/* Remove Button */}
                    <button
                      onClick={() => removeFromCart(item.key)}
                      disabled={loading}
                      className="text-red-600 hover:text-red-700 disabled:opacity-50"
                    >
                      <svg
                        className="w-5 h-5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                          clipRule="evenodd"
                        />
                      </svg>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Cart Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 sticky top-20">
              <h2 className="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>

              <div className="space-y-4 mb-6 border-b pb-6">
                <div className="flex justify-between text-gray-700">
                  <span>Subtotal</span>
                  <span>{total}</span>
                </div>
                <div className="flex justify-between text-gray-700">
                  <span>Shipping</span>
                  <span className="text-green-600 font-semibold">FREE</span>
                </div>
                <div className="flex justify-between text-gray-700">
                  <span>Tax</span>
                  <span>Calculated at checkout</span>
                </div>
              </div>

              <div className="flex justify-between items-center mb-6">
                <span className="text-lg font-bold text-gray-800">Total</span>
                <span className="text-2xl font-bold text-blue-600">{total}</span>
              </div>

              <a
                href="/checkout"
                className="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg text-center transition mb-3"
              >
                Proceed to Checkout
              </a>

              <a
                href="/"
                className="block w-full border border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold py-3 rounded-lg text-center transition"
              >
                Continue Shopping
              </a>

              <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                <p className="text-sm text-blue-900">
                  ✓ Free shipping on orders over $50
                </p>
                <p className="text-sm text-blue-900 mt-2">
                  ✓ 30-day return guarantee
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
