<?php
/**
 * Функционал административного меню плагина
 *
 * @package WC_Avito_VDOM
 */

// Если этот файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавляем пункт меню в админ-панель
 */
add_action('admin_menu', 'wc_avito_xml_menu');

function wc_avito_xml_menu() {
    add_menu_page(
        'WC to Avito XML',
        'WC to Avito XML',
        'manage_options',
        'wc-avito-xml',
        'wc_avito_xml_page',
        'dashicons-media-spreadsheet',
        56
    );
}

/**
 * Страница генерации XML
 */
function wc_avito_xml_page() {
    if (isset($_POST['save_settings'])) {
        // Сохранение существующих настроек
        update_option('wc_avito_xml_enable_categories', isset($_POST['wc_avito_xml_enable_categories']) ? '1' : '0');
        update_option('wc_avito_xml_enable_products', isset($_POST['wc_avito_xml_enable_products']) ? '1' : '0');
        update_option('wc_avito_xml_enable_main_ad', isset($_POST['wc_avito_xml_enable_main_ad']) ? '1' : '0');
        update_option('wc_avito_xml_disable_single_product_categories', isset($_POST['wc_avito_xml_disable_single_product_categories']) ? '1' : '0');

        // Сохранение общих настроек
        update_option('wc_avito_xml_contact_phone', sanitize_text_field($_POST['wc_avito_xml_contact_phone']));
        update_option('wc_avito_xml_manager_name', sanitize_text_field($_POST['wc_avito_xml_manager_name']));
        update_option('wc_avito_xml_address', sanitize_text_field($_POST['wc_avito_xml_address']));
        update_option('wc_avito_xml_contact_method', sanitize_text_field($_POST['wc_avito_xml_contact_method']));
        update_option('wc_avito_xml_internet_calls', isset($_POST['wc_avito_xml_internet_calls']) ? 'Да' : 'Нет');
        
        // Новые поля
        update_option('wc_avito_xml_latitude', sanitize_text_field($_POST['wc_avito_xml_latitude']));
        update_option('wc_avito_xml_longitude', sanitize_text_field($_POST['wc_avito_xml_longitude']));
        update_option('wc_avito_xml_logo', esc_url_raw($_POST['wc_avito_xml_logo']));
        update_option('wc_avito_xml_work_days', sanitize_text_field($_POST['wc_avito_xml_work_days']));

        // Настройки расписания
        update_option('wc_avito_xml_schedule_enabled', isset($_POST['wc_avito_xml_schedule_enabled']) ? '1' : '0');
        update_option('wc_avito_xml_schedule_interval', sanitize_text_field($_POST['wc_avito_xml_schedule_interval']));
        
        // Настройки логирования и уведомлений
        update_option('wc_avito_xml_enable_logging', isset($_POST['wc_avito_xml_enable_logging']) ? '1' : '0');
        update_option('wc_avito_xml_notify_errors', isset($_POST['wc_avito_xml_notify_errors']) ? '1' : '0');

        echo '<div class="updated"><p>Настройки сохранены.</p></div>';
    }

    if (isset($_POST['generate_xml']) && check_admin_referer('generate_avito_xml', 'wc_avito_xml_nonce')) {
        generate_avito_xml();
    }
    
    if (isset($_POST['clear_logs']) && check_admin_referer('clear_avito_logs', 'wc_avito_logs_nonce')) {
        wc_avito_xml_clear_cron_logs();
        echo '<div class="updated"><p>Логи очищены.</p></div>';
    }
    
    if (isset($_POST['export_debug_info']) && check_admin_referer('export_debug_info', 'wc_avito_debug_nonce')) {
        wc_avito_export_debug_info();
    }

    // HTML код страницы настроек
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <h2>Настройки экспорта</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Активировать экспорт категорий</th>
                    <td><input type="checkbox" name="wc_avito_xml_enable_categories" value="1" <?php checked(get_option('wc_avito_xml_enable_categories', '1'), '1'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Активировать экспорт товаров</th>
                    <td><input type="checkbox" name="wc_avito_xml_enable_products" value="1" <?php checked(get_option('wc_avito_xml_enable_products', '1'), '1'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Активировать основное объявление</th>
                    <td><input type="checkbox" name="wc_avito_xml_enable_main_ad" value="1" <?php checked(get_option('wc_avito_xml_enable_main_ad', '1'), '1'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Деактивировать категории с одним товаром</th>
                    <td><input type="checkbox" name="wc_avito_xml_disable_single_product_categories" value="1" <?php checked(get_option('wc_avito_xml_disable_single_product_categories', '1'), '1'); ?> /></td>
                </tr>
            </table>

            <h2>Общие настройки</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Контактный телефон</th>
                    <td><input type="text" name="wc_avito_xml_contact_phone" value="<?php echo esc_attr(get_option('wc_avito_xml_contact_phone', '')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Имя менеджера</th>
                    <td><input type="text" name="wc_avito_xml_manager_name" value="<?php echo esc_attr(get_option('wc_avito_xml_manager_name', '')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Адрес</th>
                    <td><input type="text" name="wc_avito_xml_address" value="<?php echo esc_attr(get_option('wc_avito_xml_address', '')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Способ связи</th>
                    <td>
                        <select name="wc_avito_xml_contact_method">
                            <option value="По телефону и в сообщениях" <?php selected(get_option('wc_avito_xml_contact_method', 'По телефону и в сообщениях'), 'По телефону и в сообщениях'); ?>>По телефону и в сообщениях</option>
                            <option value="По телефону" <?php selected(get_option('wc_avito_xml_contact_method', 'По телефону и в сообщениях'), 'По телефону'); ?>>По телефону</option>
                            <option value="В сообщениях" <?php selected(get_option('wc_avito_xml_contact_method', 'По телефону и в сообщениях'), 'В сообщениях'); ?>>В сообщениях</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Интернет-звонки</th>
                    <td><input type="checkbox" name="wc_avito_xml_internet_calls" value="1" <?php checked(get_option('wc_avito_xml_internet_calls', 'Нет'), 'Да'); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Широта</th>
                    <td><input type="number" name="wc_avito_xml_latitude" step="any" value="<?php echo esc_attr(get_option('wc_avito_xml_latitude', '')); ?>" />
                    <p class="description">Широта местоположения для всех объявлений</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Долгота</th>
                    <td><input type="number" name="wc_avito_xml_longitude" step="any" value="<?php echo esc_attr(get_option('wc_avito_xml_longitude', '')); ?>" />
                    <p class="description">Долгота местоположения для всех объявлений</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">URL логотипа</th>
                    <td><input type="url" name="wc_avito_xml_logo" value="<?php echo esc_attr(get_option('wc_avito_xml_logo', '')); ?>" />
                    <p class="description">URL логотипа компании для всех объявлений</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Рабочие дни</th>
                    <td><input type="text" name="wc_avito_xml_work_days" value="<?php echo esc_attr(get_option('wc_avito_xml_work_days', '')); ?>" placeholder="Пн-Пт, Сб-Вс" />
                    <p class="description">Рабочие дни для всех объявлений (например: Пн-Пт, Сб-Вс)</p></td>
                </tr>
            </table>

            <h2>Настройки автоматической генерации</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Автоматическая генерация XML</th>
                    <td>
                        <input type="checkbox" name="wc_avito_xml_schedule_enabled" value="1" <?php checked(get_option('wc_avito_xml_schedule_enabled', '1'), '1'); ?> />
                        <p class="description">Включить автоматическую генерацию XML по расписанию</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Интервал генерации XML</th>
                    <td>
                        <select name="wc_avito_xml_schedule_interval">
                            <option value="fifteen_minutes" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'fifteen_minutes'); ?>>Каждые 15 минут</option>
                            <option value="thirty_minutes" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'thirty_minutes'); ?>>Каждые 30 минут</option>
                            <option value="hourly" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'hourly'); ?>>Каждый час</option>
                            <option value="two_hours" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'two_hours'); ?>>Каждые 2 часа</option>
                            <option value="four_hours" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'four_hours'); ?>>Каждые 4 часа</option>
                            <option value="six_hours" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'six_hours'); ?>>Каждые 6 часов</option>
                            <option value="twelve_hours" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'twelve_hours'); ?>>Каждые 12 часов</option>
                            <option value="daily" <?php selected(get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'), 'daily'); ?>>Ежедневно</option>
                        </select>
                        <p class="description">Как часто генерировать XML файл</p>
                    </td>
                </tr>
            </table>

            <h2>Настройки логирования и уведомлений</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Включить логирование</th>
                    <td>
                        <input type="checkbox" name="wc_avito_xml_enable_logging" value="1" <?php checked(get_option('wc_avito_xml_enable_logging', '1'), '1'); ?> />
                        <p class="description">Записывать логи выполнения cron-задач</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Уведомления об ошибках</th>
                    <td>
                        <input type="checkbox" name="wc_avito_xml_notify_errors" value="1" <?php checked(get_option('wc_avito_xml_notify_errors', '0'), '1'); ?> />
                        <p class="description">Отправлять email-уведомления администратору при ошибках</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Сохранить настройки', 'primary', 'save_settings'); ?>
        </form>

        <h2>Генерация XML</h2>
        <form method="post" action="">
            <?php wp_nonce_field('generate_avito_xml', 'wc_avito_xml_nonce'); ?>
            <p>Нажмите кнопку ниже, чтобы сгенерировать XML-файл для Avito.</p>
            <?php submit_button('Сгенерировать XML', 'secondary', 'generate_xml'); ?>
        </form>
        
        <h2>Экспорт настроек для отладки</h2>
        <form method="post" action="">
            <?php wp_nonce_field('export_debug_info', 'wc_avito_debug_nonce'); ?>
            <p>Экспортировать все настройки плагина и информацию о товарах в JSON файл для диагностики проблем.</p>
            <?php submit_button('Экспортировать настройки', 'secondary', 'export_debug_info'); ?>
        </form>

        <?php
        // Добавляем ссылки на сгенерированные файлы
        $upload_dir = wp_upload_dir();
        
        // XML файл
        $xml_file_path = $upload_dir['basedir'] . '/avito_products.xml';
        $xml_file_url = $upload_dir['baseurl'] . '/avito_products.xml';

        if (file_exists($xml_file_path)) {
            echo '<h2>Сгенерированный файл</h2>';
            
            $xml_file_time = filemtime($xml_file_path);
            echo '<h3>XML-файл</h3>';
            echo '<p>Последнее обновление: ' . date('Y-m-d H:i:s', $xml_file_time) . '</p>';
            echo '<p><a href="' . esc_url($xml_file_url) . '" target="_blank" class="button">Скачать XML-файл</a></p>';
        }
        
        // Информация о расписании cron-задач
        echo '<h2>Информация о расписании</h2>';
        $cron_info = wc_avito_xml_get_next_cron_info();
        
        if (!empty($cron_info)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Тип</th><th>Статус</th><th>Расписание</th><th>Следующий запуск</th><th>Последний запуск</th></tr></thead>';
            echo '<tbody>';
            
            // XML
            if (isset($cron_info['xml'])) {
                $xml_last_run = get_option('wc_avito_xml_last_cron_run', 'Никогда');
                echo '<tr>';
                echo '<td><strong>XML генерация</strong></td>';
                echo '<td>' . ($cron_info['xml']['enabled'] ? '<span style="color: green;">Включено</span>' : '<span style="color: red;">Отключено</span>') . '</td>';
                echo '<td>' . esc_html($cron_info['xml']['schedule']) . '</td>';
                echo '<td>' . esc_html($cron_info['xml']['next_run']) . '</td>';
                echo '<td>' . esc_html($xml_last_run) . '</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo '<td><strong>XML генерация</strong></td>';
                echo '<td><span style="color: red;">Не запланировано</span></td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>Нет активных cron-задач.</p>';
        }
        
        // Логи cron-задач
        if (get_option('wc_avito_xml_enable_logging', '1') === '1') {
            echo '<h2>Логи выполнения</h2>';
            $logs = get_option('wc_avito_xml_cron_logs', array());
            
            if (!empty($logs)) {
                // Показываем последние 20 записей
                $recent_logs = array_slice(array_reverse($logs), 0, 20);
                
                echo '<div style="background: #f1f1f1; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">';
                foreach ($recent_logs as $log) {
                    $level_color = '';
                    switch ($log['level']) {
                        case 'error':
                            $level_color = 'color: red;';
                            break;
                        case 'warning':
                            $level_color = 'color: orange;';
                            break;
                        case 'info':
                            $level_color = 'color: blue;';
                            break;
                    }
                    echo '<div style="margin-bottom: 5px;">';
                    echo '<span style="color: #666;">[' . esc_html($log['timestamp']) . ']</span> ';
                    echo '<span style="' . $level_color . '">[' . strtoupper(esc_html($log['level'])) . ']</span> ';
                    echo esc_html($log['message']);
                    echo '</div>';
                }
                echo '</div>';
                
                // Кнопка очистки логов
                echo '<form method="post" action="" style="margin-top: 10px;">';
                wp_nonce_field('clear_avito_logs', 'wc_avito_logs_nonce');
                submit_button('Очистить логи', 'delete', 'clear_logs', false);
                echo '</form>';
            } else {
                echo '<p>Логи пусты.</p>';
            }
        }
        ?>
    </div>
    <?php
}

/**
 * Функция экспорта отладочной информации
 */
function wc_avito_export_debug_info() {
    global $wp_version;
    
    // Собираем данные для экспорта
    $debug_data = array();
    
    // 1. Информация о системе
    $debug_data['system'] = array(
        'export_date' => current_time('Y-m-d H:i:s'),
        'site_url' => get_site_url(),
        'php_version' => phpversion(),
        'wordpress_version' => $wp_version,
        'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
        'plugin_version' => '1.0.0',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
    );
    
    // 2. Все настройки плагина
    $plugin_settings = array(
        'enable_categories' => get_option('wc_avito_xml_enable_categories', '1'),
        'enable_products' => get_option('wc_avito_xml_enable_products', '1'),
        'enable_main_ad' => get_option('wc_avito_xml_enable_main_ad', '1'),
        'disable_single_product_categories' => get_option('wc_avito_xml_disable_single_product_categories', '1'),
        'schedule_enabled' => get_option('wc_avito_xml_schedule_enabled', '1'),
        'schedule_interval' => get_option('wc_avito_xml_schedule_interval', 'thirty_minutes'),
        'enable_logging' => get_option('wc_avito_xml_enable_logging', '1'),
        'notify_errors' => get_option('wc_avito_xml_notify_errors', '0'),
    );
    
    // Контактные данные - только непустые
    $contact_phone = get_option('wc_avito_xml_contact_phone', '');
    if (!empty($contact_phone)) {
        $plugin_settings['contact_phone'] = $contact_phone;
    }
    
    $contact_method = get_option('wc_avito_xml_contact_method', '');
    if (!empty($contact_method)) {
        $plugin_settings['contact_method'] = $contact_method;
    }
    
    $manager_name = get_option('wc_avito_xml_manager_name', '');
    if (!empty($manager_name)) {
        $plugin_settings['manager_name'] = $manager_name;
    }
    
    $address = get_option('wc_avito_xml_address', '');
    if (!empty($address)) {
        $plugin_settings['address'] = $address;
    }
    
    $internet_calls = get_option('wc_avito_xml_internet_calls', '');
    if (!empty($internet_calls)) {
        $plugin_settings['internet_calls'] = $internet_calls;
    }
    
    $latitude = get_option('wc_avito_xml_latitude', '');
    if (!empty($latitude)) {
        $plugin_settings['latitude'] = $latitude;
    }
    
    $longitude = get_option('wc_avito_xml_longitude', '');
    if (!empty($longitude)) {
        $plugin_settings['longitude'] = $longitude;
    }
    
    $logo = get_option('wc_avito_xml_logo', '');
    if (!empty($logo)) {
        $plugin_settings['logo'] = $logo;
    }
    
    $work_days = get_option('wc_avito_xml_work_days', '');
    if (!empty($work_days)) {
        $plugin_settings['work_days'] = $work_days;
    }
    
    $debug_data['plugin_settings'] = $plugin_settings;
    
    // 3. Информация о товарах для экспорта
    $products = wc_get_products(array(
        'status' => 'publish',
        'limit' => -1,
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
    
    $debug_data['products_for_export'] = array(
        'total_count' => count($products),
        'products' => array()
    );
    
    foreach ($products as $product) {
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'type' => $product->get_type(),
            'price' => $product->get_price()
        );
        
        // Добавляем только непустые поля
        $sku = $product->get_sku();
        if (!empty($sku)) {
            $product_data['sku'] = $sku;
        }
        
        // Статус только если не publish
        if ($product->get_status() !== 'publish') {
            $product_data['status'] = $product->get_status();
        }
        
        // Цены только если отличаются от основной
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        if (!empty($regular_price) && $regular_price != $product->get_price()) {
            $product_data['regular_price'] = $regular_price;
        }
        if (!empty($sale_price)) {
            $product_data['sale_price'] = $sale_price;
        }
        
        // Категории - только ID и название
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories)) {
            $product_data['categories'] = array();
            foreach ($categories as $category) {
                $product_data['categories'][] = array(
                    'id' => $category->term_id,
                    'name' => $category->name
                );
            }
        }
        
        // Мета-поля - только заполненные
        $meta_fields = array();
        $avito_export = get_post_meta($product->get_id(), 'avito_export', true);
        if (!empty($avito_export)) {
            $meta_fields['avito_export'] = $avito_export;
        }
        
        $avito_title = get_post_meta($product->get_id(), 'avito_title', true);
        if (!empty($avito_title)) {
            $meta_fields['avito_title'] = $avito_title;
        }
        
        $avito_description = get_post_meta($product->get_id(), 'avito_description', true);
        if (!empty($avito_description)) {
            $meta_fields['avito_description'] = $avito_description;
        }
        
        $avito_specialty = get_post_meta($product->get_id(), 'avito_specialty', true);
        if (!empty($avito_specialty)) {
            $meta_fields['avito_specialty'] = $avito_specialty;
        }
        
        $short_avalible = get_post_meta($product->get_id(), 'short_avalible', true);
        if (!empty($short_avalible)) {
            $meta_fields['short_avalible'] = $short_avalible;
        }
        
        $deposit_amount = get_post_meta($product->get_id(), 'deposit_amount', true);
        if (!empty($deposit_amount)) {
            $meta_fields['deposit_amount'] = $deposit_amount;
        }
        
        if (!empty($meta_fields)) {
            $product_data['meta_fields'] = $meta_fields;
        }
        
        // Для вариативных товаров - только если цены отличаются
        if ($product->is_type('variable')) {
            $var_min = $product->get_variation_price('min', true);
            $var_max = $product->get_variation_price('max', true);
            if ($var_min != $var_max) {
                $product_data['variation_price_min'] = $var_min;
                $product_data['variation_price_max'] = $var_max;
            }
        }
        
        $debug_data['products_for_export']['products'][] = $product_data;
    }
    
    // 4. Информация о категориях
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    
    $debug_data['categories'] = array(
        'total_count' => count($categories),
        'categories' => array()
    );
    
    foreach ($categories as $category) {
        $category_data = array(
            'id' => $category->term_id,
            'name' => $category->name
        );
        
        // Количество товаров только если больше 0
        if ($category->count > 0) {
            $category_data['count'] = $category->count;
        }
        
        // Мета-поля - только заполненные
        $meta_fields = array();
        
        $avito_export = get_term_meta($category->term_id, 'avito_export', true);
        if (!empty($avito_export)) {
            $meta_fields['avito_export'] = $avito_export;
        }
        
        $avito_category = get_term_meta($category->term_id, 'avito_category', true);
        if (!empty($avito_category)) {
            $meta_fields['avito_category'] = $avito_category;
        }
        
        $avito_price = get_term_meta($category->term_id, 'avito_price', true);
        if (!empty($avito_price)) {
            $meta_fields['avito_price'] = $avito_price;
        }
        
        $avito_contact_method = get_term_meta($category->term_id, 'avito_contact_method', true);
        if (!empty($avito_contact_method)) {
            $meta_fields['avito_contact_method'] = $avito_contact_method;
        }
        
        $avito_manager_name = get_term_meta($category->term_id, 'avito_manager_name', true);
        if (!empty($avito_manager_name)) {
            $meta_fields['avito_manager_name'] = $avito_manager_name;
        }
        
        $avito_contact_phone = get_term_meta($category->term_id, 'avito_contact_phone', true);
        if (!empty($avito_contact_phone)) {
            $meta_fields['avito_contact_phone'] = $avito_contact_phone;
        }
        
        $avito_address = get_term_meta($category->term_id, 'avito_address', true);
        if (!empty($avito_address)) {
            $meta_fields['avito_address'] = $avito_address;
        }
        
        $avito_internet_calls = get_term_meta($category->term_id, 'avito_internet_calls', true);
        if (!empty($avito_internet_calls)) {
            $meta_fields['avito_internet_calls'] = $avito_internet_calls;
        }
        
        if (!empty($meta_fields)) {
            $category_data['meta_fields'] = $meta_fields;
        }
        
        $debug_data['categories']['categories'][] = $category_data;
    }
    
    // 5. Информация о cron задачах
    $debug_data['cron_info'] = wc_avito_xml_get_next_cron_info();
    $debug_data['cron_info']['last_xml_run'] = get_option('wc_avito_xml_last_cron_run', 'Никогда');
    
    // 6. Логи (последние 50)
    $logs = get_option('wc_avito_xml_cron_logs', array());
    $debug_data['logs'] = array_slice(array_reverse($logs), 0, 50);
    
    // 7. Глобальные ошибки
    global $avito_xml_errors;
    $debug_data['current_errors'] = $avito_xml_errors;
    
    // 8. Информация о файлах
    $upload_dir = wp_upload_dir();
    $xml_file_path = $upload_dir['basedir'] . '/avito_products.xml';
    
    $debug_data['files'] = array(
        'xml_exists' => file_exists($xml_file_path),
        'xml_size' => file_exists($xml_file_path) ? filesize($xml_file_path) : 0,
        'xml_modified' => file_exists($xml_file_path) ? date('Y-m-d H:i:s', filemtime($xml_file_path)) : 'N/A',
        'upload_dir' => $upload_dir['basedir'],
        'upload_url' => $upload_dir['baseurl'],
    );
    
    // 9. Спецификация мета-полей плагина
    $debug_data['meta_fields_specification'] = array(
        'product_meta_fields' => array(
            'avito_export' => array(
                'type' => 'checkbox',
                'label' => 'Экспортировать на Avito',
                'values' => array('yes', 'no'),
                'required' => true,
                'description' => 'Определяет, будет ли товар экспортироваться в XML'
            ),
            'avito_title' => array(
                'type' => 'text',
                'label' => 'Пользовательское название для Avito',
                'required' => false,
                'description' => 'Если не указано, используется название товара + " – аренда"',
                'max_length' => 50
            ),
            'avito_description' => array(
                'type' => 'textarea',
                'label' => 'Пользовательское описание для Avito',
                'required' => false,
                'description' => 'Дополнительное описание для объявления на Avito',
                'max_length' => 3000
            ),
            'avito_specialty' => array(
                'type' => 'select',
                'label' => 'Специальность (Avito)',
                'required' => false,
                'description' => 'Категория инструмента для Avito',
                'values' => array(
                    'Строительные инструменты',
                    'Садовая техника',
                    'Электроинструменты',
                    'Ручной инструмент',
                    'Измерительные приборы'
                )
            ),
            'short_avalible' => array(
                'type' => 'select',
                'label' => 'Аренда на 3 часа',
                'required' => false,
                'values' => array('да', 'нет'),
                'default' => 'да',
                'description' => 'Доступна ли аренда на 3 часа. Влияет на отображение цен в объявлении'
            ),
            'deposit_amount' => array(
                'type' => 'number',
                'label' => 'Размер залога',
                'required' => false,
                'description' => 'Сумма залога в рублях. Если не указана, рассчитывается как цена товара × 10'
            )
        ),
        'category_meta_fields' => array(
            'avito_export' => array(
                'type' => 'checkbox',
                'label' => 'Экспортировать на Avito',
                'values' => array('yes', ''),
                'required' => false,
                'description' => 'Определяет, будет ли создано объявление для категории'
            ),
            'avito_category' => array(
                'type' => 'select',
                'label' => 'Категория Avito',
                'required' => false,
                'default' => 'Предложение услуг',
                'values' => array(
                    'Предложение услуг',
                    'Ремонт и строительство',
                    'Грузоперевозки',
                    'Аренда транспорта',
                    'Бытовые услуги',
                    'Услуги для бизнеса',
                    'Обучение, курсы',
                    'Красота, здоровье',
                    'Компьютерные услуги',
                    'Праздники, мероприятия',
                    'Фото- и видеосъемка',
                    'Няни, сиделки'
                ),
                'description' => 'Категория объявления на Avito'
            ),
            'avito_price' => array(
                'type' => 'number',
                'label' => 'Цена для категории',
                'required' => false,
                'description' => 'Если не указана, используется цена самого дешевого товара в категории'
            ),
            'avito_contact_method' => array(
                'type' => 'select',
                'label' => 'Способ связи',
                'required' => false,
                'values' => array(
                    'По телефону и в сообщениях',
                    'По телефону',
                    'В сообщениях'
                ),
                'description' => 'Fallback на общие настройки плагина, если не указано'
            ),
            'avito_manager_name' => array(
                'type' => 'text',
                'label' => 'Имя менеджера',
                'required' => false,
                'description' => 'Fallback на общие настройки плагина, если не указано'
            ),
            'avito_contact_phone' => array(
                'type' => 'text',
                'label' => 'Контактный телефон',
                'required' => false,
                'description' => 'Fallback на общие настройки плагина, если не указано',
                'format' => '+7XXXXXXXXXX'
            ),
            'avito_address' => array(
                'type' => 'text',
                'label' => 'Адрес',
                'required' => false,
                'description' => 'Fallback на общие настройки плагина, если не указано'
            ),
            'avito_internet_calls' => array(
                'type' => 'select',
                'label' => 'Интернет-звонки',
                'required' => false,
                'values' => array('yes', 'no'),
                'description' => 'Fallback на общие настройки плагина, если не указано'
            ),
            'avito_work_format' => array(
                'type' => 'select',
                'label' => 'Формат работы',
                'required' => false,
                'values' => array(
                    'В организации',
                    'С выездом',
                    'В организации и с выездом',
                    'Удаленно'
                )
            ),
            'avito_experience' => array(
                'type' => 'select',
                'label' => 'Опыт работы',
                'required' => false,
                'values' => array(
                    'Не имеет значения',
                    'Менее года',
                    'От 1 до 3 лет',
                    'От 3 до 5 лет',
                    'Более 5 лет'
                )
            ),
            'avito_portfolio' => array(
                'type' => 'url',
                'label' => 'Ссылка на портфолио',
                'required' => false,
                'description' => 'URL ссылка на портфолио или примеры работ'
            ),
            'avitoid' => array(
                'type' => 'text',
                'label' => 'AvitoId категории',
                'required' => false,
                'description' => 'Используется как Id в XML для категорийных объявлений'
            )
        ),
        'price_calculation_logic' => array(
            'price_3hours' => array(
                'source' => 'product.get_price() или get_variation_price("min") для вариативных',
                'description' => 'Базовая цена за 3 часа аренды',
                'default_if_empty' => 50
            ),
            'daily_price' => array(
                'formula' => 'price_3hours × 2',
                'description' => 'Цена за сутки',
                'default_if_empty' => 100
            ),
            'price_7days' => array(
                'formula' => 'daily_price × 0.8',
                'description' => 'Цена за сутки при аренде от 7 дней (скидка 20%)'
            ),
            'price_30days' => array(
                'formula' => 'daily_price × 0.6',
                'description' => 'Цена за сутки при аренде от 30 дней (скидка 40%)'
            ),
            'deposit' => array(
                'source' => 'deposit_amount meta-field или price × 10',
                'description' => 'Размер залога',
                'default' => 2000
            )
        ),
        'fallback_logic' => array(
            'description' => 'Порядок приоритета значений настроек',
            'priority' => array(
                1 => 'Значение из мета-поля конкретного товара/категории',
                2 => 'Значение из мета-поля категории товара (для товаров)',
                3 => 'Значение из общих настроек плагина',
                4 => 'Значение по умолчанию'
            ),
            'applies_to' => array(
                'contact_method',
                'manager_name',
                'contact_phone',
                'address',
                'internet_calls',
                'avito_category'
            )
        )
    );
    
    // Конвертируем в JSON
    $json_data = json_encode($debug_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Генерируем имя файла
    $filename = 'avito-debug-' . date('Y-m-d-H-i-s') . '.json';
    
    // Сохраняем файл в директорию загрузок
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/' . $filename;
    $file_url = $upload_dir['baseurl'] . '/' . $filename;
    
    // Записываем JSON в файл
    $result = file_put_contents($file_path, $json_data);
    
    if ($result !== false) {
        // Показываем сообщение с ссылкой на скачивание
        echo '<div class="updated"><p>';
        echo 'Файл диагностики успешно создан: ';
        echo '<a href="' . esc_url($file_url) . '" download="' . esc_attr($filename) . '" class="button button-primary">Скачать ' . esc_html($filename) . '</a>';
        echo '</p></div>';
        
        // Логируем создание файла
        wc_avito_xml_log('Debug info exported to: ' . $filename, 'info');
    } else {
        echo '<div class="error"><p>Ошибка при создании файла диагностики. Проверьте права доступа к папке uploads.</p></div>';
        wc_avito_xml_log('Failed to create debug export file', 'error');
    }
}
