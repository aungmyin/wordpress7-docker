import React, { createContext, useContext } from 'react'
import { create } from 'zustand'
import axios from 'axios'

const REST_URL = window.claudeShoppingTheme?.restUrl || '/wp-json'
const API_URL = `${REST_URL}/claude-shopping/v1`

// Zustand store for cart state
const useCartStore = create((set, get) => ({
  items: [],
  total: 0,
  count: 0,
  loading: false,
  error: null,

  addToCart: async (productId, quantity = 1) => {
    set({ loading: true, error: null })
    try {
      const response = await axios.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'add',
        product_id: productId,
        quantity,
      }, {
        headers: {
          'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
        },
      })
      set({
        items: response.data.items,
        total: response.data.total,
        count: response.data.count,
        loading: false,
      })
      return response.data
    } catch (error) {
      set({
        error: error.response?.data?.message || 'Failed to add to cart',
        loading: false,
      })
      throw error
    }
  },

  updateCart: async (cartItemKey, quantity) => {
    set({ loading: true, error: null })
    try {
      const response = await axios.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'update',
        cart_item_key: cartItemKey,
        quantity,
      }, {
        headers: {
          'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
        },
      })
      set({
        items: response.data.items,
        total: response.data.total,
        count: response.data.count,
        loading: false,
      })
      return response.data
    } catch (error) {
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
      const response = await axios.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'remove',
        cart_item_key: cartItemKey,
      }, {
        headers: {
          'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
        },
      })
      set({
        items: response.data.items,
        total: response.data.total,
        count: response.data.count,
        loading: false,
      })
      return response.data
    } catch (error) {
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
      const response = await axios.post(`${REST_URL}/claude-shopping/v1/cart`, {
        action: 'get',
      }, {
        headers: {
          'X-WP-Nonce': window.claudeShoppingTheme?.nonce || '',
        },
      })
      set({
        items: response.data.items,
        total: response.data.total,
        count: response.data.count,
        loading: false,
      })
      return response.data
    } catch (error) {
      set({
        error: error.response?.data?.message || 'Failed to load cart',
        loading: false,
      })
      throw error
    }
  },

  clearError: () => set({ error: null }),
}))

// React Context for Cart
const CartContext = createContext()

export function CartProvider({ children }) {
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
        const response = await axios.get(`${API_URL}/products/${productId}`)
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
