<?php
/**
 * Plugin Name: WP-HiTPersianDate
 * Description: تبدیل تاریخ به شمسی + فونت حرفه‌ای + رفع خطای gmdate() در داشبورد + صفحه تنظیمات گرافیکی.
 * Version: 1.3
 * Author: Hossein.IT
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class WP_Jalali_Vazir_Pro_Final3 {
    private $fonts = ['vazir'=>'وزیر','yekan'=>'یکان','shabnam'=>'شبنم','dana'=>'دانا','iransens'=>'ایران‌سنس'];

    public function __construct() {
        add_action('admin_menu', [$this,'settings_page']);
        add_action('admin_init', [$this,'register_settings']);
        add_action('wp_enqueue_scripts', [$this,'enqueue_fonts']);
        add_action('admin_enqueue_scripts', [$this,'enqueue_fonts']);
        add_filter('get_the_date', [$this,'to_jalali'], 10, 3);
        add_filter('get_the_time', [$this,'to_jalali'], 10, 3);
        add_filter('the_time', [$this,'to_jalali'], 10, 3);
        add_filter('the_date', [$this,'to_jalali'], 10, 3);
    }

    public function register_settings() {
        register_setting('wp_jalali_vazir_options','wp_jalali_vazir_font');
        register_setting('wp_jalali_vazir_options','wp_jalali_date_format');
        register_setting('wp_jalali_vazir_options','wp_jalali_number_type');
    }

    public function settings_page() {
        add_options_page('تنظیمات فونت و تاریخ','فونت و تاریخ شمسی','manage_options','wp-jalali-vazir-final3',[$this,'settings_html']);
    }

    public function settings_html() { ?>
        <div class="wrap wp-jalali-container">
            <h1>⚙️ تنظیمات فونت و تاریخ شمسی</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_jalali_vazir_options'); ?>

                <div class="wp-jalali-card">
                    <h2>🔠 انتخاب فونت</h2>
                    <select name="wp_jalali_vazir_font" class="wp-jalali-select">
                        <?php foreach($this->fonts as $key=>$label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected(get_option('wp_jalali_vazir_font','vazir'),$key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">فونت مورد علاقه خود را برای کل سایت و مدیریت انتخاب کنید.</p>
                </div>

                <div class="wp-jalali-card">
                    <h2>📅 تنظیمات تاریخ</h2>
                    <label>
                        فرمت تاریخ:<br>
                        <input type="text" name="wp_jalali_date_format" 
                            value="<?php echo esc_attr(get_option('wp_jalali_date_format','j F Y')); ?>" 
                            class="wp-jalali-input">
                    </label>
                    <p class="description">نمونه: <code>l j F Y</code> → دوشنبه ۲۵ شهریور ۱۴۰۴</p>

                    <div class="wp-jalali-radios" style="margin-top:15px;">
                        نوع اعداد:<br>
                        <?php $num_type = get_option('wp_jalali_number_type','persian'); ?>
                        <label><input type="radio" name="wp_jalali_number_type" value="persian" <?php checked($num_type,'persian'); ?>> فارسی (۰۱۲۳۴)</label>
                        <label><input type="radio" name="wp_jalali_number_type" value="latin" <?php checked($num_type,'latin'); ?>> لاتین (01234)</label>
                    </div>
                </div>

                <?php submit_button('💾 ذخیره تغییرات'); ?>
            </form>
        </div>
    <?php }

    public function enqueue_fonts($hook) {
        $chosen = get_option('wp_jalali_vazir_font','vazir');
        $url = plugins_url('fonts/'.$chosen.'.woff2',__FILE__);

        $css = "
        @font-face {
            font-family:'$chosen';
            src: url('$url') format('woff2');
            font-weight:normal;
            font-style:normal;
        }
        body, td, th, p, div, span, input, select, textarea, button, #adminmenu li a, .wrap {
            font-family:'$chosen', sans-serif !important;
            direction:rtl;
        }
        .ab-icon, .dashicons, .dashicons-before, [class*='dashicons-'] {
            font-family: dashicons !important;
        }";

        wp_register_style('wp-jalali-vazir-style', false);
        wp_enqueue_style('wp-jalali-vazir-style');
        wp_add_inline_style('wp-jalali-vazir-style',$css);

        if ($hook === 'settings_page_wp-jalali-vazir-final3') {
            wp_enqueue_style('wp-jalali-admin-style', plugins_url('admin-style.css', __FILE__));
        }
    }

    public function to_jalali($the_date,$d='',$post=null){
        $timestamp = get_post_time('U', true, $post);

        // بررسی معتبر بودن timestamp
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return $the_date;
        }

        $timestamp = (int) $timestamp;
        $format = $d ?: get_option('wp_jalali_date_format','j F Y');
        return $this->jdate($format, $timestamp);
    }

    private function jdate($format, $timestamp=null){
        if (!$timestamp) $timestamp=time();

        $g = gmdate('Y-n-j-G-i-s',$timestamp + (int)date('Z'));
        list($gy,$gm,$gd,$gH,$gI,$gS)=explode('-',$g);
        list($jy,$jm,$jd)=$this->gregorian_to_jalali($gy,$gm,$gd);
        $months=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        $weekdays=['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
        $wd=date('w',$timestamp);

        $replacements=[
            'Y'=>$jy,'y'=>substr($jy,-2),'m'=>str_pad($jm,2,'0',STR_PAD_LEFT),
            'n'=>$jm,'F'=>$months[$jm-1],'d'=>str_pad($jd,2,'0',STR_PAD_LEFT),'j'=>$jd,
            'l'=>$weekdays[$wd],'H'=>str_pad($gH,2,'0',STR_PAD_LEFT),'i'=>$gI,'s'=>$gS,
            'g'=>$gH,'A'=>($gH<12)?'صبح':'عصر','a'=>($gH<12)?'ق.ظ':'ب.ظ'
        ];

        $result='';
        $len=strlen($format);
        for($i=0;$i<$len;$i++){
            $ch=$format[$i];
            if($ch=='\\\\' && $i+1<$len){$i++; $result.=$format[$i]; continue;}
            $result.=isset($replacements[$ch])?$replacements[$ch]:date($ch,$timestamp);
        }

        // تبدیل اعداد فقط در محیط سایت (Frontend)
        if (!is_admin() && get_option('wp_jalali_number_type','persian')==='persian') {
            $en=['0','1','2','3','4','5','6','7','8','9'];
            $fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            $result=str_replace($en,$fa,$result);
        }

        return $result;
    }

    private function gregorian_to_jalali($gy,$gm,$gd){
        $g_d_m=[0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2=($gm>2)?($gy+1):$gy;
        $days=355666+365*$gy+(int)(($gy2+3)/4)-(int)(($gy2+99)/100)+(int)(($gy2+399)/400)+$gd+$g_d_m[$gm-1];
        $jy=-1595+33*(int)($days/12053); $days%=12053;
        $jy+=4*(int)($days/1461); $days%=1461;
        if($days>365){$jy+=(int)(($days-1)/365);$days=($days-1)%365;}
        $jm=($days<186)?1+(int)($days/31):7+(int)(($days-186)/30);
        $jd=1+(($days<186)?($days%31):(($days-186)%30));
        return [$jy,$jm,$jd];
    }
}

new WP_Jalali_Vazir_Pro_Final3();
