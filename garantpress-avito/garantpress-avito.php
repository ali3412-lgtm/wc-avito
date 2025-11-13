<?php
/**
 * Plugin Name: GarantPress Avito Export
 * Plugin URI: https://github.com/ali3412-lgtm/wc-avito
 * Description: Экспорт товаров WooCommerce на Avito в формате XML
 * Version: 1.0.0
 * Author: GarantPress
 * Author URI: https://press18.ru
 * Text Domain: garantpress-avito
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * WC supports high-performance order storage: true
 */

// Если этот файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

// Определяем константы плагина
define('WC_AVITO_VDOM_VERSION', '1.0.0');
define('WC_AVITO_VDOM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_AVITO_VDOM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_AVITO_VDOM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Глобальная переменная для ошибок
global $avito_xml_errors;
$avito_xml_errors = array();

// Объявляем совместимость с HPOS (High Performance Order Storage)
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Включаем файлы плагина
require_once WC_AVITO_VDOM_PLUGIN_DIR . 'includes/admin-menu.php';
require_once WC_AVITO_VDOM_PLUGIN_DIR . 'includes/product-fields.php';
require_once WC_AVITO_VDOM_PLUGIN_DIR . 'includes/xml-generator.php';
require_once WC_AVITO_VDOM_PLUGIN_DIR . 'includes/cron.php';

/**
 * Функция для добавления ошибки
 */
function add_avito_xml_error($message) {
    global $avito_xml_errors;
    $avito_xml_errors[] = $message;
    error_log("Avito XML Error: " . $message);
}

/**
 * Активация плагина
 */
function wc_avito_vdom_activate() {
    // Создаем папку uploads, если её еще нет
    $upload_dir = wp_upload_dir();
    if (!file_exists($upload_dir['basedir'])) {
        wp_mkdir_p($upload_dir['basedir']);
    }
    
    // Регистрация настроек по умолчанию
    add_option('wc_avito_xml_enable_categories', '1');
    add_option('wc_avito_xml_enable_products', '1');
    add_option('wc_avito_xml_enable_main_ad', '1');
    add_option('wc_avito_xml_disable_single_product_categories', '1');
    
    // Настройки расписания по умолчанию
    add_option('wc_avito_xml_schedule_enabled', '1');
    add_option('wc_avito_xml_schedule_interval', 'thirty_minutes');
    
    // Настройки логирования и уведомлений
    add_option('wc_avito_xml_enable_logging', '1');
    add_option('wc_avito_xml_notify_errors', '0');
    
    // Инициализация cron-задач будет выполнена в wc_avito_xml_init_cron()
    
    // Сбрасываем правила перезаписи
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wc_avito_vdom_activate');

/**
 * Деактивация плагина
 */
function wc_avito_vdom_deactivate() {
    // Очищаем все cron-задачи
    wc_avito_xml_unschedule_event('wc_avito_xml_cron_generate_event');
    
    // Сбрасываем правила перезаписи
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wc_avito_vdom_deactivate');

/**
 * Проверка зависимостей плагина
 */
function wc_avito_vdom_check_dependencies() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_avito_vdom_woocommerce_notice');
        return false;
    }
    return true;
}
add_action('plugins_loaded', 'wc_avito_vdom_check_dependencies');

/**
 * Вывод уведомления о необходимости установки WooCommerce
 */
function wc_avito_vdom_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('Для работы плагина "WC Avito XML Export" требуется установленный и активированный плагин WooCommerce.', 'wc-avito-vdom'); ?></p>
    </div>
    <?php
}

/**
 * Регистрация настроек
 */
function wc_avito_xml_register_settings() {
    // Основные настройки экспорта
    register_setting('wc_avito_xml_options', 'wc_avito_xml_enable_categories');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_enable_products');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_enable_main_ad');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_disable_single_product_categories');
    
    // Контактная информация
    register_setting('wc_avito_xml_options', 'wc_avito_xml_contact_phone');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_manager_name');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_address');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_contact_method');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_internet_calls');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_latitude');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_longitude');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_logo');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_work_days');
    
    // Настройки расписания XML
    register_setting('wc_avito_xml_options', 'wc_avito_xml_schedule_enabled');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_schedule_interval');
    
    // Настройки логирования и уведомлений
    register_setting('wc_avito_xml_options', 'wc_avito_xml_enable_logging');
    register_setting('wc_avito_xml_options', 'wc_avito_xml_notify_errors');
}
add_action('admin_init', 'wc_avito_xml_register_settings');
