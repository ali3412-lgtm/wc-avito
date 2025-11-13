<?php
/**
 * Функционал CRON для автоматической генерации XML
 *
 * @package WC_Avito_VDOM
 */

// Если этот файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавление пользовательских интервалов для cron
 */
function wc_avito_xml_add_cron_intervals($schedules) {
    // Добавляем различные интервалы
    $schedules['fifteen_minutes'] = array(
        'interval' => 900,
        'display'  => __('Every 15 minutes', 'wc-avito-vdom')
    );
    
    $schedules['thirty_minutes'] = array(
        'interval' => 1800,
        'display'  => __('Every 30 minutes', 'wc-avito-vdom')
    );
    
    $schedules['two_hours'] = array(
        'interval' => 7200,
        'display'  => __('Every 2 hours', 'wc-avito-vdom')
    );
    
    $schedules['four_hours'] = array(
        'interval' => 14400,
        'display'  => __('Every 4 hours', 'wc-avito-vdom')
    );
    
    $schedules['six_hours'] = array(
        'interval' => 21600,
        'display'  => __('Every 6 hours', 'wc-avito-vdom')
    );
    
    $schedules['twelve_hours'] = array(
        'interval' => 43200,
        'display'  => __('Every 12 hours', 'wc-avito-vdom')
    );
    
    return $schedules;
}
add_filter('cron_schedules', 'wc_avito_xml_add_cron_intervals');

/**
 * Инициализация cron-задач
 */
function wc_avito_xml_init_cron() {
    // Получаем настройки расписания
    $xml_schedule_enabled = get_option('wc_avito_xml_schedule_enabled', '1');
    $xml_schedule_interval = get_option('wc_avito_xml_schedule_interval', 'thirty_minutes');
    
    // Настройка XML генерации
    if ($xml_schedule_enabled === '1') {
        if (!wp_next_scheduled('wc_avito_xml_cron_generate_event')) {
            wp_schedule_event(time(), $xml_schedule_interval, 'wc_avito_xml_cron_generate_event');
        } else {
            // Проверяем, изменился ли интервал
            $current_schedule = wp_get_schedule('wc_avito_xml_cron_generate_event');
            if ($current_schedule !== $xml_schedule_interval) {
                wc_avito_xml_reschedule_event('wc_avito_xml_cron_generate_event', $xml_schedule_interval);
            }
        }
    } else {
        // Отключаем задачу, если она была включена
        wc_avito_xml_unschedule_event('wc_avito_xml_cron_generate_event');
    }
}
add_action('init', 'wc_avito_xml_init_cron');

/**
 * Перепланирование cron-события
 */
function wc_avito_xml_reschedule_event($hook, $new_schedule) {
    // Удаляем старое событие
    wc_avito_xml_unschedule_event($hook);
    
    // Создаем новое с новым расписанием
    wp_schedule_event(time(), $new_schedule, $hook);
    
    // Логируем изменение
    wc_avito_xml_log('Cron event rescheduled: ' . $hook . ' to ' . $new_schedule);
}

/**
 * Отмена cron-события
 */
function wc_avito_xml_unschedule_event($hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
        wc_avito_xml_log('Cron event unscheduled: ' . $hook);
    }
}

/**
 * Хуки для выполнения генерации по расписанию
 */
add_action('wc_avito_xml_cron_generate_event', 'wc_avito_xml_cron_generate');

/**
 * Функция для запуска генерации XML по расписанию
 */
function wc_avito_xml_cron_generate() {
    // Проверяем, включена ли автоматическая генерация
    if (get_option('wc_avito_xml_schedule_enabled', '1') !== '1') {
        wc_avito_xml_log('XML cron generation skipped - disabled in settings');
        return;
    }
    
    wc_avito_xml_log('XML generation started via cron');
    
    try {
        generate_avito_xml();
        wc_avito_xml_log('XML generation completed successfully via cron');
        
        // Обновляем время последнего запуска
        update_option('wc_avito_xml_last_cron_run', current_time('mysql'));
        
    } catch (Exception $e) {
        wc_avito_xml_log('XML generation failed via cron: ' . $e->getMessage());
        
        // Отправляем уведомление администратору, если включено
        if (get_option('wc_avito_xml_notify_errors', '0') === '1') {
            wc_avito_xml_send_error_notification('XML Generation Error', $e->getMessage());
        }
    }
}

/**
 * Функция логирования с поддержкой различных уровней
 */
function wc_avito_xml_log($message, $level = 'info') {
    // Проверяем, включено ли логирование
    if (get_option('wc_avito_xml_enable_logging', '1') !== '1') {
        return;
    }
    
    $timestamp = current_time('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] WC Avito: {$message}";
    
    // Записываем в error_log
    error_log($log_message);
    
    // Дополнительно сохраняем в базу данных для отображения в админке
    $logs = get_option('wc_avito_xml_cron_logs', array());
    
    // Ограничиваем количество записей в логе (последние 100)
    if (count($logs) >= 100) {
        $logs = array_slice($logs, -99);
    }
    
    $logs[] = array(
        'timestamp' => $timestamp,
        'level' => $level,
        'message' => $message
    );
    
    update_option('wc_avito_xml_cron_logs', $logs);
}

/**
 * Отправка уведомления об ошибке администратору
 */
function wc_avito_xml_send_error_notification($subject, $message) {
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    $email_subject = "[{$site_name}] WC Avito: {$subject}";
    $email_message = "Произошла ошибка в плагине WC Avito XML Export:\n\n";
    $email_message .= "Время: " . current_time('Y-m-d H:i:s') . "\n";
    $email_message .= "Ошибка: {$message}\n\n";
    $email_message .= "Проверьте настройки плагина в админ-панели WordPress.";
    
    wp_mail($admin_email, $email_subject, $email_message);
}

/**
 * Получение информации о следующем запуске cron-задач
 */
function wc_avito_xml_get_next_cron_info() {
    $info = array();
    
    // XML генерация
    $xml_next = wp_next_scheduled('wc_avito_xml_cron_generate_event');
    if ($xml_next) {
        $info['xml'] = array(
            'next_run' => date('Y-m-d H:i:s', $xml_next),
            'schedule' => wp_get_schedule('wc_avito_xml_cron_generate_event'),
            'enabled' => get_option('wc_avito_xml_schedule_enabled', '1') === '1'
        );
    }
    
    return $info;
}

/**
 * Очистка логов cron-задач
 */
function wc_avito_xml_clear_cron_logs() {
    delete_option('wc_avito_xml_cron_logs');
    wc_avito_xml_log('Cron logs cleared by administrator');
}
