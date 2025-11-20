<?php
/**
 * Функционал генерации XML для Avito
 *
 * @package WC_Avito_VDOM
 */

// Если этот файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Основная функция генерации XML для Avito
 */
function generate_avito_xml() {
    global $avito_xml_errors;
    
    // Увеличиваем лимит памяти и времени выполнения
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);

    // Инициализируем XML с правильными атрибутами согласно документации Avito
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Ads formatVersion="3" target="Avito.ru"></Ads>');

    // Добавляем дату и время генерации файла
    $generation_time = new DateTime();
    $xml->addChild('GeneratedAt', $generation_time->format('Y-m-d H:i:s'));
    $xml->addChild('GeneratedDate', $generation_time->format('Y-m-d'));
    $xml->addChild('GeneratedTime', $generation_time->format('H:i:s'));

    if (!empty($avito_xml_errors)) {
        $errors_node = $xml->addChild('Errors');
        foreach ($avito_xml_errors as $error) {
            $errors_node->addChild('Error', $error);
        }
    }

    // Добавляем основные объявления
    $main_ad_active = get_option('wc_avito_xml_enable_main_ad', '1') === '1';
    if ($main_ad_active) {
        add_custom_ad($xml, 'Строительные инструменты');
        add_custom_ad($xml, 'Садовые инструменты');
    }

    // Добавляем объявления по категориям с оптимизацией
    $categories_active = get_option('wc_avito_xml_enable_categories', '1') === '1';
    $disable_single_product_categories = get_option('wc_avito_xml_disable_single_product_categories', '1') === '1';
    if ($categories_active) {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 50, // Ограничиваем количество категорий
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
                ),
                'relation' => 'AND'
            )
        ));
        
        foreach ($categories as $category) {
            $products_count = get_products_count_in_category($category->term_id);
            $is_active = $categories_active && ($products_count > 1 || !$disable_single_product_categories);
            add_category_ad($xml, $category, $is_active);
            
            // Освобождаем память после каждой категории
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    // Добавляем объявления по товарам с пагинацией
    $products_active = get_option('wc_avito_xml_enable_products', '1') === '1';
    if ($products_active) {
        $batch_size = 20; // Обрабатываем по 20 товаров за раз
        $offset = 0;
        
        do {
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => $batch_size,
                'offset' => $offset,
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
            
            foreach ($products as $product) {
                // Дополнительная проверка на случай проблем с meta_query
                $avito_export = get_post_meta($product->get_id(), 'avito_export', true);
                if ($avito_export === 'yes') {
                    add_product_ad($xml, $product, true);
                }
            }
            
            $offset += $batch_size;
            
            // Освобождаем память после каждой партии
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
        } while (count($products) === $batch_size && $offset < 500); // Максимум 500 товаров
    }

    $xml_string = $xml->asXML();
    
    // Валидация XML согласно документации Avito
    validate_avito_xml($xml_string);

    // Сохраняем XML в файл
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/avito_products.xml';
    file_put_contents($file_path, $xml_string);

    if (!empty($avito_xml_errors)) {
        error_log('XML файл сгенерирован с ошибками. Пожалуйста, проверьте начало XML файла для подробностей об ошибках.');
    } else {
        error_log('XML файл успешно сгенерирован.');
    }
}

/**
 * Валидация XML согласно документации Avito
 */
