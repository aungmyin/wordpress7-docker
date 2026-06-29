import React, { useState, useEffect } from 'react'
import ProductCard from '../components/ProductCard'
import { useProducts } from '../hooks/useCart'

export default function HomePage({ searchQuery }) {
  const searchParams = new URLSearchParams(window.location.search)
  const finalSearchQuery = searchQuery || searchParams.get('search') || ''

  const [page, setPage] = useState(null)
  const [loading, setLoading] = useState(true)
  const [categories, setCategories] = useState([])
  const [settings, setSettings] = useState({})

  const { products, loading: productsLoading } = useProducts({
    per_page: 8,
    search: finalSearchQuery,
  })

  useEffect(() => {
    const fetchPage = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/page/home`
        )
        if (response.ok) {
          const data = await response.json()
          setPage(data)
        }
      } catch (err) {
        console.error('Error fetching home page:', err)
      } finally {
        setLoading(false)
      }
    }

    const fetchCategories = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/categories`
        )
        if (response.ok) {
          const data = await response.json()
          setCategories(data)
        }
      } catch (err) {
        console.error('Error fetching categories:', err)
      }
    }

    const fetchSettings = async () => {
      try {
        const response = await fetch(
          `${window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'}/claude-shopping/v1/home-settings`
        )
        if (response.ok) {
          const data = await response.json()
          setSettings(data)
        }
      } catch (err) {
        console.error('Error fetching settings:', err)
      }
    }

    fetchPage()
    fetchCategories()
    fetchSettings()
  }, [])

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading home page...</p>
        </div>
      </div>
    )
  }

  const homeTitle = settings.hero_title || 'Welcome to Claude AI Shopping'
  const homeSubtitle = settings.hero_subtitle || 'Discover amazing products at great prices'

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Banner */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-5xl md:text-6xl font-bold mb-6">{homeTitle}</h1>
            <p className="text-xl md:text-2xl text-blue-100 mb-8 max-w-2xl mx-auto">{homeSubtitle}</p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a href="/" className="inline-block bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                Shop Now
              </a>
              <a href="/about" className="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition cursor-pointer">
                Learn More
              </a>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        {/* Features Section */}
        {!finalSearchQuery && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
            <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
              <svg className="w-12 h-12 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Premium Quality</h3>
              <p className="text-gray-600">Carefully curated products that meet our high standards</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
              <svg className="w-12 h-12 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Best Prices</h3>
              <p className="text-gray-600">Competitive pricing with frequent discounts and deals</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-8 text-center hover:shadow-lg transition">
              <svg className="w-12 h-12 text-purple-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <h3 className="text-xl font-bold text-gray-800 mb-3">Expert Support</h3>
              <p className="text-gray-600">Dedicated customer support ready to help anytime</p>
            </div>
          </div>
        )}

        {/* Featured Products */}
        <div className="mb-20">
          <h2 className="text-4xl font-bold text-gray-800 mb-2 text-center">Featured Products</h2>
          <p className="text-gray-600 text-center mb-12">Discover our best-selling items</p>
          {productsLoading ? (
            <div className="text-center py-12"><p className="text-gray-600">Loading products...</p></div>
          ) : products.length === 0 ? (
            <div className="text-center py-12"><p className="text-gray-600">No products available</p></div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
              {products.map((product) => (<ProductCard key={product.id} product={product} />))}
            </div>
          )}
        </div>

        {/* Popular Products Section */}
        {!finalSearchQuery && (
          <div className="mb-20">
            <div className="bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg p-8 text-center mb-12">
              <h2 className="text-4xl font-bold mb-2">{settings.popular_section_title || '🔥 Best Sellers'}</h2>
              <p className="text-lg opacity-90">{settings.popular_section_subtitle || 'Most loved products by our customers'}</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {products.slice(0, 4).map((product) => (<ProductCard key={`popular-${product.id}`} product={product} />))}
            </div>
          </div>
        )}

        {/* Categories Slider Section */}
        {!finalSearchQuery && categories.length > 0 && (
          <div className="bg-white rounded-lg shadow-md p-12 mb-16">
            <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">Shop by Category</h2>
            <div className="relative">
              <div className="overflow-x-auto scrollbar-hide" style={{scrollBehavior: 'smooth'}}>
                <div className="flex gap-6" style={{minWidth: 'min-content'}}>
                  {categories.map((category, index) => {
                    const colors = ['from-blue-500 to-blue-600', 'from-green-500 to-green-600', 'from-purple-500 to-purple-600', 'from-pink-500 to-pink-600', 'from-orange-500 to-orange-600', 'from-red-500 to-red-600']
                    return (
                      <a
                        key={category.id}
                        href={`/category/${category.id}`}
                        className={`bg-gradient-to-r ${colors[index % colors.length]} text-white p-8 rounded-lg text-center transition transform hover:scale-105 block cursor-pointer flex-shrink-0 w-48`}
                      >
                        <h3 className="text-xl font-bold mb-2">{category.name}</h3>
                        <p className="opacity-90 text-sm">{category.count} products</p>
                      </a>
                    )
                  })}
                </div>
              </div>
            </div>
            <style>{`
              .scrollbar-hide::-webkit-scrollbar {
                display: none;
              }
              .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
              }
            `}</style>
          </div>
        )}

        {/* Discount Section */}
        {!finalSearchQuery && (
          <div className="bg-gradient-to-r from-yellow-400 to-orange-400 text-gray-800 rounded-lg shadow-lg p-12 mb-16 text-center">
            <h2 className="text-4xl font-bold mb-4">{settings.discount_title || 'Limited Time Offer!'}</h2>
            <p className="text-xl mb-6">{settings.discount_subtitle || 'Get up to 40% OFF on selected items'}</p>
            <a href="/" className="inline-block bg-white text-orange-600 font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition">
              Shop Discounts
            </a>
          </div>
        )}

        {/* Testimonials Section */}
        {!finalSearchQuery && (
          <div className="mb-16">
            <h2 className="text-4xl font-bold text-gray-800 mb-12 text-center">{settings.testimonial_section_title || 'What Our Customers Say'}</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {[
                { name: 'Sarah Johnson', text: 'Excellent quality and fast delivery! Highly recommended.', rating: 5 },
                { name: 'Mike Chen', text: 'Great products at competitive prices. Will definitely order again!', rating: 5 },
                { name: 'Emily Davis', text: 'Amazing customer service! They helped me find the perfect product.', rating: 5 },
              ].map((testimonial, i) => (
                <div key={i} className="bg-white rounded-lg shadow-md p-8">
                  <div className="flex text-yellow-400 mb-4">
                    {[...Array(testimonial.rating)].map((_, j) => (<span key={j} className="text-xl">★</span>))}
                  </div>
                  <p className="text-gray-600 mb-4 italic">"{testimonial.text}"</p>
                  <p className="font-semibold text-gray-800">— {testimonial.name}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Trust Badges Section */}
        {!finalSearchQuery && (
          <div className="bg-gray-100 rounded-lg p-12 mb-16">
            <h2 className="text-3xl font-bold text-gray-800 mb-12 text-center">{settings.trust_section_title || 'Trusted by Thousands'}</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
              <div><div className="text-4xl mb-2">🔒</div><p className="text-gray-700 font-semibold">Secure Payments</p></div>
              <div><div className="text-4xl mb-2">🚚</div><p className="text-gray-700 font-semibold">Fast Shipping</p></div>
              <div><div className="text-4xl mb-2">↩️</div><p className="text-gray-700 font-semibold">Easy Returns</p></div>
              <div><div className="text-4xl mb-2">💬</div><p className="text-gray-700 font-semibold">24/7 Support</p></div>
            </div>
          </div>
        )}

        {/* FAQ Section */}
        {!finalSearchQuery && (
          <div className="mb-16">
            <h2 className="text-4xl font-bold text-gray-800 mb-12 text-center">{settings.faq_section_title || 'Frequently Asked Questions'}</h2>
            <div className="space-y-4 max-w-2xl mx-auto">
              {[
                { q: 'How long does shipping take?', a: 'Orders are shipped within 2-3 business days. Delivery typically takes 5-7 business days.' },
                { q: 'Do you offer international shipping?', a: 'Yes, we ship to most countries worldwide. Shipping costs vary by location.' },
                { q: 'What is your return policy?', a: 'We offer 30-day returns on all items in original condition. No questions asked!' },
                { q: 'Is my payment information secure?', a: 'Absolutely! We use SSL encryption and PCI compliance to protect your data.' },
              ].map((faq, i) => (
                <details key={i} className="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition">
                  <summary className="font-semibold text-gray-800">{faq.q}</summary>
                  <p className="text-gray-600 mt-3">{faq.a}</p>
                </details>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
