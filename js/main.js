// TPSIT 2024/2025 - AJAX功能实现
// 功能：热销商品加载 + 购物车数量异步修改
// 符合指南要求：异步调用、响应处理、错误管理、详细注释

document.addEventListener('DOMContentLoaded', function() {
    // 加载热销商品
    prodotti_piu_venduti();
    // 绑定购物车数量按钮
    initCartQuantityControls();
});

/**
 * AJAX调用：加载热销商品TOP4
 */
function prodotti_piu_venduti() {
    const container = document.getElementById('piu-venduti');
    if (!container) return;

    container.innerHTML = '<p class="loading">Caricamento prodotti più venduti...</p>';

    fetch('api/piu_venduti.php')
        .then(response => {
            if (!response.ok) throw new Error('Errore di connessione al server');
            return response.json();
        })
        .then(products => {
            if (!products.length) {
                container.innerHTML = '<p class="text-muted">Nessun prodotto trovato</p>';
                return;
            }

            let html = '<div class="piu-venduto-grid">';
            products.forEach(product => {
                const img = product.indirizzo_img || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect fill="%23e5e7eb" width="200" height="200"/><text x="50%25" y="50%25" fill="%239ca3af" text-anchor="middle" dy=".3em">Nessuna immagine</text></svg>';
                html += `
                    <div class="piu-venduto-item">
                        <img src="${img}" alt="${escapeHtml(product.nome)}" loading="lazy">
                        <h4>${escapeHtml(product.nome)}</h4>
                        <p class="price">€ ${Number(product.prezzo).toFixed(2).replace('.', ',')}</p>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = `<p class="error">Errore: ${error.message}</p>`;
        });
}

/**
 * 购物车数量加减按钮AJAX交互
 */
function initCartQuantityControls() {
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.qty-form');
            if (!form) return;

            const productId = form.querySelector('input[name="product_id"]').value;
            const currentQty = parseInt(form.querySelector('.qty-input').value);
            const isPlus = this.classList.contains('qty-plus');
            const newQty = isPlus ? currentQty + 1 : currentQty - 1;

            if (newQty < 1) return;

            // 获取CSRF token
            const csrfToken = form.querySelector('input[name="csrf_token"]')?.value || '';

            // 发送AJAX请求
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', newQty);
            formData.append('update_quantity', '1');
            formData.append('csrf_token', csrfToken);

            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    form.querySelector('.qty-input').value = data.quantity;
                    // 更新小计
                    const row = form.closest('tr');
                    if (row && data.subtotal) {
                        const subtotalCell = row.querySelector('.subtotal');
                        if (subtotalCell) {
                            subtotalCell.textContent = '€ ' + data.subtotal;
                        }
                    }
                    // 更新购物车角标
                    if (data.cart_total !== undefined) {
                        updateCartBadge(data.cart_total);
                    }
                    // 更新总计
                    if (data.total && document.getElementById('cart-total')) {
                        document.getElementById('cart-total').textContent = '€ ' + data.total;
                    }
                } else {
                    alert(data.message || 'Errore durante l\'aggiornamento');
                }
            })
            .catch(error => {
                // AJAX失败时降级为普通表单提交
                form.submit();
            });
        });
    });
}

/**
 * 更新导航栏购物车角标数字
 */
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * HTML转义工具函数（防止XSS）
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
