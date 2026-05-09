<?php
// TPSIT AJAX接口：获取销量最高的4个商品
// 返回JSON格式数据
header('Content-Type: application/json');

include '../config.php';

// 查询销量最高的4个商品（LEFT JOIN确保即使没卖出过也会显示）
$stmt = $conn->prepare("
    SELECT p.nome, p.prezzo, p.indirizzo_img, 
           COALESCE(SUM(po.pezzi), 0) AS totale_vendite
    FROM prodotto p
    LEFT JOIN p_o po ON p.id_prodotto = po.id_prodotto
    GROUP BY p.id_prodotto
    ORDER BY totale_vendite DESC
    LIMIT 4
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 返回JSON响应
echo json_encode($products);
exit;
?>