function validate_avito_xml($xml_string) {
    global $avito_xml_errors;
    
    $xml = simplexml_load_string($xml_string);
    if (!$xml) {
        add_avito_xml_error('Невалидный XML-формат');
        return;
    }
    
    // Проверяем обязательные атрибуты корневого элемента
    if (!isset($xml['formatVersion']) || !isset($xml['target'])) {
        add_avito_xml_error('Отсутствуют обязательные атрибуты formatVersion или target');
    }
    
    // Проверяем каждое объявление
    foreach ($xml->Ad as $ad) {
        // Обязательное поле Id
        if (empty((string)$ad->Id)) {
            add_avito_xml_error('Отсутствует обязательное поле Id');
        }
        
        // Проверяем длину Id (максимум 100 символов)
        if (strlen((string)$ad->Id) > 100) {
            add_avito_xml_error('Поле Id превышает максимальную длину 100 символов: ' . (string)$ad->Id);
        }
        
        // Проверяем наличие основных полей
        $required_fields = ['Category', 'Title', 'Price'];
        foreach ($required_fields as $field) {
            if (empty((string)$ad->$field)) {
                add_avito_xml_error('Отсутствует обязательное поле: ' . $field . ' в объявлении ID: ' . (string)$ad->Id);
            }
        }
        
        // Проверяем формат дат
        if (!empty($ad->DateBegin) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$ad->DateBegin)) {
            add_avito_xml_error('Неверный формат даты DateBegin: ' . (string)$ad->DateBegin);
        }
        if (!empty($ad->DateEnd) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$ad->DateEnd)) {
            add_avito_xml_error('Неверный формат даты DateEnd: ' . (string)$ad->DateEnd);
        }
    }
}

/**
 * Устанавливает общие настройки объявления
 */
