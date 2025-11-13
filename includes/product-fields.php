<?php
/**
 * Функционал дополнительных полей для товаров WooCommerce
 *
 * @package WC_Avito_VDOM
 */

// Если этот файл вызван напрямую, прерываем выполнение
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Добавляем метаполе для экспорта товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_avito_export_field');
add_action('woocommerce_process_product_meta', 'save_avito_export_field');

function add_avito_export_field() {
    woocommerce_wp_checkbox(array(
        'id' => 'avito_export',
        'label' => 'Экспортировать на Avito',
        'description' => 'Отметьте, если товар должен быть экспортирован на Avito'
    ));
}

function save_avito_export_field($post_id) {
    $avito_export = isset($_POST['avito_export']) ? 'yes' : 'no';
    update_post_meta($post_id, 'avito_export', $avito_export);
}

/**
 * Добавляем поле для категорий товаров
 */
add_action('product_cat_add_form_fields', 'add_avito_export_category_field');
add_action('product_cat_edit_form_fields', 'edit_avito_export_category_field', 10, 2);
add_action('created_product_cat', 'save_avito_export_category_field', 10, 2);
add_action('edited_product_cat', 'save_avito_export_category_field', 10, 2);

function add_avito_export_category_field() {
    // Плагин не добавляет поля на страницу создания категорий
}

