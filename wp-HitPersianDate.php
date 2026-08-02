<?php
/**
 * Plugin Name: WP-HiTPersianDate
 * Description: تبدیل تاریخ به شمسی + فونت حرفه‌ای با تنظیمات گرافیکی مدرن
 * Version: 4.4
 * Author: Hossein.IT
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class WP_HiT_PersianDate {
    private $fonts = [
        'vazir' => 'وزیر (Vazir)',
        'yekan' => 'یکان (Yekan)',
        'shabnam' => 'شبنم (Shabnam)',
        'dana' => 'دانا (Dana)',
        'iransens' => 'ایران‌سنس (IranSans)',
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_fonts'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_fonts'], 1);
        
        add_action('wp_head', [$this, 'force_font_css'], 1);
        add_action('admin_head', [$this, 'force_font_css'], 1);
        
        add_filter('date_i18n', [$this, 'convert_date_i18n'], 10, 4);
        add_filter('get_the_date', [$this, 'convert_get_the_date'], 99, 3);
        add_filter('get_the_time', [$this, 'convert_get_the_time'], 99, 3);
        add_filter('the_date', [$this, 'convert_the_date'], 99, 2);
        add_filter('the_time', [$this, 'convert_the_time'], 99, 2);
        add_filter('wp_date', [$this, 'convert_wp_date'], 99, 4);
        
        add_filter('manage_posts_columns', [$this, 'add_shamsi_column']);
        add_action('manage_posts_custom_column', [$this, 'render_shamsi_column'], 10, 2);
        add_filter('manage_pages_columns', [$this, 'add_shamsi_column']);
        add_action('manage_pages_custom_column', [$this, 'render_shamsi_column'], 10, 2);
        add_filter('manage_users_columns', [$this, 'add_user_shamsi_column']);
        add_action('manage_users_custom_column', [$this, 'render_user_shamsi_column'], 10, 3);
    }

    public function register_settings() {
        register_setting('wp_jalali_vazir_options', 'wp_jalali_vazir_font');
        register_setting('wp_jalali_vazir_options', 'wp_jalali_date_format');
        register_setting('wp_jalali_vazir_options', 'wp_jalali_number_type');
        register_setting('wp_jalali_vazir_options', 'wp_jalali_apply_to_admin');
        register_setting('wp_jalali_vazir_options', 'wp_jalali_apply_to_front');
    }

    public function settings_page() {
        add_options_page(
            'تنظیمات فونت و تاریخ شمسی',
            'فونت و تاریخ شمسی',
            'manage_options',
            'wp-jalali-vazir-final3',
            [$this, 'settings_html']
        );
    }

    public function settings_html() {
        $font = get_option('wp_jalali_vazir_font', 'vazir');
        $date_format = get_option('wp_jalali_date_format', 'j F Y');
        $num_type = get_option('wp_jalali_number_type', 'persian');
        $apply_admin = get_option('wp_jalali_apply_to_admin', 'yes');
        $apply_front = get_option('wp_jalali_apply_to_front', 'yes');
        
        $font_dir = plugin_dir_path(__FILE__) . 'fonts/';
        $font_file = $font_dir . $font . '.woff2';
        $font_exists = file_exists($font_file);
        
        $available_fonts = [];
        if (is_dir($font_dir)) {
            foreach (scandir($font_dir) as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'woff2') {
                    $available_fonts[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        ?>
        <div class="wrap wp-jalali-dashboard">
            <div class="wp-jalali-container">
                <!-- هدر -->
                <div class="wp-jalali-header">
                    <div class="wp-jalali-header-content">
                        <div class="wp-jalali-header-icon">⚡</div>
                        <div>
                            <h1 class="wp-jalali-title">تنظیمات فونت و تاریخ شمسی</h1>
                            <p class="wp-jalali-subtitle">تغییر فونت و تاریخ در کل سایت و مدیریت</p>
                        </div>
                    </div>
                    <div class="wp-jalali-badge">
                        <span class="wp-jalali-version">نسخه ۴.۴</span>
                        <span class="wp-jalali-status-dot"></span>
                        <span class="wp-jalali-status-text">فعال</span>
                    </div>
                </div>

                <!-- وضعیت فونت‌ها -->
                <div class="wp-jalali-status-box">
                    <div class="wp-jalali-status-item">
                        <span class="wp-jalali-status-icon">📁</span>
                        <span class="wp-jalali-status-label">پوشه فونت:</span>
                        <code class="wp-jalali-status-code"><?php echo esc_html($font_dir); ?></code>
                    </div>
                    <div class="wp-jalali-status-item">
                        <span class="wp-jalali-status-icon">📄</span>
                        <span class="wp-jalali-status-label">فایل‌های موجود:</span>
                        <?php if (!empty($available_fonts)): ?>
                            <span class="wp-jalali-status-success"><?php echo implode(', ', array_map('esc_html', $available_fonts)); ?></span>
                        <?php else: ?>
                            <span class="wp-jalali-status-danger">هیچ فایل woff2 یافت نشد!</span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" action="options.php" id="wp-jalali-settings-form">
                    <?php settings_fields('wp_jalali_vazir_options'); ?>

                    <!-- کارت انتخاب فونت -->
                    <div class="wp-jalali-card wp-jalali-card-font">
                        <div class="wp-jalali-card-header">
                            <span class="wp-jalali-card-icon">🔠</span>
                            <h3 class="wp-jalali-card-title">انتخاب فونت</h3>
                            <?php if ($font_exists): ?>
                                <span class="wp-jalali-badge-success">✅ موجود</span>
                            <?php else: ?>
                                <span class="wp-jalali-badge-warning">⚠️ ناموجود</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="wp-jalali-card-body">
                            <div class="wp-jalali-grid-2">
                                <div class="wp-jalali-field">
                                    <label class="wp-jalali-label">فونت مورد نظر</label>
                                    <select name="wp_jalali_vazir_font" id="wp-jalali-font-select" class="wp-jalali-select">
                                        <?php foreach ($this->fonts as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($font, $key); ?>>
                                                <?php echo esc_html($label); ?>
                                                <?php if (in_array($key, $available_fonts)): ?> ✅<?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wp-jalali-field">
                                    <label class="wp-jalali-label">ضخامت فونت</label>
                                    <select name="wp_jalali_font_weight" id="wp-jalali-weight-select" class="wp-jalali-select">
                                        <option value="400">معمولی (Regular)</option>
                                        <option value="700">ضخیم (Bold)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="wp-jalali-checkbox-group">
                                <label class="wp-jalali-checkbox">
                                    <input type="checkbox" name="wp_jalali_apply_to_admin" value="yes" <?php checked($apply_admin, 'yes'); ?>>
                                    <span class="wp-jalali-checkbox-label">اعمال در مدیریت</span>
                                </label>
                                <label class="wp-jalali-checkbox">
                                    <input type="checkbox" name="wp_jalali_apply_to_front" value="yes" <?php checked($apply_front, 'yes'); ?>>
                                    <span class="wp-jalali-checkbox-label">اعمال در سایت</span>
                                </label>
                            </div>
                            
                            <!-- پیش‌نمایش زنده -->
                            <div class="wp-jalali-preview-box">
                                <div class="wp-jalali-preview-header">
                                    <span>💡 پیش‌نمایش فونت</span>
                                </div>
                                <div class="wp-jalali-preview-content">
                                    <span id="wp-jalali-preview-text" class="wp-jalali-preview-text">
                                        این یک متن نمونه است
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- کارت تنظیمات تاریخ -->
                    <div class="wp-jalali-card wp-jalali-card-date">
                        <div class="wp-jalali-card-header">
                            <span class="wp-jalali-card-icon">📅</span>
                            <h3 class="wp-jalali-card-title">تنظیمات تاریخ</h3>
                        </div>
                        
                        <div class="wp-jalali-card-body">
                            <div class="wp-jalali-grid-2">
                                <div class="wp-jalali-field">
                                    <label class="wp-jalali-label">فرمت تاریخ</label>
                                    <input type="text" name="wp_jalali_date_format" value="<?php echo esc_attr($date_format); ?>" class="wp-jalali-input" dir="ltr">
                                    <p class="wp-jalali-help">مثال: <code>l j F Y</code> → دوشنبه ۲۵ شهریور ۱۴۰۴</p>
                                </div>
                                <div class="wp-jalali-field">
                                    <label class="wp-jalali-label">نوع اعداد</label>
                                    <div class="wp-jalali-radio-group">
                                        <label class="wp-jalali-radio">
                                            <input type="radio" name="wp_jalali_number_type" value="persian" <?php checked($num_type, 'persian'); ?>>
                                            <span>فارسی (۰۱۲۳۴)</span>
                                        </label>
                                        <label class="wp-jalali-radio">
                                            <input type="radio" name="wp_jalali_number_type" value="latin" <?php checked($num_type, 'latin'); ?>>
                                            <span>لاتین (01234)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="wp-jalali-current-date">
                                <span>📌 تاریخ فعلی:</span>
                                <strong><?php echo $this->jdate($date_format, time()); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- دکمه‌ها -->
                    <div class="wp-jalali-actions">
                        <?php submit_button('💾 ذخیره تنظیمات', 'primary', 'submit', false, ['class' => 'wp-jalali-btn-primary']); ?>
                        <button type="button" onclick="location.reload();" class="wp-jalali-btn-secondary">
                            🔄 بازنشانی صفحه
                        </button>
                    </div>
                </form>

                <div class="wp-jalali-footer">
                    <span>توسط <strong>Hossein.IT</strong></span>
                    <span>سازگار با وردپرس ۷.۰.۲</span>
                </div>
            </div>
        </div>

        <!-- استایل نهایی با رفع کامل لیست‌ها -->
        <style>
        /* ===== ریست و پایه ===== */
        .wp-jalali-dashboard {
            margin: 10px 20px 0 2px;
            font-family: 'Vazir', Tahoma, sans-serif;
        }
        
        .wp-jalali-container {
            max-width: 900px;
            margin: 0 auto;
            background: linear-gradient(145deg, #1a1a2e, #16213e);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            color: #e2e8f0;
            direction: rtl;
        }

        /* ===== هدر ===== */
        .wp-jalali-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 22px;
            margin-bottom: 22px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .wp-jalali-header-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .wp-jalali-header-icon {
            font-size: 34px;
        }
        
        .wp-jalali-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #a78bfa, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .wp-jalali-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin: 3px 0 0;
        }
        
        .wp-jalali-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.04);
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.06);
        }
        
        .wp-jalali-version {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .wp-jalali-status-dot {
            width: 7px;
            height: 7px;
            background: #34d399;
            border-radius: 50%;
            display: inline-block;
        }
        
        .wp-jalali-status-text {
            font-size: 12px;
            color: #34d399;
        }

        /* ===== وضعیت فونت‌ها ===== */
        .wp-jalali-status-box {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 28px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 22px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .wp-jalali-status-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #94a3b8;
        }
        
        .wp-jalali-status-code {
            background: rgba(0,0,0,0.3);
            padding: 2px 10px;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 12px;
            direction: ltr;
        }
        
        .wp-jalali-status-success {
            color: #34d399;
            font-weight: 500;
        }
        
        .wp-jalali-status-danger {
            color: #f87171;
            font-weight: 500;
        }

        /* ===== کارت‌ها ===== */
        .wp-jalali-card {
            background: rgba(255,255,255,0.04);
            border-radius: 16px;
            padding: 0;
            margin-bottom: 18px;
            border: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }
        
        .wp-jalali-card:hover {
            border-color: rgba(99,102,241,0.15);
        }
        
        .wp-jalali-card-font:hover {
            border-color: rgba(99,102,241,0.2);
        }
        
        .wp-jalali-card-date:hover {
            border-color: rgba(52,211,153,0.2);
        }
        
        .wp-jalali-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 22px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        
        .wp-jalali-card-icon {
            font-size: 18px;
        }
        
        .wp-jalali-card-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
            color: #e2e8f0;
        }
        
        .wp-jalali-badge-success {
            background: rgba(52,211,153,0.12);
            color: #34d399;
            font-size: 11px;
            padding: 2px 12px;
            border-radius: 50px;
            margin-right: auto;
        }
        
        .wp-jalali-badge-warning {
            background: rgba(251,191,36,0.12);
            color: #fbbf24;
            font-size: 11px;
            padding: 2px 12px;
            border-radius: 50px;
            margin-right: auto;
        }
        
        .wp-jalali-card-body {
            padding: 18px 22px 22px;
        }

        /* ===== فیلدها ===== */
        .wp-jalali-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        @media (max-width: 600px) {
            .wp-jalali-grid-2 {
                grid-template-columns: 1fr;
            }
        }
        
        .wp-jalali-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .wp-jalali-label {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        /* ★★★ اصلاح کامل لیست‌های انتخابی ★★★ */
        .wp-jalali-select {
            padding: 9px 14px;
            background: #1e293b !important;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #f1f5f9 !important;
            font-size: 14px;
            width: 100%;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 14px center;
            background-size: 12px;
            padding-left: 40px;
            cursor: pointer;
        }

        /* ★★★ مهم: رنگ آپشن‌ها ★★★ */
        .wp-jalali-select option {
            background: #0f172a !important;
            color: #f1f5f9 !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
        }

        /* ★★★ رنگ آپشن هنگام هاور ★★★ */
        .wp-jalali-select option:hover,
        .wp-jalali-select option:focus,
        .wp-jalali-select option:checked {
            background: #334155 !important;
            color: #ffffff !important;
        }

        /* ★★★ وقتی لیست باز میشه ★★★ */
        .wp-jalali-select:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }

        /* ★★★ استایل خاص برای مرورگر فایرفاکس ★★★ */
        .wp-jalali-select:-moz-focusring {
            color: #f1f5f9 !important;
            background: #1e293b !important;
        }

        /* ★★★ استایل برای مرورگرهای وب‌کیت ★★★ */
        .wp-jalali-select::-webkit-listbox {
            background: #0f172a !important;
            color: #f1f5f9 !important;
        }
        
        /* ★★★ برای اینپوت ها ★★★ */
        .wp-jalali-input {
            padding: 9px 14px;
            background: #1e293b !important;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            color: #f1f5f9 !important;
            font-size: 14px;
            width: 100%;
        }

        .wp-jalali-input:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
            background: #1e293b !important;
        }
        
        .wp-jalali-help {
            font-size: 12px;
            color: #64748b;
            margin: 3px 0 0;
        }
        
        .wp-jalali-help code {
            background: rgba(0,0,0,0.3);
            padding: 1px 8px;
            border-radius: 4px;
            color: #a78bfa;
        }

        /* ===== چک‌باکس‌ها ===== */
        .wp-jalali-checkbox-group {
            display: flex;
            gap: 22px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        
        .wp-jalali-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #cbd5e1;
        }
        
        .wp-jalali-checkbox:hover {
            color: #e2e8f0;
        }
        
        .wp-jalali-checkbox input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        /* ===== رادیو ===== */
        .wp-jalali-radio-group {
            display: flex;
            gap: 18px;
            padding-top: 3px;
            flex-wrap: wrap;
        }
        
        .wp-jalali-radio {
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            font-size: 14px;
            color: #cbd5e1;
        }
        
        .wp-jalali-radio:hover {
            color: #e2e8f0;
        }
        
        .wp-jalali-radio input[type="radio"] {
            width: 17px;
            height: 17px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        /* ===== پیش‌نمایش ===== */
        .wp-jalali-preview-box {
            margin-top: 16px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
        }
        
        .wp-jalali-preview-header {
            padding: 7px 16px;
            background: rgba(255,255,255,0.03);
            font-size: 12px;
            color: #94a3b8;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        
        .wp-jalali-preview-content {
            padding: 16px 20px;
            text-align: center;
        }
        
        .wp-jalali-preview-text {
            font-size: 22px;
            color: #e2e8f0;
            display: inline-block;
            padding: 3px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.03);
        }

        /* ===== تاریخ فعلی ===== */
        .wp-jalali-current-date {
            margin-top: 14px;
            padding: 10px 16px;
            background: rgba(251,191,36,0.06);
            border-radius: 10px;
            border-right: 3px solid #fbbf24;
            color: #fbbf24;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .wp-jalali-current-date strong {
            color: #e2e8f0;
        }

        /* ===== دکمه‌ها ===== */
        .wp-jalali-actions {
            display: flex;
            gap: 14px;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        
        .wp-jalali-btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            border: none !important;
            padding: 12px 40px !important;
            border-radius: 50px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #fff !important;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(34,197,94,0.3);
        }
        
        .wp-jalali-btn-primary:hover {
            background: linear-gradient(135deg, #16a34a, #15803d) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 28px rgba(34,197,94,0.4);
        }
        
        .wp-jalali-btn-primary:active {
            transform: translateY(0);
        }
        
        .wp-jalali-btn-secondary {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            padding: 12px 30px;
            border-radius: 50px;
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .wp-jalali-btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
            border-color: rgba(255,255,255,0.15);
        }

        /* ===== دکمه وردپرس ===== */
        .wp-jalali-actions .button-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            border: none !important;
            padding: 12px 40px !important;
            border-radius: 50px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #fff !important;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(34,197,94,0.3);
            text-shadow: none !important;
        }
        
        .wp-jalali-actions .button-primary:hover {
            background: linear-gradient(135deg, #16a34a, #15803d) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 28px rgba(34,197,94,0.4);
        }
        
        .wp-jalali-actions .button-primary:active {
            transform: translateY(0);
        }

        /* ===== فوتر ===== */
        .wp-jalali-footer {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .wp-jalali-footer strong {
            color: #94a3b8;
        }

        /* ===== اسکرول ===== */
        .wp-jalali-select::-webkit-scrollbar {
            width: 5px;
        }
        
        .wp-jalali-select::-webkit-scrollbar-track {
            background: #0f172a;
            border-radius: 10px;
        }
        
        .wp-jalali-select::-webkit-scrollbar-thumb {
            background: #6366f1;
            border-radius: 10px;
        }
        
        .wp-jalali-select {
            scrollbar-color: #6366f1 #0f172a;
            scrollbar-width: thin;
        }
        </style>

        <!-- جاوااسکریپت پیش‌نمایش -->
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var fontSelect = document.getElementById('wp-jalali-font-select');
            var previewText = document.getElementById('wp-jalali-preview-text');
            var styleTag = document.getElementById('wp-jalali-preview-style');
            
            if (!fontSelect || !previewText) return;
            
            function updatePreview() {
                var selectedFont = fontSelect.value;
                var fontUrl = '<?php echo plugins_url('fonts/', __FILE__); ?>' + selectedFont + '.woff2';
                
                var css = `
                    @font-face {
                        font-family: 'preview-${selectedFont}';
                        src: url('${fontUrl}') format('woff2');
                        font-weight: 400;
                        font-style: normal;
                        font-display: swap;
                    }
                    #wp-jalali-preview-text {
                        font-family: 'preview-${selectedFont}', Tahoma, sans-serif !important;
                        font-weight: 400;
                    }
                `;
                
                styleTag.textContent = css;
            }
            
            fontSelect.addEventListener('change', updatePreview);
            updatePreview();
        });
        </script>

        <style id="wp-jalali-preview-style"></style>
        <?php
    }

    // ========== بقیه توابع ==========

    public function enqueue_fonts($hook) {
        $chosen = get_option('wp_jalali_vazir_font', 'vazir');
        $font_file = plugin_dir_path(__FILE__) . 'fonts/' . $chosen . '.woff2';
        $font_url = plugins_url('fonts/' . $chosen . '.woff2', __FILE__);
        
        if (file_exists($font_file)) {
            $css = "
            @font-face {
                font-family: '" . esc_js($chosen) . "';
                src: url('" . esc_url($font_url) . "') format('woff2');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }
            @font-face {
                font-family: '" . esc_js($chosen) . "';
                src: url('" . esc_url($font_url) . "') format('woff2');
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }
            body, body *:not(.dashicons):not([class*='dashicons']) {
                font-family: '" . esc_js($chosen) . "', Tahoma, Arial, sans-serif !important;
            }
            .dashicons, .dashicons-before::before, [class*='dashicons-']::before,
            #adminmenu .wp-menu-image::before, .ab-icon::before {
                font-family: 'dashicons' !important;
            }";
            
            wp_register_style('wp-font-local-' . $chosen, false);
            wp_enqueue_style('wp-font-local-' . $chosen);
            wp_add_inline_style('wp-font-local-' . $chosen, $css);
        } else {
            $font_urls = [
                'vazir' => 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
                'yekan' => 'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css',
                'shabnam' => 'https://cdn.jsdelivr.net/gh/rastikerdar/shabnam-font@v5.0.1/dist/font-face.css',
                'dana' => 'https://cdn.jsdelivr.net/gh/rastikerdar/dana-font@v1.0.0/dist/font-face.css',
                'iransens' => 'https://cdn.jsdelivr.net/gh/rastikerdar/iransans-font@v2.0.0/dist/font-face.css',
            ];
            
            if (isset($font_urls[$chosen])) {
                wp_enqueue_style('wp-font-cdn-' . $chosen, $font_urls[$chosen], [], null);
            }
        }
    }

    public function force_font_css() {
        $chosen = get_option('wp_jalali_vazir_font', 'vazir');
        $apply_admin = get_option('wp_jalali_apply_to_admin', 'yes');
        $apply_front = get_option('wp_jalali_apply_to_front', 'yes');

        $is_admin = is_admin();
        
        if (($is_admin && $apply_admin !== 'yes') || (!$is_admin && $apply_front !== 'yes')) {
            return;
        }

        $font_file = plugin_dir_path(__FILE__) . 'fonts/' . $chosen . '.woff2';
        $font_exists = file_exists($font_file);
        
        if (!$font_exists) {
            return;
        }

        echo '<style id="wp-jalali-force-font" type="text/css">';
        echo 'body, body *:not(.dashicons):not([class*="dashicons"]):not(.ab-icon):not(.ab-icon::before):not(#adminmenu .wp-menu-image):not(#adminmenu .wp-menu-image::before) {';
        echo '    font-family: "' . esc_attr($chosen) . '", Tahoma, Arial, sans-serif !important;';
        echo '}';
        echo '</style>';
    }

    // ========== توابع تبدیل تاریخ ==========

    public function convert_date_i18n($date, $format, $timestamp, $gmt) {
        if (empty($date) || !$timestamp) return $date;
        $shamsi_date = $this->jdate($format, $timestamp);
        return $shamsi_date ?: $date;
    }

    public function convert_get_the_date($date, $format, $post) {
        if (empty($date)) return $date;
        $timestamp = get_post_time('U', true, $post);
        if (!$timestamp) return $date;
        $format = $format ?: get_option('wp_jalali_date_format', 'j F Y');
        return $this->jdate($format, $timestamp);
    }

    public function convert_get_the_time($time, $format, $post) {
        if (empty($time)) return $time;
        $timestamp = get_post_time('U', true, $post);
        if (!$timestamp) return $time;
        $format = $format ?: get_option('wp_jalali_date_format', 'j F Y');
        return $this->jdate($format, $timestamp);
    }

    public function convert_the_date($date, $format) {
        if (empty($date)) return $date;
        $post_id = get_the_ID();
        if (!$post_id) return $date;
        $timestamp = get_post_time('U', true, $post_id);
        if (!$timestamp) return $date;
        $format = $format ?: get_option('wp_jalali_date_format', 'j F Y');
        return $this->jdate($format, $timestamp);
    }

    public function convert_the_time($time, $format) {
        if (empty($time)) return $time;
        $post_id = get_the_ID();
        if (!$post_id) return $time;
        $timestamp = get_post_time('U', true, $post_id);
        if (!$timestamp) return $time;
        $format = $format ?: get_option('wp_jalali_date_format', 'j F Y');
        return $this->jdate($format, $timestamp);
    }

    public function convert_wp_date($date, $format, $timestamp, $timezone) {
        if (empty($date) || !$timestamp) return $date;
        $shamsi_date = $this->jdate($format, $timestamp);
        return $shamsi_date ?: $date;
    }

    private function jdate($format, $timestamp = null) {
        if (!$timestamp) $timestamp = current_time('timestamp');

        $gy = gmdate('Y', $timestamp);
        $gm = gmdate('m', $timestamp);
        $gd = gmdate('d', $timestamp);
        $gH = gmdate('H', $timestamp);
        $gI = gmdate('i', $timestamp);
        $gS = gmdate('s', $timestamp);

        list($jy, $jm, $jd) = $this->gregorian_to_jalali($gy, $gm, $gd);
        
        $months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        $weekdays = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $wd = date('w', $timestamp);
        $wd_shamsi = ($wd + 1) % 7;

        $replacements = [
            'Y' => $jy,
            'y' => substr($jy, -2),
            'm' => str_pad($jm, 2, '0', STR_PAD_LEFT),
            'n' => $jm,
            'F' => $months[$jm - 1],
            'd' => str_pad($jd, 2, '0', STR_PAD_LEFT),
            'j' => $jd,
            'l' => $weekdays[$wd_shamsi],
            'H' => str_pad($gH, 2, '0', STR_PAD_LEFT),
            'i' => str_pad($gI, 2, '0', STR_PAD_LEFT),
            's' => str_pad($gS, 2, '0', STR_PAD_LEFT),
            'g' => ($gH > 12) ? $gH - 12 : $gH,
            'G' => $gH,
            'A' => ($gH < 12) ? 'صبح' : 'عصر',
            'a' => ($gH < 12) ? 'ق.ظ' : 'ب.ظ',
        ];

        $result = '';
        $len = strlen($format);
        for ($i = 0; $i < $len; $i++) {
            $ch = $format[$i];
            if ($ch == '\\' && $i + 1 < $len) {
                $i++;
                $result .= $format[$i];
                continue;
            }
            $result .= isset($replacements[$ch]) ? $replacements[$ch] : $ch;
        }

        if (get_option('wp_jalali_number_type', 'persian') === 'persian') {
            $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $result = str_replace($en, $fa, $result);
        }

        return $result;
    }

    private function gregorian_to_jalali($gy, $gm, $gd) {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + 365 * $gy + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        
        $jy = -1595 + 33 * (int)($days / 12053);
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        
        return [$jy, $jm, $jd];
    }

    // ========== توابع ستون‌های مدیریت ==========

    public function add_shamsi_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'date') {
                $new_columns['shamsi_date'] = 'تاریخ شمسی';
            }
        }
        return $new_columns;
    }

    public function render_shamsi_column($column_name, $post_id) {
        if ($column_name === 'shamsi_date') {
            $timestamp = get_post_time('U', true, $post_id);
            if ($timestamp && $timestamp > 0) {
                $format = get_option('wp_jalali_date_format', 'j F Y');
                echo esc_html($this->jdate($format, $timestamp));
            }
        }
    }

    public function add_user_shamsi_column($columns) {
        $columns['shamsi_user_date'] = 'تاریخ ثبت‌نام شمسی';
        return $columns;
    }

    public function render_user_shamsi_column($value, $column_name, $user_id) {
        if ($column_name === 'shamsi_user_date') {
            $user = get_userdata($user_id);
            if ($user && isset($user->user_registered)) {
                $timestamp = strtotime($user->user_registered);
                if ($timestamp !== false) {
                    $format = get_option('wp_jalali_date_format', 'j F Y');
                    echo esc_html($this->jdate($format, $timestamp));
                }
            }
        }
        return $value;
    }
}

new WP_HiT_PersianDate();