function set_common_ad_settings($ad, $product = null, $is_active = true, $category_id = null) {
    // Категория Avito с приоритетом: товар → категория → дефолт
    $avito_category = 'Предложение услуг'; // По умолчанию
    
    // Приоритет 2: Категория WooCommerce
    if ($category_id) {
        $category_avito_category = get_term_meta($category_id, 'avito_category', true);
        if (!empty($category_avito_category)) {
            $avito_category = $category_avito_category;
        }
    }
    
    // Приоритет 1: Товар (самый высокий)
    if ($product) {
        $product_avito_category = get_post_meta($product->get_id(), 'avito_category', true);
        if (!empty($product_avito_category)) {
            $avito_category = $product_avito_category;
        }
    }
    
    $ad->addChild('Category', $avito_category);
    
    // Добавляем поле ServiceType из категории
    if ($category_id) {
        $service_type = get_term_meta($category_id, 'avito_service_type', true);
        if (!empty($service_type)) {
            $ad->addChild('ServiceType', $service_type);
        }
        
        // Добавляем поле Specialty из категории
        $specialty = get_term_meta($category_id, 'avito_specialty', true);
        if (!empty($specialty)) {
            $ad->addChild('Specialty', $specialty);
        }
        
        // Добавляем поле AvitoId из категории
        $avito_id = get_term_meta($category_id, 'avitoid', true);
        if (!empty($avito_id)) {
            $ad->addChild('AvitoId', $avito_id);
        }
    }
    
    
    // Способ связи - категорийный или общий
    $contact_method = get_option('wc_avito_xml_contact_method', 'По телефону и в сообщениях');
    if ($category_id) {
        $category_contact_method = get_term_meta($category_id, 'avito_contact_method', true);
        if (!empty($category_contact_method)) {
            $contact_method = $category_contact_method;
        }
    }
    $ad->addChild('ContactMethod', $contact_method);

    $today = new DateTime();
    $yesterday = (new DateTime())->modify('-1 day');

    // Определяем дату начала размещения
    $date_begin = $yesterday->format('Y-m-d'); // По умолчанию
    
    if ($product) {
        // Для товаров сначала проверяем категорийную настройку, потом товарную
        if ($category_id) {
            $category_date_begin = get_term_meta($category_id, 'avito_date_begin', true);
            if (!empty($category_date_begin)) {
                $date = DateTime::createFromFormat('Y-m-d', $category_date_begin);
                if ($date) {
                    $date_begin = $date->format('Y-m-d');
                }
            }
        }
        
        // Проверяем индивидуальную дату товара (приоритет выше категорийной)
        $available_from = get_post_meta($product->get_id(), 'available_from', true);
        if ($available_from) {
            $date = DateTime::createFromFormat('Y-m-d', $available_from);
            if ($date && $date > $today) {
                // Товар будет доступен в будущем, устанавливаем эту дату
                $date_begin = $date->format('Y-m-d');
            }
        }
    } else {
        // Для категорийных объявлений проверяем настройку категории
        if ($category_id) {
            $category_date_begin = get_term_meta($category_id, 'avito_date_begin', true);
            if (!empty($category_date_begin)) {
                $date = DateTime::createFromFormat('Y-m-d', $category_date_begin);
                if ($date) {
                    $date_begin = $date->format('Y-m-d');
                }
            }
        }
    }
    
    $ad->addChild('DateBegin', $date_begin);

    // Если объявление неактивно, принудительно устанавливаем вчерашний день
    if (!$is_active) {
        $ad->addChild('DateBegin', $yesterday->format('Y-m-d'));
    }

    // Определяем дату окончания размещения
    $date_end = $today->modify('+30 days')->format('Y-m-d'); // По умолчанию
    
    if ($category_id) {
        $category_date_end = get_term_meta($category_id, 'avito_date_end', true);
        if (!empty($category_date_end)) {
            $date = DateTime::createFromFormat('Y-m-d', $category_date_end);
            if ($date) {
                $date_end = $date->format('Y-m-d');
            }
        }
    }
    
    $ad->addChild('DateEnd', $date_end);
    
    // Имя менеджера - категорийное или общее
    $manager_name = get_option('wc_avito_xml_manager_name', '');
    if ($category_id) {
        $category_manager_name = get_term_meta($category_id, 'avito_manager_name', true);
        if (!empty($category_manager_name)) {
            $manager_name = $category_manager_name;
        }
    }
    $ad->addChild('ManagerName', $manager_name);
    
    // Контактный телефон - категорийный или общий
    $contact_phone = get_option('wc_avito_xml_contact_phone', '');
    if ($category_id) {
        $category_contact_phone = get_term_meta($category_id, 'avito_contact_phone', true);
        if (!empty($category_contact_phone)) {
            $contact_phone = $category_contact_phone;
        }
    }
    $ad->addChild('ContactPhone', $contact_phone);
    
    // Адрес - категорийный или общий
    $address = get_option('wc_avito_xml_address', '');
    if ($category_id) {
        $category_address = get_term_meta($category_id, 'avito_address', true);
        if (!empty($category_address)) {
            $address = $category_address;
        }
    }
    $ad->addChild('Address', $address);
    
    // Интернет-звонки - категорийные или общие
    $internet_calls = get_option('wc_avito_xml_internet_calls', '');
    if ($category_id) {
        $category_internet_calls = get_term_meta($category_id, 'avito_internet_calls', true);
        if ($category_internet_calls === 'yes') {
            $internet_calls = 'Да';
        } else if ($category_internet_calls === '') {
            // Используем общие настройки, оставляем как есть
        } else {
            $internet_calls = 'Нет';
        }
    }
    $ad->addChild('InternetCalls', $internet_calls);
    
    // Добавляем дополнительные поля для услуг из категории
    if ($category_id) {
        $scope = get_term_meta($category_id, 'avito_scope', true);
        if (!empty($scope)) {
            // Заменяем ", " на "|" в поле Scope
            $scope = str_replace(', ', '|', $scope);
            $ad->addChild('Scope', $scope);
        }
        
        // Используем логотип из общих настроек
        $logo = get_option('wc_avito_xml_logo', '');
        if (!empty($logo)) {
            $ad->addChild('Logo', $logo);
        }
        
        $work_format = get_term_meta($category_id, 'avito_workformat', true);
        if (!empty($work_format)) {
            // Заменяем ", " на "|" в поле WorkFormat
            $work_format = str_replace(', ', '|', $work_format);
            $ad->addChild('WorkFormat', $work_format);
        }
        
        $work_experience = get_term_meta($category_id, 'avito_work_experience', true);
        if (!empty($work_experience)) {
            $ad->addChild('WorkExperience', $work_experience);
        }
        
        $place = get_term_meta($category_id, 'avito_place', true);
        if (!empty($place)) {
            $ad->addChild('Place', $place);
        }
        
        // Обрабатываем поле портфолио как URL
        $portfolio = get_term_meta($category_id, 'avito_portfolio', true);
        if (!empty($portfolio)) {
            $ad->addChild('Portfolio', $portfolio);
        }
        
        // Используем рабочие дни из общих настроек
        $work_days = get_option('wc_avito_xml_work_days', '');
        if (!empty($work_days)) {
            $ad->addChild('WorkDays', $work_days);
        }
        
        // Обрабатываем предоплату как radio button
        $prepayment = get_term_meta($category_id, 'avito_prepayment', true);
        if (!empty($prepayment)) {
            $ad->addChild('Prepayment', $prepayment);
        }
        
        // Обрабатываем checkbox поля
        $take_urgent_orders = get_term_meta($category_id, 'avito_takeurgentorders', true);
        if ($take_urgent_orders === 'yes') {
            $ad->addChild('TakeUrgentOrders', 'Да');
        }
        
        $guarantee = get_term_meta($category_id, 'avito_guarantee', true);
        if ($guarantee === 'yes') {
            $ad->addChild('Guarantee', 'Да');
        }
        
        $work_with_legal_entities = get_term_meta($category_id, 'avito_workwithlegalentities', true);
        if (!empty($work_with_legal_entities)) {
            $ad->addChild('WorkWithLegalEntities', $work_with_legal_entities);
        }
        
        // Добавляем координаты из общих настроек
        $latitude = get_option('wc_avito_xml_latitude', '');
        $longitude = get_option('wc_avito_xml_longitude', '');
        if (!empty($latitude)) {
            $ad->addChild('Latitude', $latitude);
        }
        if (!empty($longitude)) {
            $ad->addChild('Longitude', $longitude);
        }
        
        // Добавляем устройства для звонков
        $calls_devices = get_term_meta($category_id, 'avito_calls_devices', true);
        if (!empty($calls_devices)) {
            $ad->addChild('CallsDevices', $calls_devices);
        }
    }
    
    // Добавляем новые поля согласно документации Avito
    
    // ListingFee - тип платного размещения
    $listing_fee = 'Package'; // По умолчанию
    if ($category_id) {
        $category_listing_fee = get_term_meta($category_id, 'avito_listing_fee', true);
        if (!empty($category_listing_fee)) {
            $listing_fee = $category_listing_fee;
        }
    }
    $ad->addChild('ListingFee', $listing_fee);
    
    // AdStatus - услуга продвижения
    $ad_status = 'Free'; // По умолчанию
    if ($category_id) {
        $category_ad_status = get_term_meta($category_id, 'avito_ad_status', true);
        if (!empty($category_ad_status)) {
            $ad_status = $category_ad_status;
        }
    }
    $ad->addChild('AdStatus', $ad_status);
    
    // Добавляем поля продвижения
    if ($category_id) {
        // Поле Promo
        $promo = get_term_meta($category_id, 'avito_promo', true);
        if (!empty($promo)) {
            $ad->addChild('Promo', $promo);
        }
        
        // Поля для PromoManualOptions
        $region = get_term_meta($category_id, 'avito_region', true);
        $bid = get_term_meta($category_id, 'avito_bid', true);
        $daily_limit = get_term_meta($category_id, 'avito_dailylimit', true);
        
        // Добавляем PromoManualOptions только если есть хотя бы одно из полей
        if (!empty($region) || !empty($bid) || !empty($daily_limit)) {
            $promo_manual_options = $ad->addChild('PromoManualOptions');
            $item = $promo_manual_options->addChild('Item');
            
            if (!empty($region)) {
                $item->addChild('Region', $region);
            }
            if (!empty($bid)) {
                $item->addChild('Bid', $bid);
            }
            if (!empty($daily_limit)) {
                $item->addChild('DailyLimit', $daily_limit);
            }
        }
    }
}