function edit_avito_export_category_field($term, $taxonomy) {
    // Получаем все meta-данные категории
    $avito_export = get_term_meta($term->term_id, 'avito_export', true);
    $avito_category = get_term_meta($term->term_id, 'avito_category', true);

    $avito_contact_method = get_term_meta($term->term_id, 'avito_contact_method', true);
    $avito_listing_fee = get_term_meta($term->term_id, 'avito_listing_fee', true);
    $avito_ad_status = get_term_meta($term->term_id, 'avito_ad_status', true);
    $avito_manager_name = get_term_meta($term->term_id, 'avito_manager_name', true);
    $avito_contact_phone = get_term_meta($term->term_id, 'avito_contact_phone', true);
    $avito_address = get_term_meta($term->term_id, 'avito_address', true);
    $avito_internet_calls = get_term_meta($term->term_id, 'avito_internet_calls', true);
    $avito_description = get_term_meta($term->term_id, 'avito_description', true);
    
    // Новые поля для CSV экспорта
    $avito_calls_devices = get_term_meta($term->term_id, 'avito_calls_devices', true);
    $avito_scope = get_term_meta($term->term_id, 'avito_scope', true);
    $avito_scope_auto_separator = get_term_meta($term->term_id, 'avito_scope_auto_separator', true);
    $avito_work_format = get_term_meta($term->term_id, 'avito_work_format', true);
    $avito_work_experience = get_term_meta($term->term_id, 'avito_work_experience', true);
    $avito_take_urgent_orders = get_term_meta($term->term_id, 'avito_take_urgent_orders', true);
    $avito_guarantee = get_term_meta($term->term_id, 'avito_guarantee', true);
    $avito_place = get_term_meta($term->term_id, 'avito_place', true);
    $avito_prepayment = get_term_meta($term->term_id, 'avito_prepayment', true);
    $avito_portfolio = get_term_meta($term->term_id, 'avito_portfolio', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_export">Экспортировать на Avito</label></th>
        <td>
            <input type="checkbox" name="avito_export" id="avito_export" value="yes" <?php checked($avito_export, 'yes'); ?> />
            <p class="description"><code>avito_export</code></p>
        </td>
    </tr>
    
    <!-- Основные поля -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_category">Категория объявления</label></th>
        <td>
            <input type="text" name="avito_category" id="avito_category" value="<?php echo esc_attr($avito_category); ?>" placeholder="Например: Предложение услуг" />
            <p class="description"><code>Category</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_title">Заголовок объявления</label></th>
        <td>
            <input type="text" name="avito_title" id="avito_title" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avito_title', true)); ?>" placeholder="Например: Прокат строительных инструментов" />
            <p class="description"><code>Title</code></p>
        </td>
    </tr>
    
    <!-- Поля ServiceType и ServiceSubtype -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_service_type">Тип услуги</label></th>
        <td>
            <input type="text" name="avito_service_type" id="avito_service_type" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avito_service_type', true)); ?>" placeholder="Например: Логотипы, брендирование, полиграфия" />
            <p class="description"><code>ServiceType</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_specialty">Чем вы занимаетесь</label></th>
        <td>
            <input type="text" name="avito_specialty" id="avito_specialty" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avito_specialty', true)); ?>" placeholder="Например: Дизайн логотипов" />
            <p class="description"><code>Specialty</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_scope">Область деятельности</label></th>
        <td>
            <input type="text" name="avito_scope" id="avito_scope" value="<?php echo esc_attr($avito_scope ?? ''); ?>" />
            <p class="description"><code>Scope</code> Например: Квартиры, Дома, Офисы</p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_scope_auto_separator">Автозамена разделителей</label></th>
        <td>
            <label>
                <input type="checkbox" name="avito_scope_auto_separator" id="avito_scope_auto_separator" value="yes" <?php checked($avito_scope_auto_separator ?? '', 'yes'); ?> />
                Автоматически заменять ", " на "|" в поле Scope
            </label>
            <p class="description">Если включено, запятые будут заменены на вертикальные черты. Например: "Квартиры, Дома" → "Квартиры|Дома"</p>
        </td>
    </tr>
    
    <!-- Дополнительные поля -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_contact_method">Способ связи</label></th>
        <td>
            <select name="avito_contact_method" id="avito_contact_method">
                <option value="">Использовать общие настройки</option>
                <option value="По телефону и в сообщениях" <?php selected($avito_contact_method, 'По телефону и в сообщениях'); ?>>По телефону и в сообщениях</option>
                <option value="По телефону" <?php selected($avito_contact_method, 'По телефону'); ?>>По телефону</option>
                <option value="В сообщениях" <?php selected($avito_contact_method, 'В сообщениях'); ?>>В сообщениях</option>
            </select>
            <p class="description"><code>ContactMethod</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_listing_fee">Тип размещения</label></th>
        <td>
            <select name="avito_listing_fee" id="avito_listing_fee">
                <option value="Package" <?php selected($avito_listing_fee, 'Package'); ?>>Package - только при наличии пакета</option>
                <option value="PackageSingle" <?php selected($avito_listing_fee, 'PackageSingle'); ?>>PackageSingle - пакет или разовое</option>
                <option value="Single" <?php selected($avito_listing_fee, 'Single'); ?>>Single - только разовое размещение</option>
            </select>
            <p class="description">Тип платного размещения <code>ListingFee</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_ad_status">Услуга продвижения</label></th>
        <td>
            <select name="avito_ad_status" id="avito_ad_status">
                <option value="Free" <?php selected($avito_ad_status, 'Free'); ?>>Free - обычное объявление</option>
                <option value="Highlight" <?php selected($avito_ad_status, 'Highlight'); ?>>Highlight - выделение цветом (7 дней)</option>
                <option value="XL" <?php selected($avito_ad_status, 'XL'); ?>>XL - XL-объявление (7 дней)</option>
                <option value="x2_1" <?php selected($avito_ad_status, 'x2_1'); ?>>x2_1 - до 2х больше просмотров на 1 день</option>
                <option value="x2_7" <?php selected($avito_ad_status, 'x2_7'); ?>>x2_7 - до 2х больше просмотров на 7 дней</option>
                <option value="x5_1" <?php selected($avito_ad_status, 'x5_1'); ?>>x5_1 - до 5х больше просмотров на 1 день</option>
                <option value="x5_7" <?php selected($avito_ad_status, 'x5_7'); ?>>x5_7 - до 5х больше просмотров на 7 дней</option>
                <option value="x10_1" <?php selected($avito_ad_status, 'x10_1'); ?>>x10_1 - до 10х больше просмотров на 1 день</option>
                <option value="x10_7" <?php selected($avito_ad_status, 'x10_7'); ?>>x10_7 - до 10х больше просмотров на 7 дней</option>
                <option value="x15_1" <?php selected($avito_ad_status, 'x15_1'); ?>>x15_1 - до 15х больше просмотров на 1 день (доступно в некоторых регионах)</option>
                <option value="x15_7" <?php selected($avito_ad_status, 'x15_7'); ?>>x15_7 - до 15х больше просмотров на 7 дней (доступно в некоторых регионах)</option>
                <option value="x20_1" <?php selected($avito_ad_status, 'x20_1'); ?>>x20_1 - до 20х больше просмотров на 1 день (доступно в некоторых регионах)</option>
                <option value="x20_7" <?php selected($avito_ad_status, 'x20_7'); ?>>x20_7 - до 20х больше просмотров на 7 дней (доступно в некоторых регионах)</option>
            </select>
            <p class="description"><code>AdStatus</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_manager_name">Имя менеджера</label></th>
        <td>
            <input type="text" name="avito_manager_name" id="avito_manager_name" value="<?php echo esc_attr($avito_manager_name); ?>" />
            <p class="description"><code>ManagerName</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_contact_phone">Контактный телефон</label></th>
        <td>
            <input type="text" name="avito_contact_phone" id="avito_contact_phone" value="<?php echo esc_attr($avito_contact_phone); ?>" />
            <p class="description"><code>ContactPhone</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_address">Адрес</label></th>
        <td>
            <input type="text" name="avito_address" id="avito_address" value="<?php echo esc_attr($avito_address); ?>" />
            <p class="description"><code>Address</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_internet_calls">Интернет-звонки</label></th>
        <td>
            <input type="checkbox" name="avito_internet_calls" id="avito_internet_calls" value="yes" <?php checked($avito_internet_calls, 'yes'); ?> />
            <p class="description"><code>InternetCalls</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_description">Описание для Avito</label></th>
        <td>
            <textarea name="avito_description" id="avito_description" rows="4"><?php echo esc_textarea($avito_description); ?></textarea>
            <p class="description"><code>Description</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_date_begin">Дата начала размещения</label></th>
        <td>
            <input type="date" name="avito_date_begin" id="avito_date_begin" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avito_date_begin', true)); ?>" />
            <p class="description"><code>DateBegin</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_date_end">Дата окончания размещения</label></th>
        <td>
            <input type="date" name="avito_date_end" id="avito_date_end" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avito_date_end', true)); ?>" />
            <p class="description"><code>DateEnd</code></p>
        </td>
    </tr>
    
    <!-- Дополнительные поля для CSV экспорта -->
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_calls_devices">Устройства для звонков</label></th>
        <td>
            <select name="avito_calls_devices" id="avito_calls_devices">
                <option value="">Выберите устройство</option>
                <option value="Телефон" <?php selected($avito_calls_devices ?? '', 'Телефон'); ?>>Телефон</option>
                <option value="Компьютер" <?php selected($avito_calls_devices ?? '', 'Компьютер'); ?>>Компьютер</option>
                <option value="Планшет" <?php selected($avito_calls_devices ?? '', 'Планшет'); ?>>Планшет</option>
                <option value="Любое устройство" <?php selected($avito_calls_devices ?? '', 'Любое устройство'); ?>>Любое устройство</option>
            </select>
            <p class="description"><code>CallsDevices</code></p>
        </td>
    </tr>
    
    
    
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_work_format">Формат работы</label></th>
        <td>
            <input type="text" name="avito_work_format" id="avito_work_format" value="<?php echo esc_attr($avito_work_format); ?>" placeholder="Например: Удаленно" />
            <p class="description"><code>WorkFormat</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_work_experience">Опыт работы</label></th>
        <td>
            <select name="avito_work_experience" id="avito_work_experience">
                <option value="">Выберите опыт</option>
                <option value="Меньше года" <?php selected($avito_work_experience ?? '', 'Меньше года'); ?>>Меньше года</option>
                <option value="1–3 года" <?php selected($avito_work_experience ?? '', '1–3 года'); ?>>1–3 года</option>
                <option value="4–7 лет" <?php selected($avito_work_experience ?? '', '4–7 лет'); ?>>4–7 лет</option>
                <option value="8–10 лет" <?php selected($avito_work_experience ?? '', '8–10 лет'); ?>>8–10 лет</option>
                <option value="10 лет и больше" <?php selected($avito_work_experience ?? '', '10 лет и больше'); ?>>10 лет и больше</option>
            </select>
            <p class="description"><code>WorkExperience</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_take_urgent_orders">Принимаю срочные заказы</label></th>
        <td>
            <input type="checkbox" name="avito_take_urgent_orders" id="avito_take_urgent_orders" value="yes" <?php checked($avito_take_urgent_orders ?? '', 'yes'); ?> />
            <p class="description"><code>TakeUrgentOrders</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_guarantee">Предоставляю гарантию</label></th>
        <td>
            <input type="checkbox" name="avito_guarantee" id="avito_guarantee" value="yes" <?php checked($avito_guarantee ?? '', 'yes'); ?> />
            <p class="description"><code>Guarantee</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_place">Место оказания услуг</label></th>
        <td>
            <select name="avito_place" id="avito_place">
                <option value="">Выберите место</option>
                <option value="У заказчика" <?php selected($avito_place ?? '', 'У заказчика'); ?>>У заказчика</option>
                <option value="У исполнителя" <?php selected($avito_place ?? '', 'У исполнителя'); ?>>У исполнителя</option>
                <option value="Любое место" <?php selected($avito_place ?? '', 'Любое место'); ?>>Любое место</option>
            </select>
            <p class="description"><code>Place</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_prepayment">Требую предоплату</label></th>
        <td>
            <input type="checkbox" name="avito_prepayment" id="avito_prepayment" value="yes" <?php checked($avito_prepayment ?? '', 'yes'); ?> />
            <p class="description"><code>Prepayment</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avito_portfolio">URL портфолио</label></th>
        <td>
            <input type="url" name="avito_portfolio" id="avito_portfolio" value="<?php echo esc_attr($avito_portfolio ?? ''); ?>" />
            <p class="description"><code>Portfolio</code></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top"><label for="avitoid">Avito ID</label></th>
        <td>
            <input type="text" name="avitoid" id="avitoid" value="<?php echo esc_attr(get_term_meta($term->term_id, 'avitoid', true)); ?>" placeholder="Например: 12345" />
            <p class="description"><code>AvitoId</code></p>
        </td>
    </tr>
    
    <?php
}

function save_avito_export_category_field($term_id, $tt_id) {
    // Сохраняем экспорт на Avito
    if (isset($_POST['avito_export'])) {
        update_term_meta($term_id, 'avito_export', 'yes');
    } else {
        delete_term_meta($term_id, 'avito_export');
    }
    
    // Сохраняем автозамену разделителей для Scope
    if (isset($_POST['avito_scope_auto_separator'])) {
        update_term_meta($term_id, 'avito_scope_auto_separator', 'yes');
    } else {
        delete_term_meta($term_id, 'avito_scope_auto_separator');
    }
    
    // Массив полей для сохранения
    $fields = [
        'avito_category',
        'avito_title',
        'avito_service_type',
        'avito_ad_status',
        'avito_manager_name',
        'avito_contact_phone',
        'avito_address',
        'avito_calls_devices',
        'avito_scope',
        'avito_specialty',
        'avito_work_format',
        'avito_work_experience',
        'avito_place',
        'avito_date_begin',
        'avito_date_end',
        'avitoid'
    ];
    
    // Сохраняем все текстовые поля
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            if (!empty($value)) {
                update_term_meta($term_id, $field, $value);
            } else {
                delete_term_meta($term_id, $field);
            }
        }
    }
    
    // Отдельно обрабатываем checkbox поля
    $checkbox_fields = ['avito_internet_calls', 'avito_take_urgent_orders', 'avito_guarantee'];
    foreach ($checkbox_fields as $field) {
        if (isset($_POST[$field])) {
            update_term_meta($term_id, $field, 'yes');
        } else {
            delete_term_meta($term_id, $field);
        }
    }
    
    // Поле avito_portfolio обрабатываем как обычное текстовое поле
    if (isset($_POST['avito_portfolio'])) {
        update_term_meta($term_id, 'avito_portfolio', sanitize_text_field($_POST['avito_portfolio']));
    }
    
    // Обрабатываем radio button поле предоплаты
    if (isset($_POST['avito_prepayment'])) {
        update_term_meta($term_id, 'avito_prepayment', sanitize_text_field($_POST['avito_prepayment']));
    } else {
        delete_term_meta($term_id, 'avito_prepayment');
    }
    
    
    // Отдельно обрабатываем описание (может содержать HTML)
    if (isset($_POST['avito_description'])) {
        $avito_description = wp_kses_post($_POST['avito_description']);
        if (!empty($avito_description)) {
            update_term_meta($term_id, 'avito_description', $avito_description);
        } else {
            delete_term_meta($term_id, 'avito_description');
        }
    }
    
    // Сохраняем чекбоксы
    $checkbox_fields = ['avito_internet_calls', 'avito_take_urgent_orders', 'avito_guarantee', 'avito_prepayment'];
    foreach ($checkbox_fields as $field) {
        if (isset($_POST[$field])) {
            update_term_meta($term_id, $field, 'yes');
        } else {
            delete_term_meta($term_id, $field);
        }
    }
}

