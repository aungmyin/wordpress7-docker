import React, { createContext, useContext, useEffect } from 'react'
import { create } from 'zustand'
import axios from 'axios'

// Safe access to window object
const getConfig = () => {
  if (typeof window === 'undefined') return {}
  return window.claudeShoppingTheme || {}
}

const config = getConfig()
const REST_URL = config.restUrl || '/index.php/wp-json'
const STORE_API_URL = `${REST_URL}/wc/store/v1`
const CART_NONCE = config.cartNonce || ''

console.log('🛒 Cart config:', { REST_URL, STORE_API_URL, CART_NONCE })

// Configure axios instance for WooCommerce Store API
// WooCommerce Store API typically doesn't require nonce for public endpoints
const axiosInstance = axios.create({
  baseURL: STORE_API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// Transform WC Store API cart format to our format
const transformCartData = (wcCart) => {
  if (!wcCart) return { items: [], total: '$0', count: 0 }

  const items = (wcCart.items || []).map(item => ({
    key: item.key,
    product_id: item.id,
    quantity: item.quantity,
    total: item.totals?.line_total || '0',
    product_name: item.name,
    product_image: item.images?.[0]?.src || null,
    price: item.prices?.price || '0',
  }))

  // Format total with currency
  const totalsData = wcCart.totals || {}
  const currencyPrefix = totalsData.currency_prefix || ''
  const currencySuffix = totalsData.currency_suffix || ''
  const totalPrice = totalsData.total_price || '0'
  const formattedTotal = `${currencyPrefix}${totalPrice}${currencySuffix}`

  return {
    items,
    total: formattedTotal || '$0',
    count: wcCart.items_count || 0,
  }
}

// Zustand store for cart state
const useCartStore = create((set, get) => ({
  items: [],
  total: '$0',
  count: 0,
  loading: false,
  error: null,
  isInitialized: false,

  addToCart: async (productId, quantity = 1) => {
    set({ loading: true, error: null })
    try {
      console.log('🛒 Adding to cart:', { productId, quantity })
      const response = await axiosInstance.post('/cart/add-item', {
        id: productId,
        quantity,
      })
      console.log('✅ Add to cart success:', response.data)
      const cartData = transformCartData(response.data)
      set({
        items: cartData.items,
        total: cartData.total,
        count: cartData.count,
        loading: false,
      })
      return cartData
    } catch (error) {
      const errorMsg = error.response?.data?.message || error.message || 'Failed to add to cart'
      console.error('❌ Add to cart error:', errorMsg, error)
      set({
        error: errorMsg,
        loading: false,
      })
      throw new Error(errorMsg)
    }
  },

  updateCart: async (cartItemKey, quantity) => {
    set({ loading: true, error: null })
    try {
      console.log('📝 Updating cart:', { cartItemKey, quantity })
      const response = await axiosInstance.post(`/cart/items/${cartItemKey}`, {
        quantity,
      })
      console.log('✅ Update success:', response.data)
      const cartData = transformCartData(response.data)
      set({
        items: cartData.items,
        total: cartData.total,
        count: cartData.count,
        loading: false,
      })
      return cartData
    } catch (error) {
      console.error('❌ Update cart error:', error)
      set({
        error: error.response?.data?.message || 'Failed to update cart',
        loading: false,
      })
      throw error
    }
  },

  removeFromCart: async (cartItemKey) => {
    set({ loading: true, error: null })
    try {
      console.log('🗑️ Removing from cart:', cartItemKey)
      const response = await axiosInstance.post('/cart/remove-item', {
        key: cartItemKey,
      })
      console.log('✅ Remove success:', response.data)
      const cartData = transformCartData(response.data)
      set({
        items: cartData.items,
        total: cartData.total,
        count: cartData.count,
        loading: false,
      })
      return cartData
    } catch (error) {
      console.error('❌ Remove from cart error:', error)
      set({
        error: error.response?.data?.message || 'Failed to remove from cart',
        loading: false,
      })
      throw error
    }
  },

  getCart: async () => {
    set({ loading: true, error: null })
    try {
      const response = await axiosInstance.get('/cart')
      console.log('📦 Get cart success:', response.data)
      const cartData = transformCartData(response.data)
      set({
        items: cartData.items,
        total: cartData.total,
        count: cartData.count,
        loading: false,
        isInitialized: true,
      })
      return cartData
    } catch (error) {
      console.error('⚠️ Get cart error:', error.message)
      set({
        loading: false,
        isInitialized: true,
      })
      // Return empty cart on error instead of throwing
      return { items: [], total: '$0', count: 0 }
    }
  },

  clearError: () => set({ error: null }),
}))

// React Context for Cart
const CartContext = createContext()

export function CartProvider({ children }) {
  useEffect(() => {
    console.log('🛍️ CartProvider mounted - initializing cart')
    useCartStore.getState().getCart()
  }, [])

  return (
    <CartContext.Provider value={useCartStore}>
      {children}
    </CartContext.Provider>
  )
}

export function useCart() {
  const context = useContext(CartContext)
  if (!context) {
    throw new Error('useCart must be used within CartProvider')
  }
  return context
}

// Hook for fetching products from WooCommerce API
export function useProducts(params = {}) {
  const [products, setProducts] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [error, setError] = React.useState(null)

  // Serialize params to stable string to avoid dependency array issues
  const paramsKey = JSON.stringify(params)

  React.useEffect(() => {
    const fetchProducts = async () => {
      setLoading(true)
      setError(null)
      try {
        const response = await axios.get(`${REST_URL}/claude-shopping/v1/products`, {
          params: {
            per_page: 12,
            ...params,
          },
        })
        setProducts(response.data)
      } catch (err) {
        console.error('Product fetch error:', err)
        setError(err.response?.data?.message || err.message || 'Failed to load products')
      } finally {
        setLoading(false)
      }
    }

    fetchProducts()
  }, [paramsKey])

  return { products, loading, error }
}

// Hook for fetching single product
export function useProduct(productId) {
  const [product, setProduct] = React.useState(null)
  const [loading, setLoading] = React.useState(false)
  const [error, setError] = React.useState(null)

  React.useEffect(() => {
    if (!productId) return

    const fetchProduct = async () => {
      setLoading(true)
      setError(null)
      try {
        // Fetch all products and find the one with matching ID
        const response = await axios.get(`${REST_URL}/claude-shopping/v1/products?per_page=100`)
        const foundProduct = response.data.find(p => p.id === parseInt(productId))

        if (foundProduct) {
          setProduct(foundProduct)
          console.log('📦 Product loaded:', foundProduct)
        } else {
          setError('Product not found')
        }
      } catch (err) {
        console.error('❌ Product fetch error:', err.message)
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }

    fetchProduct()
  }, [productId])

  return { product, loading, error }
}