/**
 * Добавляет основное объявление
 */
function add_custom_ad($xml, $type) {
    $ad = $xml->addChild('Ad');
    set_common_ad_settings($ad, null, true);

    $cheapest_product = get_cheapest_product($type);
    
    // Получаем цену с учетом вариативных товаров
    $price = 100; // По умолчанию
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

    $ad->addChild('Price', $price);

    $images = $ad->addChild('Images');

    // Добавляем иконку из настроек логотипа
    $logo_url = get_option('wc_avito_xml_logo', '');
    if (!empty($logo_url)) {
        $icon_image = $images->addChild('Image');
        $icon_image->addAttribute('url', $logo_url);
    }

    // Добавляем изображения категорий
    $category_images = get_category_images($type);
    foreach ($category_images as $image_id) {
        add_image_to_ad($images, $image_id);
    }

    $ad->addChild('Id', $type == 'Строительные инструменты' ? 'custom_ad' : 'custom_ad_garden');
    $ad->addChild('Title', $type . ': аренда (прокат)');

    $description_prefix = '<p>Предлагаем прокат широкого ассортимента качественных инструментов для бытовых и профессиональных задач. У нас вы найдете все необходимое для ' . ($type == 'Строительные инструменты' ? 'ремонта и строительства' : 'садоводства и ландшафтных работ') . ' по доступным ценам и на гибких условиях аренды. Экономьте на покупке, арендуя надежное оборудование с быстрой доставкой и консультацией наших специалистов. Решайте любые задачи с нашими инструментами – удобно, выгодно и эффективно!</p>';

    $full_description = $description_prefix . get_common_description() . '<p>Нужен ' . ($type == 'Строительные инструменты' ? 'строительный' : 'садовый') . ' инструмент? Обращайтесь, постараемся подобрать!</p>';

    add_description_to_ad($ad, $full_description);
}