/**
 * Добавляем метаполе "avito_title" для товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_avito_title_field');
add_action('woocommerce_process_product_meta', 'save_avito_title_field');

function add_avito_title_field() {
    woocommerce_wp_text_input(array(
        'id' => 'avito_title',
        'label' => 'Название для Авито',
        'description' => 'Укажите название товара, которое будет использоваться при публикации на Авито. Если оставить пустым, будет использовано стандартное название товара.'
    ));
}

function save_avito_title_field($post_id) {
    $avito_title = isset($_POST['avito_title']) ? sanitize_text_field($_POST['avito_title']) : '';
    update_post_meta($post_id, 'avito_title', $avito_title);
}

/**
 * Добавляем метаполе "available_from" для товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_available_from_field');
add_action('woocommerce_process_product_meta', 'save_available_from_field');

function add_available_from_field() {
    woocommerce_wp_text_input(array(
        'id' => 'available_from',
        'label' => 'Доступен с',
        'description' => 'Укажите дату, с которой товар доступен (формат: ГГГГ-ММ-ДД)',
        'type' => 'date'
    ));
}

function save_available_from_field($post_id) {
    $available_from = isset($_POST['available_from']) ? sanitize_text_field($_POST['available_from']) : '';
    update_post_meta($post_id, 'available_from', $available_from);
}

/**
 * Добавляем метаполе "deposit" для товаров
 */
