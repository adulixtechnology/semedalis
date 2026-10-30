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

            // Donate form — validation error messages
            'donate.err_name_required'   => 'Please enter your name.',
            'donate.err_name_long'       => 'Name must be 150 characters or fewer.',
            'donate.err_email_long'      => 'Email must be 150 characters or fewer.',
            'donate.err_email_invalid'   => 'Please enter a valid email address.',
            'donate.err_phone_long'      => 'Phone must be 50 characters or fewer.',
            'donate.err_amount_invalid'  => 'Enter a valid amount (up to 2 decimal places).',
            'donate.err_ref_long'        => 'Bank reference must be 255 characters or fewer.',
            'donate.err_msg_long'        => 'Message must be 65000 characters or fewer.',
            'donate.err_screenshot_required' => 'Please upload a payment screenshot.',
            'donate.err_form_generic'    => 'Sorry, we could not submit your donation right now. Please try again.',

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

            // Admin general
            'admin.dashboard'      => 'Dashboard',
            'admin.logout'         => 'Logout',
            'admin.save'           => 'Save',
            'admin.cancel'         => 'Cancel',
            'admin.delete'         => 'Delete',
            'admin.edit'           => 'Edit',
            'admin.create'         => 'Create',
            'admin.update'         => 'Update',
            'admin.actions'        => 'Actions',
            'admin.status'         => 'Status',
            'admin.date'           => 'Date',
            'admin.yes'            => 'Yes',
            'admin.no'             => 'No',
            'admin.confirm_delete' => 'Are you sure?',
            'admin.no_records'     => 'No records found.',
            'admin.success'        => 'Success',
            'admin.error'          => 'Error',

            // Dashboard
            'admin.dash.welcome'              => 'Welcome back',
            'admin.dash.pending_donations'    => 'Pending Donations',
            'admin.dash.total_donations'      => 'Total Donations',
            'admin.dash.active_announcements' => 'Active Announcements',
            'admin.dash.gallery_albums'       => 'Gallery Albums',
            'admin.dash.slider_images'        => 'Slider Images',
            'admin.dash.total_users'          => 'Total Users',
            'admin.dash.recent_donations'     => 'Recent Donations',
            'admin.dash.recent_announcements' => 'Recent Announcements',
            'admin.dash.view_all'             => 'View all',
            'admin.dash.quick_actions'        => 'Quick Actions',
            'admin.dash.no_donations'         => 'No donations submitted yet.',
            'admin.dash.no_announcements'     => 'No announcements posted yet.',
            'admin.dash.new_announcement'     => 'New Announcement',
            'admin.dash.add_gallery'          => 'Add Gallery Album',
            'admin.dash.manage_slider'        => 'Manage Slider',
            'admin.dash.review_donations'     => 'Review Donations',
            'admin.dash.manage_users'         => 'Manage Users',
            'admin.dash.site_settings'        => 'Site Settings',
            'admin.dash.publish_public'       => 'Publish to public',
            'admin.dash.upload_photos'        => 'Upload photos',
            'admin.dash.home_page_images'     => 'Home page images',
            'admin.dash.pending'              => 'pending',
            'admin.dash.registered'           => 'registered',
            'admin.dash.contact_about_donate' => 'Contact, about, donate',
            'admin.dash.donor'                => 'Donor',
            'admin.dash.amount'               => 'Amount',
            'admin.dash.title_col'            => 'Title',

            // Slider
            'admin.slider.title'      => 'Slider Images',
            'admin.slider.sub'        => 'Upload images for the full-screen homepage slider.',
            'admin.slider.add'        => 'Add Slider Image',
            'admin.slider.upload_btn' => 'Upload Image',
            'admin.slider.all_images' => 'All Slider Images',
            'admin.slider.activate'   => 'Activate',
            'admin.slider.deactivate' => 'Deactivate',
            'admin.slider.no_images'  => 'No slider images yet. Upload one using the form.',

            // Gallery
            'admin.gallery.title'        => 'Gallery',
            'admin.gallery.create_album' => 'Create Album',
            'admin.gallery.edit_album'   => 'Edit Album',
            'admin.gallery.add_more'     => 'Add More Media',
            'admin.gallery.album_title'  => 'Album Title',
            'admin.gallery.category'     => 'Category',
            'admin.gallery.description'  => 'Description',
            'admin.gallery.photos_videos'=> 'Photos & Videos',
            'admin.gallery.save_changes' => 'Save Changes',
            'admin.gallery.back'         => '← Back to all albums',
            'admin.gallery.all_albums'   => 'All Albums',
            'admin.gallery.items'        => 'items',
            'admin.gallery.photos'       => 'photos',
            'admin.gallery.no_albums'    => 'No albums yet. Create your first album.',
            'admin.gallery.no_media'     => 'No media in this album yet.',

            // Announcements
            'admin.ann.title'           => 'Announcements',
            'admin.ann.new'             => 'New Announcement',
            'admin.ann.edit'            => 'Edit Announcement',
            'admin.ann.ann_title'       => 'Title',
            'admin.ann.content'         => 'Content',
            'admin.ann.language'        => 'Language',
            'admin.ann.lang_en'         => 'English',
            'admin.ann.lang_am'         => 'Amharic',
            'admin.ann.status_active'   => 'Active',
            'admin.ann.status_inactive' => 'Inactive',
            'admin.ann.image'           => 'Image',
            'admin.ann.publish_btn'     => 'Publish Announcement',
            'admin.ann.update_btn'      => 'Update Announcement',
            'admin.ann.cancel_edit'     => 'Cancel edit',
            'admin.ann.all'             => 'All Announcements',
            'admin.ann.published'       => 'Published',
            'admin.ann.no_ann'          => 'No announcements yet.',

            // Donations
            'admin.don.title'        => 'Donations',
            'admin.don.donor'        => 'Donor',
            'admin.don.amount'       => 'Amount',
            'admin.don.reference'    => 'Reference',
            'admin.don.screenshot'   => 'Screenshot',
            'admin.don.notes'        => 'Notes',
            'admin.don.submitted'    => 'Submitted',
            'admin.don.update_btn'   => 'Update',
            'admin.don.save'         => 'Save',
            'admin.don.pending'      => 'pending',
            'admin.don.verified'     => 'verified',
            'admin.don.rejected'     => 'rejected',
            'admin.don.no_donations' => 'No donations have been submitted yet.',

            // Users
            'admin.users.title'      => 'Users',
            'admin.users.create'     => 'Create New User',
            'admin.users.create_sub' => 'Add an admin or user account to the system.',
            'admin.users.username'   => 'Username',
            'admin.users.email'      => 'Email',
            'admin.users.password'   => 'Password',
            'admin.users.role'       => 'Role',
            'admin.users.role_admin' => 'Admin',
            'admin.users.role_user'  => 'User',
            'admin.users.created'    => 'Created',
            'admin.users.create_btn' => 'Create User',
            'admin.users.all'        => 'All Users',
            'admin.users.you'        => '(you)',
            'admin.users.no_users'   => 'No users found.',

            // Settings
            'admin.settings.title'          => 'Settings',
            'admin.settings.contact_tab'    => 'Contact',
            'admin.settings.donation_tab'   => 'Donation',
            'admin.settings.about_tab'      => 'About',
            'admin.settings.save_btn'       => 'Save Settings',
            'admin.settings.contact_email'  => 'Contact Email',
            'admin.settings.contact_phones' => 'Phone Numbers',
            'admin.settings.contact_address'=> 'Address',
            'admin.settings.add_phone'      => '+ Add Phone',
            'admin.settings.bank_accounts'  => 'Bank Accounts',
            'admin.settings.add_bank'       => '+ Add Bank Account',
            'admin.settings.account_name'   => 'Account Name',
            'admin.settings.bank_name'      => 'Bank Name',
            'admin.settings.account_number' => 'Account Number',
            'admin.settings.reference'      => 'Reference',
            'admin.settings.about_intro'    => 'Intro Text',
            'admin.settings.mission'        => 'Mission',
            'admin.settings.vision'         => 'Vision',
            'admin.settings.work_title'     => 'Work Section Title',
            'admin.settings.work_desc'      => 'Work Section Description',
            'admin.settings.work_items'     => 'Work Items',
            'admin.settings.add_item'       => '+ Add Item',

            // Login
            'admin.login.title'    => 'Sign In',
            'admin.login.sub'      => 'Enter your credentials to access the dashboard.',
            'admin.login.email'    => 'Email Address',
            'admin.login.password' => 'Password',
            'admin.login.btn'      => 'Sign In',
            'admin.login.error'    => 'Invalid email or password.',

            // Sidebar
            'admin.sidebar.main'          => 'Main',
            'admin.sidebar.content'       => 'Content',
            'admin.sidebar.system'        => 'System',
            'admin.sidebar.dashboard'     => 'Dashboard',
            'admin.sidebar.slider'        => 'Slider',
            'admin.sidebar.gallery'       => 'Gallery',
            'admin.sidebar.announcements' => 'Announcements',
            'admin.sidebar.donations'     => 'Donations',
            'admin.sidebar.users'         => 'Users',
            'admin.sidebar.settings'      => 'Settings',
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

            // Donate form — validation error messages
            'donate.err_name_required'   => 'እባክዎ ስምዎን ያስገቡ።',
            'donate.err_name_long'       => 'ስም ከ150 ቁምፊዎች መብለጥ አይችልም።',
            'donate.err_email_long'      => 'ኢሜይል ከ150 ቁምፊዎች መብለጥ አይችልም።',
            'donate.err_email_invalid'   => 'እባክዎ ትክክለኛ ኢሜይል አድራሻ ያስገቡ።',
            'donate.err_phone_long'      => 'ስልክ ከ50 ቁምፊዎች መብለጥ አይችልም።',
            'donate.err_amount_invalid'  => 'ትክክለኛ መጠን ያስገቡ (እስከ 2 ትንንሽ ቁጥሮች)።',
            'donate.err_ref_long'        => 'የባንክ ማጣቀሻ ከ255 ቁምፊዎች መብለጥ አይችልም።',
            'donate.err_msg_long'        => 'መልዕክት ከ65000 ቁምፊዎች መብለጥ አይችልም።',
            'donate.err_screenshot_required' => 'እባክዎ የክፍያ ቅጽበታዊ ገጽ ያስሉጉ።',
            'donate.err_form_generic'    => 'ይቅርታ፤ ልገሳዎን አሁን ማስረከብ አልቻልንም። እባክዎ እንደገና ይሞክሩ።',

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

            // Admin general
            'admin.dashboard'      => 'ዳሽቦርድ',
            'admin.logout'         => 'ውጣ',
            'admin.save'           => 'አስቀምጥ',
            'admin.cancel'         => 'ሰርዝ',
            'admin.delete'         => 'ሰርዝ',
            'admin.edit'           => 'አርም',
            'admin.create'         => 'ፍጠር',
            'admin.update'         => 'አዘምን',
            'admin.actions'        => 'ተግባሮች',
            'admin.status'         => 'ሁኔታ',
            'admin.date'           => 'ቀን',
            'admin.yes'            => 'አዎ',
            'admin.no'             => 'አይ',
            'admin.confirm_delete' => 'እርግጠኛ ነዎት?',
            'admin.no_records'     => 'ምንም መዝገቦች አልተገኙም።',
            'admin.success'        => 'ተሳክቷል',
            'admin.error'          => 'ስህተት',

            // Dashboard
            'admin.dash.welcome'              => 'እንኳን ደህና መጡ',
            'admin.dash.pending_donations'    => 'በጥበቃ ላይ ያሉ ልገሳዎች',
            'admin.dash.total_donations'      => 'ጠቅላላ ልገሳዎች',
            'admin.dash.active_announcements' => 'ንቁ ማስታወቂያዎች',
            'admin.dash.gallery_albums'       => 'የጋለሪ አልበሞች',
            'admin.dash.slider_images'        => 'የስላይደር ምስሎች',
            'admin.dash.total_users'          => 'ጠቅላላ ተጠቃሚዎች',
            'admin.dash.recent_donations'     => 'የቅርብ ጊዜ ልገሳዎች',
            'admin.dash.recent_announcements' => 'የቅርብ ጊዜ ማስታወቂያዎች',
            'admin.dash.view_all'             => 'ሁሉንም ይመልከቱ',
            'admin.dash.quick_actions'        => 'ፈጣን ተግባሮች',
            'admin.dash.no_donations'         => 'እስካሁን ምንም ልገሳዎች አልቀረቡም።',
            'admin.dash.no_announcements'     => 'እስካሁን ምንም ማስታወቂያዎች አልተለጠፉም።',
            'admin.dash.new_announcement'     => 'አዲስ ማስታወቂያ',
            'admin.dash.add_gallery'          => 'የጋለሪ አልበም ጨምር',
            'admin.dash.manage_slider'        => 'ስላይደር አስተዳድር',
            'admin.dash.review_donations'     => 'ልገሳዎችን ይገምግሙ',
            'admin.dash.manage_users'         => 'ተጠቃሚዎችን አስተዳድር',
            'admin.dash.site_settings'        => 'የጣቢያ ቅንብሮች',
            'admin.dash.publish_public'       => 'ለህዝብ አትም',
            'admin.dash.upload_photos'        => 'ፎቶዎች ጫን',
            'admin.dash.home_page_images'     => 'የዋና ገጽ ምስሎች',
            'admin.dash.pending'              => 'በጥበቃ ላይ',
            'admin.dash.registered'           => 'የተመዘገቡ',
            'admin.dash.contact_about_donate' => 'ያግኙን፣ ስለ እኛ፣ ልገሳ',
            'admin.dash.donor'                => 'ለጋሽ',
            'admin.dash.amount'               => 'መጠን',
            'admin.dash.title_col'            => 'ርዕስ',

            // Slider
            'admin.slider.title'      => 'የስላይደር ምስሎች',
            'admin.slider.sub'        => 'ለሙሉ ማያ ፊት ዋና ገጽ ስላይደር ምስሎች ጫን።',
            'admin.slider.add'        => 'የስላይደር ምስል ጨምር',
            'admin.slider.upload_btn' => 'ምስል ጫን',
            'admin.slider.all_images' => 'ሁሉም የስላይደር ምስሎች',
            'admin.slider.activate'   => 'አንቃ',
            'admin.slider.deactivate' => 'አቦዝን',
            'admin.slider.no_images'  => 'እስካሁን የስላይደር ምስሎች የሉም።',

            // Gallery
            'admin.gallery.title'        => 'ጋለሪ',
            'admin.gallery.create_album' => 'አልበም ፍጠር',
            'admin.gallery.edit_album'   => 'አልበም አርም',
            'admin.gallery.add_more'     => 'ተጨማሪ ሚዲያ ጨምር',
            'admin.gallery.album_title'  => 'የአልበም ርዕስ',
            'admin.gallery.category'     => 'ምድብ',
            'admin.gallery.description'  => 'መግለጫ',
            'admin.gallery.photos_videos'=> 'ፎቶዎች እና ቪዲዮዎች',
            'admin.gallery.save_changes' => 'ለውጦችን አስቀምጥ',
            'admin.gallery.back'         => '← ወደ ሁሉም አልበሞች',
            'admin.gallery.all_albums'   => 'ሁሉም አልበሞች',
            'admin.gallery.items'        => 'ንጥሎች',
            'admin.gallery.photos'       => 'ፎቶዎች',
            'admin.gallery.no_albums'    => 'እስካሁን አልበሞች የሉም።',
            'admin.gallery.no_media'     => 'እስካሁን ምንም ሚዲያ የለም።',

            // Announcements
            'admin.ann.title'           => 'ማስታወቂያዎች',
            'admin.ann.new'             => 'አዲስ ማስታወቂያ',
            'admin.ann.edit'            => 'ማስታወቂያ አርም',
            'admin.ann.ann_title'       => 'ርዕስ',
            'admin.ann.content'         => 'ይዘት',
            'admin.ann.language'        => 'ቋንቋ',
            'admin.ann.lang_en'         => 'እንግሊዝኛ',
            'admin.ann.lang_am'         => 'አማርኛ',
            'admin.ann.status_active'   => 'ንቁ',
            'admin.ann.status_inactive' => 'ቦዘነ',
            'admin.ann.image'           => 'ምስል',
            'admin.ann.publish_btn'     => 'ማስታወቂያ አትም',
            'admin.ann.update_btn'      => 'ማስታወቂያ አዘምን',
            'admin.ann.cancel_edit'     => 'ማረምን ሰርዝ',
            'admin.ann.all'             => 'ሁሉም ማስታወቂያዎች',
            'admin.ann.published'       => 'የታተመ',
            'admin.ann.no_ann'          => 'እስካሁን ምንም ማስታወቂያዎች የሉም።',

            // Donations
            'admin.don.title'        => 'ልገሳዎች',
            'admin.don.donor'        => 'ለጋሽ',
            'admin.don.amount'       => 'መጠን',
            'admin.don.reference'    => 'ማጣቀሻ',
            'admin.don.screenshot'   => 'ቅጽበታዊ ገጽ',
            'admin.don.notes'        => 'ማስታወሻዎች',
            'admin.don.submitted'    => 'የቀረበ',
            'admin.don.update_btn'   => 'አዘምን',
            'admin.don.save'         => 'አስቀምጥ',
            'admin.don.pending'      => 'በጥበቃ ላይ',
            'admin.don.verified'     => 'የተረጋገጠ',
            'admin.don.rejected'     => 'የተቀናቀነ',
            'admin.don.no_donations' => 'እስካሁን ምንም ልገሳዎች አልቀረቡም።',

            // Users
            'admin.users.title'      => 'ተጠቃሚዎች',
            'admin.users.create'     => 'አዲስ ተጠቃሚ ፍጠር',
            'admin.users.create_sub' => 'ወደ ስርዓቱ ተጠቃሚ ጨምር።',
            'admin.users.username'   => 'የተጠቃሚ ስም',
            'admin.users.email'      => 'ኢሜይል',
            'admin.users.password'   => 'የይለፍ ቃል',
            'admin.users.role'       => 'ሚና',
            'admin.users.role_admin' => 'አስተዳዳሪ',
            'admin.users.role_user'  => 'ተጠቃሚ',
            'admin.users.created'    => 'የተፈጠረ',
            'admin.users.create_btn' => 'ተጠቃሚ ፍጠር',
            'admin.users.all'        => 'ሁሉም ተጠቃሚዎች',
            'admin.users.you'        => '(እርስዎ)',
            'admin.users.no_users'   => 'ምንም ተጠቃሚዎች አልተገኙም።',

            // Settings
            'admin.settings.title'          => 'ቅንብሮች',
            'admin.settings.contact_tab'    => 'ያግኙን',
            'admin.settings.donation_tab'   => 'ልገሳ',
            'admin.settings.about_tab'      => 'ስለ እኛ',
            'admin.settings.save_btn'       => 'ቅንብሮችን አስቀምጥ',
            'admin.settings.contact_email'  => 'የዕውቂያ ኢሜይል',
            'admin.settings.contact_phones' => 'ስልክ ቁጥሮች',
            'admin.settings.contact_address'=> 'አድራሻ',
            'admin.settings.add_phone'      => '+ ስልክ ጨምር',
            'admin.settings.bank_accounts'  => 'የባንክ ሒሳቦች',
            'admin.settings.add_bank'       => '+ የባንክ ሒሳብ ጨምር',
            'admin.settings.account_name'   => 'የሒሳብ ስም',
            'admin.settings.bank_name'      => 'የባንክ ስም',
            'admin.settings.account_number' => 'የሒሳብ ቁጥር',
            'admin.settings.reference'      => 'ማጣቀሻ',
            'admin.settings.about_intro'    => 'የመግቢያ ጽሑፍ',
            'admin.settings.mission'        => 'ተልዕኮ',
            'admin.settings.vision'         => 'ራዕይ',
            'admin.settings.work_title'     => 'የሥራ ክፍል ርዕስ',
            'admin.settings.work_desc'      => 'የሥራ ክፍል መግለጫ',
            'admin.settings.work_items'     => 'የሥራ ንጥሎች',
            'admin.settings.add_item'       => '+ ንጥል ጨምር',

            // Login
            'admin.login.title'    => 'ግባ',
            'admin.login.sub'      => 'ዳሽቦርዱን ለማግኘት መረጃዎን ያስገቡ።',
            'admin.login.email'    => 'የኢሜይል አድራሻ',
            'admin.login.password' => 'የይለፍ ቃል',
            'admin.login.btn'      => 'ግባ',
            'admin.login.error'    => 'ልክ ያልሆነ ኢሜይል ወይም የይለፍ ቃል።',

            // Sidebar
            'admin.sidebar.main'          => 'ዋና',
            'admin.sidebar.content'       => 'ይዘት',
            'admin.sidebar.system'        => 'ስርዓት',
            'admin.sidebar.dashboard'     => 'ዳሽቦርድ',
            'admin.sidebar.slider'        => 'ስላይደር',
            'admin.sidebar.gallery'       => 'ጋለሪ',
            'admin.sidebar.announcements' => 'ማስታወቂያዎች',
            'admin.sidebar.donations'     => 'ልገሳዎች',
            'admin.sidebar.users'         => 'ተጠቃሚዎች',
            'admin.sidebar.settings'      => 'ቅንብሮች',
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
