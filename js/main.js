// TPSIT 2024/2025 - 简单AJAX功能实现
// 功能：无刷新加载最新3个商品
// 符合指南要求：异步调用、响应处理、错误管理、详细注释

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    loadLatestProducts();
});

/**
 * AJAX调用：加载最新商品
 * 这是项目中唯一的AJAX调用，满足TPSIT要求
 */
function loadLatestProducts() {
    // 获取显示区域
    const container = document.getElementById('piu-venduti');
    
    // 显示加载状态
    container.innerHTML = '<p class="loading">Caricamento prodotti più venduti...</p>';

    // 发起AJAX请求
    fetch('api/piu_venduti.php')
        .then(response => {
            // 检查响应是否成功
            if (!response.ok) {
                throw new Error('Errore di connessione al server');
            }
            return response.json();
        })
        .then(products => {
            // 处理成功响应：动态生成HTML
            let html = '<div class="latest-grid">';
            
            products.forEach(product => {
                html += `
                    <div class="latest-item">
                        <img src="${product.indirizzo_img}" alt="${product.nome}">
                        <h4>${product.nome}</h4>
                        <p>€ ${Number(product.prezzo).toFixed(2)}</p>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            // 处理错误（网络错误、服务器错误）
            container.innerHTML = `<p class="error">Errore: ${error.message}</p>`;
        });
}