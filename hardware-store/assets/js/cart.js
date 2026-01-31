/**
 * Cart Management JavaScript
 * จัดการตะกร้าสินค้าแบบ AJAX
 */

class CartManager {
    constructor() {
        this.apiUrl = '../api/cart.php';
        this.cartCountElement = document.querySelector('.cart-count');
        this.init();
    }

    init() {
        // Event listeners for add to cart buttons
        document.querySelectorAll('[data-add-to-cart]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.productId;
                const quantity = btn.dataset.quantity || 1;
                this.addToCart(productId, quantity);
            });
        });

        // Update cart count on page load
        this.updateCartCount();
    }

    async addToCart(productId, quantity = 1) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.updateCartCount(result.data.cart_count);

                // Animate cart icon
                this.animateCartIcon();
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showNotification('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        }
    }

    async removeFromCart(productId) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                this.updateCartCount(result.data.cart_count);

                // Remove item from DOM
                const itemElement = document.querySelector(`[data-cart-item="${productId}"]`);
                if (itemElement) {
                    itemElement.remove();
                }
            }
        } catch (error) {
            console.error('Error removing from cart:', error);
        }
    }

    async updateQuantity(productId, quantity) {
        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.updateCartCount(result.data.cart_count);
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
        }
    }

    async getCart() {
        try {
            const formData = new FormData();
            formData.append('action', 'get');

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                return result.data;
            }
        } catch (error) {
            console.error('Error getting cart:', error);
        }

        return null;
    }

    updateCartCount(count = null) {
        if (count === null) {
            // Fetch current count
            this.getCart().then(data => {
                if (data && this.cartCountElement) {
                    this.cartCountElement.textContent = data.count;

                    if (data.count > 0) {
                        this.cartCountElement.style.display = 'inline';
                    } else {
                        this.cartCountElement.style.display = 'none';
                    }
                }
            });
        } else {
            // Use provided count
            if (this.cartCountElement) {
                this.cartCountElement.textContent = count;

                if (count > 0) {
                    this.cartCountElement.style.display = 'inline';
                } else {
                    this.cartCountElement.style.display = 'none';
                }
            }
        }
    }

    animateCartIcon() {
        const cartIcon = document.querySelector('.fa-shopping-cart');
        if (cartIcon) {
            cartIcon.classList.add('cart-bounce');
            setTimeout(() => {
                cartIcon.classList.remove('cart-bounce');
            }, 600);
        }
    }

    showNotification(type, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}-custom alert-dismissible fade show notification-toast`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to page
        const container = document.querySelector('.page-container');
        if (container) {
            container.insertBefore(notification, container.firstChild);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.cartManager = new CartManager();
});

// CSS for animations
const style = document.createElement('style');
style.textContent = `
    .cart-bounce {
        animation: cartBounce 0.6s ease;
    }

    @keyframes cartBounce {
        0%, 100% { transform: scale(1); }
        25% { transform: scale(1.3); }
        50% { transform: scale(0.9); }
        75% { transform: scale(1.2); }
    }

    .notification-toast {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .notification-toast {
            left: 20px;
            right: 20px;
            min-width: auto;
        }
    }
`;
document.head.appendChild(style);