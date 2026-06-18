import React from 'react'
import { Link, useNavigate } from 'react-router-dom'
import axios from 'axios'
import { useCart } from '../hooks/useCart'

export default function CheckoutPage() {
  const navigate = useNavigate()
  const { items, total, count } = useCart()
  const [formData, setFormData] = React.useState({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: '',
  })
  const [isProcessing, setIsProcessing] = React.useState(false)
  const [error, setError] = React.useState(null)

  if (items.length === 0) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-white rounded-lg shadow-md p-12 text-center">
          <p className="text-gray-600 mb-6">Your cart is empty</p>
          <Link to="/" className="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
            Continue Shopping
          </Link>
        </div>
      </div>
    )
  }

  const handleInputChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }))
    setError(null)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setIsProcessing(true)
    setError(null)

    try {
      const response = await axios.post(
        `${window.claudeShoppingTheme?.restUrl || '/wp-json'}/claude-shopping/v1/checkout`,
        formData,
        {
          headers: {
            'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
          },
        }
      )

      if (response.data.success) {
        alert(`✓ Order #${response.data.order_number} placed successfully!\n\nConfirmation email sent to ${formData.email}`)
        navigate('/')
      } else {
        setError(response.data.message || 'Failed to create order')
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Checkout failed. Please try again.')
    } finally {
      setIsProcessing(false)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 className="text-3xl font-bold text-gray-800 mb-8">Checkout</h1>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Checkout Form */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-md p-8">
              {error && (
                <div className="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                  {error}
                </div>
              )}
              <form onSubmit={handleSubmit}>
                {/* Billing Information */}
                <h2 className="text-xl font-bold text-gray-800 mb-6">Billing Information</h2>

                <div className="grid grid-cols-2 gap-4 mb-6">
                  <input
                    type="text"
                    name="firstName"
                    placeholder="First Name"
                    value={formData.firstName}
                    onChange={handleInputChange}
                    required
                    className="col-span-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <input
                    type="text"
                    name="lastName"
                    placeholder="Last Name"
                    value={formData.lastName}
                    onChange={handleInputChange}
                    required
                    className="col-span-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div className="mb-6">
                  <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div className="mb-6">
                  <input
                    type="tel"
                    name="phone"
                    placeholder="Phone Number"
                    value={formData.phone}
                    onChange={handleInputChange}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                {/* Shipping Address */}
                <h2 className="text-xl font-bold text-gray-800 mb-6 mt-8">Shipping Address</h2>

                <div className="mb-6">
                  <input
                    type="text"
                    name="address"
                    placeholder="Street Address"
                    value={formData.address}
                    onChange={handleInputChange}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4 mb-6">
                  <input
                    type="text"
                    name="city"
                    placeholder="City"
                    value={formData.city}
                    onChange={handleInputChange}
                    required
                    className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <input
                    type="text"
                    name="state"
                    placeholder="State/Province"
                    value={formData.state}
                    onChange={handleInputChange}
                    className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4 mb-8">
                  <input
                    type="text"
                    name="zip"
                    placeholder="ZIP/Postal Code"
                    value={formData.zip}
                    onChange={handleInputChange}
                    required
                    className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <input
                    type="text"
                    name="country"
                    placeholder="Country"
                    value={formData.country}
                    onChange={handleInputChange}
                    required
                    className="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                {/* Payment Method */}
                <h2 className="text-xl font-bold text-gray-800 mb-6">Payment Method</h2>

                <div className="mb-8 p-4 border-2 border-blue-600 rounded-lg bg-blue-50">
                  <label className="flex items-center">
                    <input type="radio" name="payment" value="card" defaultChecked className="mr-3" />
                    <span className="font-semibold text-gray-700">Credit/Debit Card</span>
                  </label>
                  <p className="text-sm text-gray-600 mt-2 ml-6">
                    This demo doesn't process real payments. In production, this would use Stripe, PayPal, or WooCommerce Payments.
                  </p>
                </div>

                <button
                  type="submit"
                  disabled={isProcessing}
                  className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 rounded-lg transition"
                >
                  {isProcessing ? 'Processing...' : 'Place Order'}
                </button>
              </form>
            </div>
          </div>

          {/* Order Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 sticky top-20">
              <h2 className="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>

              <div className="space-y-3 mb-6 border-b pb-6 max-h-64 overflow-y-auto">
                {items.map((item) => (
                  <div key={item.key} className="flex justify-between text-sm text-gray-700">
                    <span>
                      {item.product_name} x {item.quantity}
                    </span>
                    <span>${parseFloat(item.total).toFixed(2)}</span>
                  </div>
                ))}
              </div>

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
                  <span>Calculated</span>
                </div>
              </div>

              <div className="flex justify-between items-center mb-6">
                <span className="font-bold text-gray-800">Total</span>
                <span className="text-2xl font-bold text-blue-600">{total}</span>
              </div>

              <Link
                to="/cart"
                className="block text-center text-blue-600 hover:text-blue-700 text-sm font-semibold"
              >
                Edit Cart
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
