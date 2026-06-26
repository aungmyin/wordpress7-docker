import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { useCart } from '../hooks/useCart'

export default function CartDrawer({ isOpen, onClose }) {
  const cartState = useCart()
  const { items, total, count, loading, removeFromCart, updateCart } = cartState
  const [updatingKey, setUpdatingKey] = useState(null)

  const handleQuantityChange = async (key, newQuantity) => {
    if (newQuantity < 1) {
      handleRemove(key)
      return
    }
    setUpdatingKey(key)
    try {
      await updateCart(key, newQuantity)
    } catch (error) {
      console.error('Failed to update quantity:', error)
    } finally {
      setUpdatingKey(null)
    }
  }

  const handleRemove = async (key) => {
    try {
      await removeFromCart(key)
    } catch (error) {
      console.error('Failed to remove item:', error)
    }
  }

  return (
    <>
      {/* Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40"
          onClick={onClose}
        />
      )}

      {/* Drawer */}
      <div
        className={`fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-lg z-50 transform transition-transform duration-300 ${
          isOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        {/* Header */}
        <div className="border-b px-6 py-4 flex justify-between items-center">
          <h2 className="text-2xl font-bold text-gray-800">Shopping Cart</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ✕
          </button>
        </div>

        {/* Cart Items */}
        <div className="flex-1 overflow-y-auto" style={{ height: 'calc(100% - 200px)' }}>
          {loading && !items.length && (
            <div className="flex items-center justify-center h-32">
              <p className="text-gray-500">Loading cart...</p>
            </div>
          )}

          {!loading && items.length === 0 && (
            <div className="flex flex-col items-center justify-center h-32 text-gray-500">
              <svg
                className="w-16 h-16 mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={1.5}
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              <p>Your cart is empty</p>
            </div>
          )}

          {items.map((item) => (
            <div
              key={item.key}
              className="border-b px-6 py-4 flex gap-4"
            >
              {/* Product Image */}
              <div className="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                {item.product_image ? (
                  <img
                    src={item.product_image}
                    alt={item.product_name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-gray-400">
                    📦
                  </div>
                )}
              </div>

              {/* Product Details */}
              <div className="flex-1 min-w-0">
                <h3 className="text-sm font-semibold text-gray-800 truncate">
                  {item.product_name}
                </h3>
                <p className="text-sm text-blue-600 font-bold mt-1">
                  ${parseFloat(item.price).toFixed(2)}
                </p>

                {/* Quantity Control */}
                <div className="flex items-center gap-2 mt-3 border border-gray-300 rounded-lg w-fit">
                  <button
                    onClick={() => handleQuantityChange(item.key, item.quantity - 1)}
                    disabled={updatingKey === item.key}
                    className="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                  >
                    −
                  </button>
                  <span className="px-3 py-1 text-sm font-semibold">
                    {item.quantity}
                  </span>
                  <button
                    onClick={() => handleQuantityChange(item.key, item.quantity + 1)}
                    disabled={updatingKey === item.key}
                    className="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50"
                  >
                    +
                  </button>
                </div>

                {/* Remove Button */}
                <button
                  onClick={() => handleRemove(item.key)}
                  className="mt-2 text-xs text-red-500 hover:text-red-700 font-semibold"
                >
                  Remove
                </button>
              </div>

              {/* Item Total */}
              <div className="text-right text-sm font-semibold text-gray-800 flex-shrink-0">
                ${parseFloat(item.total).toFixed(2)}
              </div>
            </div>
          ))}
        </div>

        {/* Footer */}
        {items.length > 0 && (
          <div className="border-t px-6 py-4 space-y-4">
            <div className="flex justify-between text-lg font-bold text-gray-800">
              <span>Total:</span>
              <span className="text-blue-600">{total}</span>
            </div>
            <Link
              to="/checkout"
              onClick={onClose}
              className="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg text-center transition"
            >
              Checkout
            </Link>
            <button
              onClick={onClose}
              className="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 rounded-lg transition"
            >
              Continue Shopping
            </button>
          </div>
        )}

        {items.length === 0 && (
          <div className="border-t px-6 py-4">
            <Link
              to="/"
              onClick={onClose}
              className="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg text-center transition"
            >
              Start Shopping
            </Link>
          </div>
        )}
      </div>
    </>
  )
}
