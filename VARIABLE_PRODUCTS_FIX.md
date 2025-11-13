# Исправление ошибки с вариативными товарами

## Проблема
При генерации XML для вариативных товаров (WC_Product_Variable) возникала ошибка:
```
Uncaught TypeError: Unsupported operand types: string * int
```

Ошибка происходила из-за того, что метод `$product->get_price()` для вариативных товаров возвращает пустую строку, а не число. При попытке умножить строку на число возникала фатальная ошибка.

## Исправления

### 1. Функция `get_daily_price()` (xml-generator.php, строка 16-34)
**Было:**
```php
function get_daily_price($product) {
    return $product->get_price() * 2;
}
```

**Стало:**
```php
function get_daily_price($product) {
    $price = 0;
    
    // Для вариативных товаров получаем минимальную цену вариации
    if ($product->is_type('variable')) {
        $price = $product->get_variation_price('min', true);
    } else {
        $price = $product->get_price();
    }
    
    // Конвертируем в число и проверяем на валидность
    $price = floatval($price);
    
    if ($price <= 0) {
        return 100; // Цена по умолчанию
    }
    
    return $price * 2;
}
```

### 2. Функция `add_product_ad()` (xml-generator.php, строка 711-718)
Добавлена проверка типа товара и получение category_id:
```php
// Получаем category_id товара для использования категорийных настроек
$categories = wp_get_post_terms($product->get_id(), 'product_cat');
$category_id = !empty($categories) ? $categories[0]->term_id : null;

set_common_ad_settings($ad, $product, $is_active, $category_id);

// Для вариативных товаров получаем минимальную цену
if ($product->is_type('variable')) {
    $price_3hours = floatval($product->get_variation_price('min', true));
} else {
    $price_3hours = floatval($product->get_price());
}

if ($price_3hours <= 0) {
    $price_3hours = 50;
}
```

### 3. Функция `add_custom_ad()` (xml-generator.php, строка 490-509)
Добавлена обработка вариативных товаров при получении цены:
```php
$cheapest_product = get_cheapest_product($type);

// Получаем цену с учетом вариативных товаров
$price = 100;
if ($cheapest_product) {
    if ($cheapest_product->is_type('variable')) {
        $price = floatval($cheapest_product->get_variation_price('min', true));
    } else {
        $price = floatval($cheapest_product->get_price());
    }
    if ($price <= 0) {
        $price = 100;
    }
}
```

### 4. Функция `add_category_ad()` (xml-generator.php, строка 574-597)
Аналогичная обработка для категорийных объявлений:
```php
if (!empty($avito_price) && is_numeric($avito_price)) {
    $price = floatval($avito_price);
} else {
    $cheapest_product = get_cheapest_product_in_category($category->term_id);
    
    if ($cheapest_product) {
        if ($cheapest_product->is_type('variable')) {
            $price = floatval($cheapest_product->get_variation_price('min', true));
        } else {
            $price = floatval($cheapest_product->get_price());
        }
        if ($price <= 0) {
            $price = 100;
        }
    } else {
        $price = 100;
    }
}
```

### 5. CSV генератор (csv-generator.php)
Исправлены аналогичные проблемы в трех функциях:
- `get_custom_ad_csv_data()` (строка 120-134)
- `get_category_csv_data()` (строка 173-187)
- `get_product_csv_data()` (строка 265-282)

## Результат
После внесения исправлений:
- ✅ Вариативные товары корректно обрабатываются при генерации XML
- ✅ Используется минимальная цена вариации для расчетов
- ✅ Добавлена валидация цен (защита от нулевых/пустых значений)
- ✅ Все цены конвертируются в числовой тип через `floatval()`
- ✅ Установлены значения по умолчанию для случаев, когда цена не может быть получена
- ✅ CSV экспорт также исправлен

## Дата исправления
13 ноября 2025 года
