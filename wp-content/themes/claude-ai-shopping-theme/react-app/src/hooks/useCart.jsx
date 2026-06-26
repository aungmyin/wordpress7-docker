import React, { createContext, useContext, useEffect } from 'react'
import { create } from 'zustand'
import axios from 'axios'

const REST_URL = window.claudeShoppingTheme?.restUrl || '/index.php/wp-json'
const API_URL = `${REST_URL}/claude-shopping/v1`
const NONCE = window.claudeShoppingTheme?.nonce || ''

// Configure axios instance
const axiosInstance = axios.create({
  headers: {
    'X-WP-Nonce': NONCE,
    'Content-Type': 'application/json',
  },
  credentials: 'include',
})

// Zustand store for cart state
const useCartStore = create((set, get) => ({
  items: [],
  total: 0,
  count: 0,
  loading: false,
  error: null,
  isInitialized: false,

  addToCart: async (productId, quantity = 1) => {
    set({ loading: true, error: null })
    try {
      console.log('🛒 Adding to cart:', { productId, quantity })
      const response = await axiosInstance.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'add',
        product_id: productId,
        quantity,
      })
      console.log('✅ Add to cart success:', response.data)
      set({
        items: response.data.items || [],
        total: response.data.total || 0,
        count: response.data.count || 0,
        loading: false,
      })
      return response.data
    } catch (error) {
      console.error('❌ Add to cart error:', error.response?.data || error.message)
      const errorMsg = error.response?.data?.message || error.message || 'Failed to add to cart'
      set({
        error: errorMsg,
        loading: false,
      })
      throw error
    }
  },

  updateCart: async (cartItemKey, quantity) => {
    set({ loading: true, error: null })
    try {
      console.log('📝 Updating cart:', { cartItemKey, quantity })
      const response = await axiosInstance.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'update',
        cart_item_key: cartItemKey,
        quantity,
      })
      set({
        items: response.data.items || [],
        total: response.data.total || 0,
        count: response.data.count || 0,
        loading: false,
      })
      return response.data
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
      const response = await axiosInstance.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'remove',
        cart_item_key: cartItemKey,
      })
      set({
        items: response.data.items || [],
        total: response.data.total || 0,
        count: response.data.count || 0,
        loading: false,
      })
      return response.data
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
      const response = await axiosInstance.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'get',
      })
      console.log('📦 Get cart success:', response.data)
      set({
        items: response.data.items || [],
        total: response.data.total || 0,
        count: response.data.count || 0,
        loading: false,
        isInitialized: true,
      })
      return response.data
    } catch (error) {
      console.error('❌ Get cart error:', error)
      set({
        error: error.response?.data?.message || 'Failed to load cart',
        loading: false,
        isInitialized: true,
      })
      return { items: [], total: 0, count: 0 }
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
        const response = await axios.get(`${API_URL}/product/${productId}`)
        setProduct(response.data)
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }

    fetchProduct()
  }, [productId])

  return { product, loading, error }
}
