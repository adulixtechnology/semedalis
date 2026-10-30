<?php
/**
 * lang.php — Translation registry and t() helper.
 *
 * Included at the bottom of config/config.php, AFTER session_start().
 * Reads $_SESSION['lang'] (set by /lang.php switcher) to pick the
 * active language; defaults to 'en'.
 *
 * Usage:  t('nav.home')   →  "Home"  or  "ዋና ገጽ"
 */

// Guard: only run once per request.
if (!function_exists('t')) {

    // --- Translation arrays -----------------------------------------------
    $GLOBALS['_lang_translations'] = [

        'en' => [

            // Nav
            'nav.home'          => 'Home',
            'nav.gallery'       => 'Gallery',
            'nav.announcements' => 'Announcements',
            'nav.donations'     => 'Donations',
            'nav.about'         => 'About',
            'nav.contact'       => 'Contact',
            'nav.donate'        => 'Donate',

            // Home page
            'home.who_we_are'    => 'Who We Are',
            'home.tagline'       => 'Serving Community<br>Through Faith &amp; Love',
            'home.about_body'    => 'Semidalis is a gospel and volunteer ministry dedicated to spreading hope and serving those in need. Rooted in James 1:27 — <em>"Pure and undefiled religion before God is this: to care for orphans and widows in their distress"</em> — we believe every act of compassion makes a lasting difference.',
            'home.learn_more'    => 'Learn More',
            'home.support_mission' => 'Support Our Mission',
            'home.support_body'  => 'Your generosity helps us continue our gospel and charity work in the community.',
            'home.donate_now'    => 'Donate Now',

            // About page
            'about.our_mission'   => 'Our Mission',
            'about.our_vision'    => 'Our Vision',
            'about.what_we_do'    => 'What We Do',
            'about.secondary_note' => 'We are grateful for every volunteer, partner, and donor who makes this work possible. Together, we continue to be a source of hope and help.',
            'about.verse_body'    => '"Pure and undefiled religion before God is this: to care for orphans and widows in their distress."',
            'about.verse_cite'    => 'James 1:27',
            'about.be_part'       => 'Be Part of the Mission',
            'about.be_part_body'  => 'Whether you give, volunteer, or simply spread the word — every contribution makes a difference in the lives we serve.',
            'about.donate_now'    => 'Donate Now',
            'about.contact_us'    => 'Contact Us',

            // Contact page
            'contact.email'          => 'Email',
            'contact.email_body'     => 'Send us a message any time and we will respond as soon as we can.',
            'contact.phone'          => 'Phone',
            'contact.phone_body'     => 'Call or text us during regular hours and we will be happy to assist.',
            'contact.address'        => 'Address',
            'contact.address_body'   => 'Come visit us at our ministry office. We welcome you warmly.',
            'contact.send_email'     => 'Send Us<br>an Email',
            'contact.email_us_now'   => 'Email Us Now',
            'contact.make_donation'  => 'Make a Donation',
            'contact.ready'          => 'Ready to connect?',
            'contact.cta_body'       => 'The quickest way to reach us is by email. We read every message and aim to reply within one business day.',
            'contact.cta_also'       => 'Want to support our mission?',

            // Donations info page
            'donations.how_to_donate'     => 'How to Donate',
            'donations.three_steps'       => 'Three simple steps and your donation is on its way.',
            'donations.transfer_gift'     => 'Transfer Your Gift',
            'donations.transfer_body'     => 'Send your donation to the bank account listed below. Use your name as the reference so we can match it easily.',
            'donations.capture_screenshot' => 'Capture a Screenshot',
            'donations.capture_body'      => 'Take a clear screenshot of your transfer confirmation or bank receipt.',
            'donations.submit_form'       => 'Submit the Form',
            'donations.submit_body'       => 'Upload your screenshot through our donation form. We will verify and acknowledge your gift as soon as possible.',
            'donations.bank_details'      => 'Bank Details',
            'donations.transfer_to'       => 'Transfer directly to any of the accounts below.',
            'donations.account_name_label'  => 'Account Name',
            'donations.bank_label'          => 'Bank',
            'donations.account_number_label' => 'Account Number',
            'donations.reference_label'     => 'Reference',
            'donations.bank_note'           => 'Please contact us if you need help confirming any of the account details above.',
            'donations.submit_donation'   => 'Submit a Donation',
            'donations.ready'             => 'Ready to give?',
            'donations.submit_your'       => 'Submit Your<br>Donation',
            'donations.submit_desc'       => 'Once you have made your transfer, submit your screenshot and details through our secure form so we can verify and thank you.',
            'donations.verse_body'        => '"Pure and undefiled religion before God is this: to care for orphans and widows in their distress."',
            'donations.verse_cite'        => 'James 1:27',

            // Donate form page
            'donate.give_today'       => 'Give Today',
            'donate.submit_title'     => 'Submit a Donation',
            'donate.submit_sub'       => 'Fill in your details and attach your payment screenshot so we can verify and acknowledge your gift.',
            'donate.why_give'         => 'Why Give?',
            'donate.why_body'         => 'Your donation directly supports our gospel outreach, charity programs, and care for families in need. Every gift — no matter the size — makes a lasting impact.',
            'donate.step1_title'      => 'Transfer to our bank account',
            'donate.step1_body'       => 'Use your name as the payment reference.',
            'donate.step2_title'      => 'Screenshot your confirmation',
            'donate.step2_body'       => 'A clear image of the transfer receipt.',
            'donate.step3_title'      => 'Submit this form',
            'donate.step3_body'       => 'We will verify and confirm your gift.',
            'donate.verse_body'       => '"Pure and undefiled religion before God is this: to care for orphans and widows in their distress."',
            'donate.verse_cite'       => 'James 1:27',
            'donate.view_bank'        => 'View bank details',
            'donate.full_name'        => 'Full Name',
            'donate.email'            => 'Email',
            'donate.phone'            => 'Phone',
            'donate.amount'           => 'Amount',
            'donate.bank_ref'         => 'Bank Reference',
            'donate.payment_screenshot' => 'Payment Screenshot',
            'donate.message'          => 'Message',
            'donate.submit_btn'       => 'Submit Donation',
            'donate.cancel'           => 'Cancel',
            'donate.your_details'     => 'Your Details',
            'donate.payment_details'  => 'Payment Details',
            'donate.optional'         => 'optional',
            'donate.required_field'   => '*',
            'donate.upload_text'      => 'Click to upload or drag and drop',
            'donate.upload_hint'      => 'JPG, PNG, GIF or WebP — max 5 MB',

            // Gallery page
            'gallery.gallery'    => 'Gallery',
            'gallery.back'       => '&larr; Back to Gallery',
            'gallery.photos'     => 'photos',
            'gallery.no_media'   => 'No media in this album yet.',
            'gallery.no_photos'  => 'No photos have been added yet. Please check back soon.',

            // Announcements
            'announcements.announcements'  => 'Announcements',
            'announcements.read_more'      => 'Read More &rarr;',
            'announcements.back'           => '&larr; All announcements',
            'announcements.not_found'      => 'Announcement not found',
            'announcements.not_found_body' => 'The announcement you are looking for does not exist or is no longer available.',
            'announcements.none'           => 'No announcements have been posted yet. Please check back soon.',

            // Footer
            'footer.rights' => 'Semidalis Mission and Volunteer Ministry. All rights reserved.',
            'footer.verse'  => 'James 1:27',

            // Hero
            'hero.title' => 'Semidalis Mission and Volunteer Ministry',
            'hero.verse' => 'James 1:27',
        ],

        'am' => [

            // Nav
            'nav.home'          => 'ዋና ገጽ',
            'nav.gallery'       => 'ጋለሪ',
            'nav.announcements' => 'ማስታወቂያዎች',
            'nav.donations'     => 'ልገሳዎች',
            'nav.about'         => 'ስለ እኛ',
            'nav.contact'       => 'ያግኙን',
            'nav.donate'        => 'ልገሳ',

            // Home page
            'home.who_we_are'    => 'እኛ ማን ነን',
            'home.tagline'       => 'በእምነትና በፍቅር ማህበረሰብን ማገልገል',
            'home.about_body'    => 'ሰሚዳሊስ ተስፋን ለማሰራጨትና ለተቸገሩ ሰዎች ለማገልገል የተሰጠ የወንጌልና የበጎ ፈቃደኛ አገልግሎት ነው። ዕምነቱ ከያዕቆብ 1:27 ላይ ሥር ሰዶ — <em>"ፊቱ ፊቱ ንጹሕ ሃይማኖቱ ይህ ነው: ወላጆቻቸው ለሞቱ ልጆችና ለአርዖቶቻቸው"</em> — ይህን ያምናል።',
            'home.learn_more'    => 'ተጨማሪ ይወቁ',
            'home.support_mission' => 'ተልዕኮአችንን ይደግፉ',
            'home.support_body'  => 'ለጋስነትዎ ወንጌላዊ እና የበጎ አድራጎት ሥራዬን ለመቀጠል ይረዳናል።',
            'home.donate_now'    => 'አሁን ይለግሱ',

            // About page
            'about.our_mission'   => 'ተልዕኮአችን',
            'about.our_vision'    => 'ራዕያችን',
            'about.what_we_do'    => 'የምናደርገው',
            'about.secondary_note' => 'ይህን ሥራ ለሚቻላቸው ለሁሉ ፈቃደኞች፣ አጋሮች እና ለጋሾች ምስጋናችን ይድረስ። አብረን ተስፋ እና እርዳታ ምንጭ መሆናችንን ቀጥለናል።',
            'about.verse_body'    => '"ፊቱ ፊቱ ንጹሕ ሃይማኖቱ ይህ ነው: ወላጆቻቸው ለሞቱ ልጆችና ለአርዖቶቻቸው ለማገልገል።"',
            'about.verse_cite'    => 'ያዕቆብ 1:27',
            'about.be_part'       => 'የተልዕኮው አካል ይሁኑ',
            'about.be_part_body'  => 'ቢሰጡ፣ ቢበጀ ወይም ቃሉን ቢያሰራጩ — እያንዳንዱ አስተዋጽኦ ለምናገለግላቸው ሕይወቶች ልዩነት ያደርጋል።',
            'about.donate_now'    => 'አሁን ይለግሱ',
            'about.contact_us'    => 'ያግኙን',

            // Contact page
            'contact.email'          => 'ኢሜይል',
            'contact.email_body'     => 'በማንኛውም ጊዜ መልዕክት ይላኩልን፤ በተቻለ ፍጥነት እንመልሳለን።',
            'contact.phone'          => 'ስልክ',
            'contact.phone_body'     => 'በሥራ ሰዓቶች ይደውሉልን ወይም ጽሑፍ ይላኩ፤ ደስ ብሎን እናግዛለን።',
            'contact.address'        => 'አድራሻ',
            'contact.address_body'   => 'ወደ ቢሮአችን ይምጡ። ደስ ብሎን እንቀበልዎታለን።',
            'contact.send_email'     => 'ኢሜይል ይላኩልን',
            'contact.email_us_now'   => 'አሁን ኢሜይል ያድርጉ',
            'contact.make_donation'  => 'ልገሳ ያድርጉ',
            'contact.ready'          => 'ለመገናኘት ዝግጁ ነዎት?',
            'contact.cta_body'       => 'ለማግኘት ፈጣኑ መንገድ ኢሜይል ነው። እያንዳንዱ መልዕክት እናነባለን እናም ውስጥ አንድ ቀን ለመምለስ እንሞክራለን።',
            'contact.cta_also'       => 'ተልዕኮአችንን መደገፍ ይፈልጋሉ?',

            // Donations info page
            'donations.how_to_donate'     => 'እንዴት መለገስ',
            'donations.three_steps'       => 'በሦስት ቀላል ደረጃዎች ልገሳዎ በመንገድ ላይ ነው።',
            'donations.transfer_gift'     => 'ልገሳዎን ያስተላልፉ',
            'donations.transfer_body'     => 'ልገሳዎን ከታች ለተዘረዘረው የባንክ ሂሳብ ይላኩ። ስምዎን እንደ ማጣቀሻ ይጠቀሙ ስንዲቻ ሊያዛምደዋ እንችላለን።',
            'donations.capture_screenshot' => 'ቅጽበታዊ ገጽ ይያዙ',
            'donations.capture_body'      => 'የዝውውር ማረጋገጫ ወይም የባንክ ደረሰኝ ግልጽ ቅጽበታዊ ገጽ ይያዙ።',
            'donations.submit_form'       => 'ቅጹን ያስረክቡ',
            'donations.submit_body'       => 'ቅጽበታዊ ገጽዎን ​​በልገሳ ቅጻችን ያስሎጉ። ልገሳዎን በተቻለ ፍጥነት እናረጋግጣለን።',
            'donations.bank_details'      => 'የባንክ ዝርዝሮች',
            'donations.transfer_to'       => 'ከታች ለተዘረዘሩ ሂሳቦች ቀጥታ ያስተላልፉ።',
            'donations.account_name_label'  => 'የሂሳብ ስም',
            'donations.bank_label'          => 'ባንክ',
            'donations.account_number_label' => 'የሂሳብ ቁጥር',
            'donations.reference_label'     => 'ማጣቀሻ',
            'donations.bank_note'           => 'ከላይ ካሉ ሂሳብ ዝርዝሮቻቸን ለማረጋገጥ እርዳታ ከፈለጉ ያግኙን።',
            'donations.submit_donation'   => 'ልገሳ ያስረክቡ',
            'donations.ready'             => 'ለመለገስ ዝግጁ ነዎት?',
            'donations.submit_your'       => 'ልገሳዎን ያስረክቡ',
            'donations.submit_desc'       => 'ዝውውርዎን ካደረጉ በኋላ ቅጽበታዊ ገጽዎን ​​እና ዝርዝሮችዎን ​​ደህንነቱ የተጠበቀ ቅጻችን ​​ያስገቡ።',
            'donations.verse_body'        => '"ፊቱ ፊቱ ንጹሕ ሃይማኖቱ ይህ ነው: ወላጆቻቸው ለሞቱ ልጆችና ለአርዖቶቻቸው ለማገልገል።"',
            'donations.verse_cite'        => 'ያዕቆብ 1:27',

            // Donate form page
            'donate.give_today'       => 'ዛሬ ይለግሱ',
            'donate.submit_title'     => 'ልገሳ ያስረክቡ',
            'donate.submit_sub'       => 'ዝርዝሮችዎን ​​ይሙሉ እና የክፍያ ቅጽበታዊ ገጽዎን ​​ያያይዙ ልገሳዎን ​​ለማረጋገጥ።',
            'donate.why_give'         => 'ለምን ይለግሱ?',
            'donate.why_body'         => 'ልገሳዎ ወንጌሉ ​​ዘርፍ፣ የበጎ አድራጎት ፕሮግራሞቻችን እና ለፈላጊ ቤተሰቦቻቸን እንቅስቃሴን ቀጥታ ይደግፋል።',
            'donate.step1_title'      => 'ወደ ባንክ ሒሳባችን ያዛውሩ',
            'donate.step1_body'       => 'ስምዎን እንደ ክፍያ ማጣቀሻ ይጠቀሙ።',
            'donate.step2_title'      => 'ማረጋገጫዎን ቅጽበታዊ ያድርጉ',
            'donate.step2_body'       => 'የዝውውር ደረሰኝ ግልጽ ምስል።',
            'donate.step3_title'      => 'ይህን ቅጽ ያስረክቡ',
            'donate.step3_body'       => 'ልገሳዎን እናረጋግጣለን።',
            'donate.verse_body'       => '"ፊቱ ፊቱ ንጹሕ ሃይማኖቱ ይህ ነው: ወላጆቻቸው ለሞቱ ልጆችና ለአርዖቶቻቸው ለማገልገል።"',
            'donate.verse_cite'       => 'ያዕቆብ 1:27',
            'donate.view_bank'        => 'የባንክ ዝርዝሮችን ይመልከቱ',
            'donate.full_name'        => 'ሙሉ ስም',
            'donate.email'            => 'ኢሜይል',
            'donate.phone'            => 'ስልክ',
            'donate.amount'           => 'መጠን',
            'donate.bank_ref'         => 'የባንክ ማጣቀሻ',
            'donate.payment_screenshot' => 'የክፍያ ቅጽበታዊ ገጽ',
            'donate.message'          => 'መልዕክት',
            'donate.submit_btn'       => 'ልገሳ ያስረክቡ',
            'donate.cancel'           => 'ሰርዝ',
            'donate.your_details'     => 'ዝርዝሮችዎ',
            'donate.payment_details'  => 'የክፍያ ዝርዝሮች',
            'donate.optional'         => 'አማራጭ',
            'donate.required_field'   => '*',
            'donate.upload_text'      => 'ጠቅ ያድርጉ ወይም ያጎትቱ',
            'donate.upload_hint'      => 'JPG, PNG, GIF ወይም WebP — ከፍተኛ 5 ሜጋ ባይት',

            // Gallery page
            'gallery.gallery'    => 'ጋለሪ',
            'gallery.back'       => '&larr; ወደ ጋለሪ ተመለስ',
            'gallery.photos'     => 'ፎቶዎች',
            'gallery.no_media'   => 'እስካሁን በዚህ አልበም ውስጥ ምንም ሚዲያ የለም።',
            'gallery.no_photos'  => 'እስካሁን ምንም ፎቶዎች አልተጨመሩም። እባክዎ ብዙ ሳይቆዩ ይመልከቱ።',

            // Announcements
            'announcements.announcements'  => 'ማስታወቂያዎች',
            'announcements.read_more'      => 'ተጨማሪ ያንብቡ &rarr;',
            'announcements.back'           => '&larr; ወደ ማስታወቂያዎች ተመለስ',
            'announcements.not_found'      => 'ማስታወቂያ አልተገኘም',
            'announcements.not_found_body' => 'የሚፈልጉት ማስታወቂያ የለም ወይም ከእንግዲህ አይገኝም።',
            'announcements.none'           => 'እስካሁን ምንም ማስታወቂያዎች አልተለጠፉም። እባክዎ ብዙ ሳይቆዩ ይመልከቡ።',

            // Footer
            'footer.rights' => 'ሰሚዳሊስ ሚሽን እና የበጎ ፈቃደኞች አገልግሎት። መብቶቹ ሁሉ ተጠብቀዋል።',
            'footer.verse'  => 'ያዕቆብ 1:27',

            // Hero
            'hero.title' => 'ሰሚዳሊስ ሚሽን እና የበጎ ፈቃደኞች አገልግሎት',
            'hero.verse' => 'ያዕቆብ 1:27',
        ],
    ];

    /**
     * Return the translated string for the given key.
     * Falls back to English if the key is missing in the active language.
     * Falls back to the key itself if missing in both.
     *
     * KEY CATEGORIES — two mutually exclusive types:
     *   • Plain-text keys  — safe to wrap in e() for HTML output.
     *   • HTML-bearing keys — embed raw HTML tags or entities; must NOT be
     *     wrapped in e() (that would double-encode them). HTML-bearing keys in
     *     this registry include: home.tagline, home.about_body, contact.send_email,
     *     donations.submit_your, gallery.back, announcements.read_more,
     *     announcements.back (and their 'am' equivalents).
     *     When adding a new key, note its category here and at its call site.
     */
    function t(string $key): string
    {
        $lang = $_SESSION['lang'] ?? 'en';
        $translations = $GLOBALS['_lang_translations'];

        if (isset($translations[$lang][$key])) {
            return $translations[$lang][$key];
        }
        // Graceful fallback to English
        if (isset($translations['en'][$key])) {
            return $translations['en'][$key];
        }
        // Last resort: return the key
        return $key;
    }

} // end if (!function_exists('t'))

// --- Set active language from request / session ---------------------------
if (session_status() === PHP_SESSION_ACTIVE) {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    } elseif (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'en';
    }
}
