<?php
/**
 * Диагностический скрипт для проверки экспорта товара
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Загрузи этот файл в корень плагина
 * 2. Открой в браузере: https://твой-сайт.ru/wp-content/plugins/garantpress-avito/test-product-export.php?product_id=123
 *    (замени 123 на ID твоего товара)
 * 3. Скрипт покажет, почему товар не экспортируется
 */

// Загружаем WordPress
require_once('../../../../wp-load.php');

// Проверяем права администратора
if (!current_user_can('manage_options')) {
    die('Access denied. Only administrators can run this script.');
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if (!$product_id) {
    die('Please provide product_id parameter. Example: ?product_id=192426');
}

echo "<h1>Диагностика товара #$product_id</h1>";
echo "<hr>";

// 1. Проверяем существование товара
$product = wc_get_product($product_id);
if (!$product) {
    echo "<p style='color:red;'><strong>❌ ОШИБКА:</strong> Товар с ID $product_id не найден!</p>";
    exit;
}

echo "<h2>✅ Товар найден</h2>";
echo "<p><strong>Название:</strong> " . $product->get_name() . "</p>";
echo "<p><strong>Тип:</strong> " . $product->get_type() . "</p>";
echo "<p><strong>Статус:</strong> " . $product->get_status() . "</p>";
echo "<hr>";

// 2. Проверяем мета-поле avito_export
echo "<h2>Проверка мета-поля avito_export</h2>";

$meta_value = get_post_meta($product_id, 'avito_export', true);
$meta_exists = metadata_exists('post', $product_id, 'avito_export');

echo "<p><strong>Существует ли поле:</strong> " . ($meta_exists ? '✅ ДА' : '❌ НЕТ') . "</p>";
echo "<p><strong>Значение:</strong> <code>" . var_export($meta_value, true) . "</code></p>";
echo "<p><strong>Тип данных:</strong> " . gettype($meta_value) . "</p>";

if ($meta_value === 'yes') {
    echo "<p style='color:green;'>✅ Значение ПРАВИЛЬНОЕ (строгое сравнение === 'yes' ПРОЙДЕНО)</p>";
} else {
    echo "<p style='color:red;'>❌ Значение НЕПРАВИЛЬНОЕ! Ожидается строка 'yes', получено: " . var_export($meta_value, true) . "</p>";
    
    if ($meta_value == 'yes') {
        echo "<p style='color:orange;'>⚠️ Мягкое сравнение == 'yes' проходит, но строгое === нет. Проблема с типом данных!</p>";
    }
}

// Проверяем все meta поля товара
echo "<h3>Все мета-поля товара:</h3>";
$all_meta = get_post_meta($product_id);
echo "<pre>";
foreach ($all_meta as $key => $value) {
    if (strpos($key, 'avito') !== false) {
        echo "$key = " . var_export($value[0] ?? $value, true) . "\n";
    }
}
echo "</pre>";

echo "<hr>";

// 3. Проверяем запрос WooCommerce
echo "<h2>Проверка запроса WooCommerce</h2>";

$test_query = wc_get_products(array(
    'status' => 'publish',
    'limit' => -1,
    'include' => [$product_id],
    'meta_query' => array(
        array(
            'key' => 'avito_export',
            'value' => 'yes',
            'compare' => '=',
            'type' => 'CHAR'
        ),
        array(
            'key' => 'avito_export',
            'compare' => 'EXISTS'
        )
    ),
    'meta_relation' => 'AND'
));

if (count($test_query) > 0) {
    echo "<p style='color:green;'>✅ Товар НАЙДЕН через WooCommerce meta_query</p>";
} else {
    echo "<p style='color:red;'>❌ Товар НЕ НАЙДЕН через WooCommerce meta_query</p>";
    echo "<p><strong>Возможные причины:</strong></p>";
    echo "<ul>";
    echo "<li>Значение не равно строго 'yes'</li>";
    echo "<li>Поле не существует в базе данных</li>";
    echo "<li>Тип данных неправильный</li>";
    echo "<li>Кеширование WordPress/WooCommerce</li>";
    echo "</ul>";
}

echo "<hr>";

// 4. Дополнительная проверка через прямой SQL
echo "<h2>Проверка через SQL</h2>";

global $wpdb;
$sql_result = $wpdb->get_row($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'avito_export'",
    $product_id
));

if ($sql_result) {
    echo "<p><strong>SQL результат:</strong> <code>" . var_export($sql_result->meta_value, true) . "</code></p>";
    echo "<p><strong>Длина строки:</strong> " . strlen($sql_result->meta_value) . " символов</p>";
    echo "<p><strong>Hex dump:</strong> " . bin2hex($sql_result->meta_value) . "</p>";
    
    if ($sql_result->meta_value !== 'yes') {
        echo "<p style='color:red;'>⚠️ В базе данных значение отличается от ожидаемого 'yes'!</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Поле не найдено в базе данных!</p>";
}

echo "<hr>";

// 5. Рекомендации
echo "<h2>🔧 Рекомендации</h2>";

if ($meta_value !== 'yes') {
    echo "<div style='background:#fff3cd;padding:15px;border-left:4px solid #ffc107;'>";
    echo "<h3>Как исправить:</h3>";
    echo "<ol>";
    echo "<li>Перейди в админку WordPress</li>";
    echo "<li>Открой этот товар на редактирование</li>";
    echo "<li>В разделе <strong>Данные о товаре</strong> (вкладка Общие)</li>";
    echo "<li>Найди чекбокс <strong>\"Экспортировать на Avito\"</strong></li>";
    echo "<li>Поставь галочку</li>";
    echo "<li>Нажми <strong>Обновить</strong></li>";
    echo "<li>Проверь этот скрипт снова</li>";
    echo "</ol>";
    
    echo "<p><strong>Или исправь вручную через SQL:</strong></p>";
    echo "<pre>UPDATE {$wpdb->postmeta} 
SET meta_value = 'yes' 
WHERE post_id = $product_id 
  AND meta_key = 'avito_export';</pre>";
    echo "</div>";
} else {
    echo "<div style='background:#d4edda;padding:15px;border-left:4px solid #28a745;'>";
    echo "<p>✅ Мета-поле настроено правильно!</p>";
    echo "<p>Если товар всё равно не попадает в XML, проверь:</p>";
    echo "<ul>";
    echo "<li>Статус товара (должен быть 'publish')</li>";
    echo "<li>Обновлен ли плагин на сервере (последняя версия с GitHub)</li>";
    echo "<li>Нет ли ошибок в логах плагина (WooCommerce → Avito Export)</li>";
    echo "<li>Сгенерируй XML заново после всех изменений</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Скрипт завершён. Удали этот файл после диагностики.</small></p>";