add_action('woocommerce_product_options_pricing', 'add_deposit_field');
add_action('woocommerce_process_product_meta', 'save_deposit_field');

function add_deposit_field() {
    woocommerce_wp_text_input(array(
        'id' => 'deposit',
        'label' => 'Залог (руб.)',
        'description' => 'Укажите сумму залога для товара. Если оставить пустым, будет использовано значение по умолчанию.',
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));
}

function save_deposit_field($post_id) {
    $deposit = isset($_POST['deposit']) ? wc_format_decimal($_POST['deposit']) : '';
    update_post_meta($post_id, 'deposit', $deposit);
}

/**
 * Добавляем метаполе "avito_type" для товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_avito_type_field');
add_action('woocommerce_process_product_meta', 'save_avito_type_field');

function add_avito_type_field() {
    woocommerce_wp_select(array(
        'id' => 'avito_type',
        'label' => 'Тип товара для Авито',
        'options' => array(
            '' => 'Выберите тип',
            'Строительные инструменты' => 'Строительные инструменты',
            'Садовые инструменты' => 'Садовые инструменты'
        ),
        'description' => 'Выберите тип товара для Авито'
    ));
}

function save_avito_type_field($post_id) {
    $avito_type = isset($_POST['avito_type']) ? sanitize_text_field($_POST['avito_type']) : '';
    update_post_meta($post_id, 'avito_type', $avito_type);
}

/**
 * Добавляем метаполе "short_avalible" для товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_short_avalible_field');
add_action('woocommerce_process_product_meta', 'save_short_avalible_field');

function add_short_avalible_field() {
    woocommerce_wp_select(array(
        'id' => 'short_avalible',
        'label' => 'Доступность аренды на 3 часа',
        'options' => array(
            '' => 'Да',
            'нет' => 'Нет',
        ),
        'description' => 'Укажите, доступна ли аренда на 3 часа для этого товара.'
    ));
}

function save_short_avalible_field($post_id) {
    $short_avalible = isset($_POST['short_avalible']) ? sanitize_text_field($_POST['short_avalible']) : '';
    update_post_meta($post_id, 'short_avalible', $short_avalible);
}

/**
 * Добавляем метаполе "avito_specialty" для товаров
 */
add_action('woocommerce_product_options_general_product_data', 'add_avito_specialty_field');
add_action('woocommerce_process_product_meta', 'save_avito_specialty_field');

function add_avito_specialty_field() {
    woocommerce_wp_text_input(array(
        'id' => 'avito_specialty',
        'label' => 'Специализация для Avito',
        'description' => 'Укажите специализацию товара для Avito'
    ));
}

function save_avito_specialty_field($post_id) {
    $avito_specialty = isset($_POST['avito_specialty']) ? sanitize_text_field($_POST['avito_specialty']) : '';
    update_post_meta($post_id, 'avito_specialty', $avito_specialty);
}