/**
 * Получает самый дешевый товар определенного типа
 */
function get_cheapest_product($type) {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_price',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'avito_type',
                'value'   => $type,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        return wc_get_product(get_the_ID());
    }

    return null;
}

/**
 * Добавляет объявление для категории
 */
function add_category_ad($xml, $category, $is_active) {
    $ad = $xml->addChild('Ad');
    
    // Передаем category_id для использования категорийных настроек
    set_common_ad_settings($ad, null, $is_active, $category->term_id);

    // Сначала проверяем произвольное поле "avito_price" категории
    $avito_price = get_term_meta($category->term_id, 'avito_price', true);
    $cheapest_product = null;
    
    if (!empty($avito_price) && is_numeric($avito_price)) {
        $price = floatval($avito_price);
    } else {
        // Если поле avito_price пустое или не числовое, используем цену самого дешевого товара
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
    $ad->addChild('Price', $price);

    add_category_product_images($ad, $category->term_id);

    $ad->addChild('Id', 'category_ad_' . $category->term_id);
    
    // Используем поле avito_title или стандартный заголовок
    $custom_title = get_term_meta($category->term_id, 'avito_title', true);
    $title = !empty($custom_title) ? $custom_title : $category->name . ' – аренда';
    $ad->addChild('Title', $title);

    // Используем поле avito_description или стандартное описание
    $custom_description = get_term_meta($category->term_id, 'avito_description', true);
    if (!empty($custom_description)) {
        $description = $custom_description;
    } else {
        $description = '<p>' . $category->name . ' в аренду.</p>';
        $description .= get_common_description();
        $description .= '<p>Нужны ' . mb_strtolower($category->name) . '? Обращайтесь, постараемся подобрать!</p>';
    }

    add_description_to_ad($ad, $description);

    // Добавляем информацию о количестве товаров в категории
    $products_count = get_products_count_in_category($category->term_id);
    $ad->addChild('ProductsCount', $products_count);
}

/**
 * Добавляет изображения товаров из категории
 */
function add_category_product_images($ad, $category_id) {
    $images = $ad->addChild('Images');
    $image_count = 0;
    $max_images = 10;
    
    // Сначала добавляем изображения из ACF поля "картинки_авито" самой категории
    $category_avito_images = get_field('картинки_авито', 'product_cat_' . $category_id);
    if ($category_avito_images && is_array($category_avito_images)) {
        foreach ($category_avito_images as $image) {
            if ($image_count >= $max_images) break;
            if (isset($image['url'])) {
                $image_node = $images->addChild('Image');
                $image_node->addAttribute('url', $image['url']);
                $image_count++;
            }
        }
    }
    
    // Если после изображений категории есть свободные места, добавляем изображения товаров
    if ($image_count < $max_images) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
            'posts_per_page' => -1,
        );

        $products = new WP_Query($args);

        if ($products->have_posts()) {
            // Первый проход: добавляем изображения из ACF поля "картинки_авито" товаров
            while ($products->have_posts() && $image_count < $max_images) {
                $products->the_post();
                $product = wc_get_product(get_the_ID());
                
                $avito_images = get_field('картинки_авито', $product->get_id());
                if ($avito_images && is_array($avito_images)) {
                    foreach ($avito_images as $image) {
                        if ($image_count >= $max_images) break;
                        if (isset($image['url'])) {
                            $image_node = $images->addChild('Image');
                            $image_node->addAttribute('url', $image['url']);
                            $image_count++;
                        }
                    }
                }
            }
            
            // Второй проход: добавляем основные изображения товаров (если есть свободные места)
            if ($image_count < $max_images) {
                $products->rewind_posts();
                while ($products->have_posts() && $image_count < $max_images) {
                    $products->the_post();
                    $product = wc_get_product(get_the_ID());

                    $main_image_id = $product->get_image_id();
                    if ($main_image_id && $image_count < $max_images) {
                        add_image_to_ad($images, $main_image_id);
                        $image_count++;
                    }
                }
            }
        }

        wp_reset_postdata();
    }
}

