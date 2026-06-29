import React, { createContext, useContext, useEffect } from 'react'
import { create } from 'zustand'

// Simple localStorage-based cart store
const useCartStore = create((set, get) => ({
  items: [],
  total: '$0',
  count: 0,

  addToCart: (product) => {
    const { items } = get()
    const existingItem = items.find(item => item.id === product.id)

    if (existingItem) {
      // Update quantity if product already in cart
      const updatedItems = items.map(item =>
        item.id === product.id
          ? { ...item, quantity: item.quantity + 1, total: (parseFloat(item.price) * (item.quantity + 1)).toFixed(2) }
          : item
      )
      set({ items: updatedItems })
      localStorage.setItem('cart', JSON.stringify(updatedItems))
    } else {
      // Add new product to cart
      const newItem = {
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image || 'https://via.placeholder.com/100',
        quantity: 1,
        total: product.price,
      }
      const updatedItems = [...items, newItem]
      set({ items: updatedItems })
      localStorage.setItem('cart', JSON.stringify(updatedItems))
    }

    // Update total
    const updatedItems = get().items
    const newTotal = updatedItems.reduce((sum, item) => sum + parseFloat(item.total), 0)
    set({ total: `$${newTotal.toFixed(2)}`, count: updatedItems.length })
  },

  updateQuantity: (productId, quantity) => {
    const { items } = get()
    if (quantity <= 0) {
      // Remove item if quantity is 0
      get().removeFromCart(productId)
      return
    }

    const updatedItems = items.map(item =>
      item.id === productId
        ? { ...item, quantity, total: (parseFloat(item.price) * quantity).toFixed(2) }
        : item
    )
    set({ items: updatedItems })
    localStorage.setItem('cart', JSON.stringify(updatedItems))

    // Update total
    const newTotal = updatedItems.reduce((sum, item) => sum + parseFloat(item.total), 0)
    set({ total: `$${newTotal.toFixed(2)}`, count: updatedItems.length })
  },

  removeFromCart: (productId) => {
    const { items } = get()
    const updatedItems = items.filter(item => item.id !== productId)
    set({ items: updatedItems })
    localStorage.setItem('cart', JSON.stringify(updatedItems))

    // Update total
    const newTotal = updatedItems.reduce((sum, item) => sum + parseFloat(item.total), 0)
    set({ total: `$${newTotal.toFixed(2)}`, count: updatedItems.length })
  },

  clearCart: () => {
    set({ items: [], total: '$0', count: 0 })
    localStorage.removeItem('cart')
  },

  loadCart: () => {
    const saved = localStorage.getItem('cart')
    if (saved) {
      const items = JSON.parse(saved)
      const newTotal = items.reduce((sum, item) => sum + parseFloat(item.total), 0)
      set({ items, total: `$${newTotal.toFixed(2)}`, count: items.length })
    }
  },
}))

// React Context
const CartContext = createContext()

export function CartProvider({ children }) {
  useEffect(() => {
    useCartStore.getState().loadCart()
  }, [])

  return (
    <CartContext.Provider value={useCartStore}>
      {children}
    </CartContext.Provider>
  )
}

export function useCart(selector) {
  const context = useContext(CartContext)
  if (!context) {
    throw new Error('useCart must be used within CartProvider')
  }
  return selector ? context(selector) : context
}