/**
 * Добавляет объявление для продукта
 */
function add_product_ad($xml, $product, $is_active) {
    $ad = $xml->addChild('Ad');
    
    // Получаем category_id товара для использования категорийных настроек
    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
    $category_id = !empty($categories) ? $categories[0]->term_id : null;
    
    // Проверяем, разрешено ли применять настройки категории к товарам
    $apply_category_settings = false;
    if ($category_id) {
        $apply_to_products = get_term_meta($category_id, 'avito_apply_to_products', true);
        $apply_category_settings = ($apply_to_products === 'yes');
    }
    
    // Если настройки категории не должны применяться, передаём null
    $effective_category_id = $apply_category_settings ? $category_id : null;
    
    set_common_ad_settings($ad, $product, $is_active, $effective_category_id);

    // Получаем цену товара - сначала проверяем произвольное поле avito_price
    $avito_price = get_post_meta($product->get_id(), 'avito_price', true);
    
    if (!empty($avito_price) && is_numeric($avito_price)) {
        $price = floatval($avito_price);
    } else {
        // Если поле avito_price пустое, используем стандартную цену WooCommerce
        // Для вариативных товаров получаем минимальную цену
        if ($product->is_type('variable')) {
            $price = floatval($product->get_variation_price('min', true));
        } else {
            $price = floatval($product->get_price());
        }
    }
    
    // Если цена не установлена, используем значение по умолчанию
    if ($price <= 0) {
        $price = 50;
    }

    add_product_images($ad, $product);

    $ad->addChild('Id', $product->get_sku());

    // Используем пользовательское название, если оно задано
    $avito_title = get_post_meta($product->get_id(), 'avito_title', true);
    if (!empty($avito_title)) {
        $title = $avito_title;
    } else {
        $title = $product->get_name();
    }
    $ad->addChild('Title', $title);
    
    // Добавляем поле Specialty, если оно задано
    $avito_specialty = get_post_meta($product->get_id(), 'avito_specialty', true);
    if (!empty($avito_specialty)) {
        $ad->addChild('Specialty', $avito_specialty);
    }

    // Получаем содержимое поля avito_description
    $avito_description = get_post_meta($product->get_id(), 'avito_description', true);

    // Используем пользовательское описание если задано, иначе описание товара
    if (!empty($avito_description)) {
        $description = $avito_description;
    } else {
        $description = $product->get_description();
    }

    add_description_to_ad($ad, $description);

    // Обязательные для Avito поля товара
    $ad_type = get_post_meta($product->get_id(), 'avito_ad_type', true);
    if (!empty($ad_type)) {
        $ad->addChild('AdType', $ad_type);
    }

    $condition = get_post_meta($product->get_id(), 'avito_condition', true);
    if (!empty($condition)) {
        $ad->addChild('Condition', $condition);
    }

    $goods_type = get_post_meta($product->get_id(), 'avito_goods_type', true);
    if (!empty($goods_type)) {
        $ad->addChild('GoodsType', $goods_type);
    }

    $goods_subtype = get_post_meta($product->get_id(), 'avito_goods_subtype', true);
    if (!empty($goods_subtype)) {
        $ad->addChild('GoodsSubType', $goods_subtype);
    }

    $interior_subtype = get_post_meta($product->get_id(), 'avito_interior_subtype', true);
    if (!empty($interior_subtype)) {
        $ad->addChild('InteriorSubType', $interior_subtype);
    }

    // Promo и PromoManualOptions с приоритетом: товар -> категория
    $promo = get_post_meta($product->get_id(), 'avito_promo', true);
    if (empty($promo) && $category_id) {
        $promo = get_term_meta($category_id, 'avito_promo', true);
    }
    if (!empty($promo)) {
        $ad->addChild('Promo', $promo);
    }

    $region = get_post_meta($product->get_id(), 'avito_region', true);
    if (empty($region) && $category_id) {
        $region = get_term_meta($category_id, 'avito_region', true);
    }

    $bid = get_post_meta($product->get_id(), 'avito_bid', true);
    if (empty($bid) && $category_id) {
        $bid = get_term_meta($category_id, 'avito_bid', true);
    }

    $daily_limit = get_post_meta($product->get_id(), 'avito_dailylimit', true);
    if (empty($daily_limit) && $category_id) {
        $daily_limit = get_term_meta($category_id, 'avito_dailylimit', true);
    }

    if (!empty($region) || !empty($bid) || !empty($daily_limit)) {
        $promo_manual_options = $ad->addChild('PromoManualOptions');
        $item = $promo_manual_options->addChild('Item');

        if (!empty($region)) {
            $item->addChild('Region', $region);
        }
        if (!empty($bid)) {
            $item->addChild('Bid', $bid);
        }
        if (!empty($daily_limit)) {
            $item->addChild('DailyLimit', $daily_limit);
        }
    }

    // Устанавливаем цену объявления
    $ad->addChild('Price', $price);
}


/**
 * Формирует общее описание
 */
function get_common_description() {
    $address = get_option('wc_avito_xml_address', '');
    $work_days = get_option('wc_avito_xml_work_days', '');

    $description = '';

    if (!empty($address)) {
        $description .= '<p><strong>Адрес получения:</strong> ' . $address . '.<br />';
    }

    $description .= '<strong>Доставка возможна</strong>, обсуждается индивидуально.<br />';

    if (!empty($work_days)) {
        $description .= '<strong>Режим работы</strong>: ' . $work_days . '</p>';
    } else {
        $description .= '</p>';
    }

    $description .= '<p>Получение по одному из документов: паспорт, водительское удостоверение или военный билет.</p>';

    return $description;
}

/**
 * Добавляет описание к объявлению
 */
function add_description_to_ad($ad, $description) {
    $description_node = $ad->addChild('Description');
    $description_cdata = dom_import_simplexml($description_node);
    $description_cdata->appendChild($description_cdata->ownerDocument->createCDATASection(prepare_description($description)));
}

/**
 * Добавляет изображения товара
 */
function add_product_images($ad, $product) {
    $images = $ad->addChild('Images');

    $max_images = 10;
    $added = 0;
    $used_ids = array();

    // Добавляем основное изображение товара первым
    $main_image_id = $product->get_image_id();
    if ($main_image_id) {
        add_image_to_ad($images, $main_image_id);
        $used_ids[$main_image_id] = true;
        $added++;
    }

    // Добавляем изображения из галереи, пока не достигнем лимита
    $gallery_image_ids = $product->get_gallery_image_ids();
    if (!empty($gallery_image_ids)) {
        foreach ($gallery_image_ids as $image_id) {
            if ($added >= $max_images) {
                break;
            }

            if ($image_id && !isset($used_ids[$image_id])) {
                add_image_to_ad($images, $image_id);
                $used_ids[$image_id] = true;
                $added++;
            }
        }
    }

    // Добавляем изображения из ACF галереи "портфолио"
    if (function_exists('get_field')) {
        $portfolio_images = get_field('портфолио', $product->get_id());
        if (!empty($portfolio_images) && is_array($portfolio_images)) {
            foreach ($portfolio_images as $portfolio_image) {
                if ($added >= $max_images) {
                    break;
                }

                $image_id = null;
                // ACF галерея возвращает массивы с данными изображений
                if (is_array($portfolio_image) && isset($portfolio_image['ID'])) {
                    $image_id = $portfolio_image['ID'];
                } elseif (is_numeric($portfolio_image)) {
                    $image_id = $portfolio_image;
                }

                if ($image_id && !isset($used_ids[$image_id])) {
                    add_image_to_ad($images, $image_id);
                    $used_ids[$image_id] = true;
                    $added++;
                }
            }
        }
    }
}

/**
 * Добавляет отдельное изображение к объявлению
 */
function add_image_to_ad($images, $image_id) {
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    if ($image_url) {
        $image = $images->addChild('Image');
        $image->addAttribute('url', $image_url);
    }
}


/**
 * Считает количество товаров в категории
 */
function get_products_count_in_category($category_id) {
    // Используем кэширование для избежания повторных запросов
    $cache_key = 'avito_products_count_' . $category_id;
    $cached_count = wp_cache_get($cache_key);
    
    if ($cached_count !== false) {
        return $cached_count;
    }
    
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
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
            ),
            'relation' => 'AND'
        ),
        'posts_per_page' => 1, // Нам нужно только количество
        'fields' => 'ids', // Получаем только ID для экономии памяти
        'no_found_rows' => false, // Нужно для получения found_posts
    );

    $products = new WP_Query($args);
    $count = $products->found_posts;
    
    // Кэшируем результат на 5 минут
    wp_cache_set($cache_key, $count, '', 300);
    
    return $count;
}

/**
 * Получает самый дешевый товар в категории
 */
function get_cheapest_product_in_category($category_id) {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_price',
        'order'          => 'ASC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id
            )
        )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        wp_reset_postdata();
        return $product;
    }

    return null;
}

/**
 * Получает изображения категорий
 */
function get_category_images($type) {
    $categories = get_terms('product_cat', array('hide_empty' => true));
    $category_images = array();

    foreach ($categories as $category) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category->term_id,
                ),
            ),
            'meta_query' => array(
                array(
                    'key'     => 'avito_type',
                    'value'   => $type,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
        );

        $products = new WP_Query($args);

        if ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());
            $image_id = $product->get_image_id();
            if ($image_id) {
                $category_images[] = $image_id;
            }
        }

        wp_reset_postdata();
    }

    return $category_images;
}

/**
 * Подготавливает описание, очищая от лишних тегов
 */
function prepare_description($description) {
    // Заменяем \n на <br>
    $description = nl2br($description);

    // Разрешенные теги
    $allowed_tags = '<p><br><strong><em><ul><ol><li>';

    // Очищаем описание, оставляя только разрешенные теги
    $description = strip_tags($description, $allowed_tags);

    return $description;
}
