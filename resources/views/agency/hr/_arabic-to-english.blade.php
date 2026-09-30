{{--
    Arabic → English helper for the HR form (offline, no external API).

    Used by the Arabic↔English auto-fill script in _form.blade.php when the user
    types/pastes an ARABIC value (Sponsor Name, Profession, Qualification …) or
    an Arabic/Hijri date. Order of attempts:
      1. exact phrase dictionary (common Saudi visa professions / qualifications)
      2. word-by-word glossary (English word order for professions)
      3. known Arabic names + "ال" → "Al …" rule (sponsor / person names)
      4. readable phonetic fallback for anything unknown
    Every result stays fully editable in the English box.

    Exposes window.HrArEn = { profession, name, phrase, date }.
--}}
<script>
(function () {
    // Remove tashkeel/tatweel and unify letter variants for lookups.
    function norm(s) {
        return String(s || '')
            .replace(/[ً-ٰٟـ]/g, '')
            .replace(/[أإآ]/g, 'ا')
            .replace(/ى(?=\s|$)/g, 'ي')
            .replace(/\s+/g, ' ')
            .trim();
    }
    function title(s) {
        return s.split(' ').filter(Boolean)
            .map(function (w) { return /^(and|of|for|the)$/i.test(w) ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1); })
            .join(' ');
    }

    // ── 1. Exact profession phrases (Arabic as printed on Saudi visas) ──
    var PROFESSION = {
        'عامل': 'Worker', 'عاملة': 'Worker', 'عامل عادي': 'Ordinary Worker',
        'عامل منزلي': 'Domestic Worker', 'عاملة منزلية': 'Housemaid', 'خادمة': 'Housemaid', 'خادم': 'House Boy',
        'مدبرة منزل': 'Housekeeper', 'مربية': 'Nanny', 'مربية اطفال': 'Nanny', 'جليسة اطفال': 'Babysitter',
        'سائق': 'Driver', 'سائق خاص': 'Private Driver', 'سائق منزلي': 'House Driver', 'سائق عائلة': 'Family Driver',
        'سائق ثقيل': 'Heavy Driver', 'سائق خفيف': 'Light Driver', 'سائق شاحنة': 'Truck Driver',
        'سائق شاحنة ثقيلة': 'Heavy Truck Driver', 'سائق نقل ثقيل': 'Heavy Transport Driver',
        'سائق نقل خفيف': 'Light Transport Driver', 'سائق حافلة': 'Bus Driver', 'سائق باص': 'Bus Driver',
        'سائق سيارة': 'Car Driver', 'سائق سيارة اجرة': 'Taxi Driver', 'سائق معدات ثقيلة': 'Heavy Equipment Driver',
        'سائق رافعة': 'Crane Driver', 'سائق رافعة شوكية': 'Forklift Driver', 'مشغل رافعة شوكية': 'Forklift Operator',
        'عامل نظافة': 'Cleaner', 'عامل تنظيف': 'Cleaning Worker', 'منظف': 'Cleaner',
        'عامل تنظيف مركبات': 'Vehicle Cleaning Worker', 'عامل تنظيف سيارات': 'Car Cleaning Worker',
        'عامل غسيل سيارات': 'Car Washing Worker', 'عامل تنظيف مباني': 'Building Cleaning Worker',
        'عامل تحميل وتنزيل': 'Loading and Unloading Worker', 'عامل تحميل': 'Loading Worker',
        'عامل بناء': 'Construction Worker', 'عامل مباني': 'Building Worker', 'عامل انشاءات': 'Construction Worker',
        'عامل زراعي': 'Agricultural Worker', 'عامل مزرعة': 'Farm Worker', 'مزارع': 'Farmer',
        'عامل مستودع': 'Warehouse Worker', 'عامل مخزن': 'Store Worker', 'امين مستودع': 'Warehouse Keeper',
        'عامل مطعم': 'Restaurant Worker', 'عامل مطبخ': 'Kitchen Worker', 'عامل فندق': 'Hotel Worker',
        'عامل صيانة': 'Maintenance Worker', 'عامل صيانة عامة': 'General Maintenance Worker',
        'عامل محطة وقود': 'Petrol Station Worker', 'عامل بنزين': 'Petrol Station Worker',
        'عامل مغسلة': 'Laundry Worker', 'عامل مصنع': 'Factory Worker', 'عامل انتاج': 'Production Worker',
        'عامل حدائق': 'Garden Worker', 'بستاني': 'Gardener', 'راعي': 'Shepherd', 'راعي غنم': 'Shepherd',
        'راعي ابل': 'Camel Herder', 'حارس': 'Guard', 'حارس امن': 'Security Guard', 'حارس عمارة': 'Building Guard',
        'ناطور': 'Watchman', 'بواب': 'Doorman',
        'كهربائي': 'Electrician', 'كهربائي سيارات': 'Auto Electrician', 'كهربائي مباني': 'Building Electrician',
        'فني كهرباء': 'Electrical Technician', 'سباك': 'Plumber', 'نجار': 'Carpenter', 'نجار مسلح': 'Formwork Carpenter',
        'نجار باب': 'Door Carpenter', 'حداد': 'Blacksmith', 'حداد مسلح': 'Steel Fixer', 'لحام': 'Welder',
        'دهان': 'Painter', 'دهان سيارات': 'Car Painter', 'مبلط': 'Tiler', 'مليس': 'Plasterer', 'لياس': 'Plasterer',
        'بناء': 'Mason', 'بناء طوب': 'Brick Mason', 'بناء حجر': 'Stone Mason', 'مساح': 'Surveyor',
        'ميكانيكي': 'Mechanic', 'ميكانيكي سيارات': 'Auto Mechanic', 'ميكانيكي معدات ثقيلة': 'Heavy Equipment Mechanic',
        'فني': 'Technician', 'فني تكييف': 'AC Technician', 'فني تكييف وتبريد': 'AC and Refrigeration Technician',
        'فني تبريد': 'Refrigeration Technician', 'فني صيانة': 'Maintenance Technician', 'فني سيارات': 'Auto Technician',
        'فني الكترونيات': 'Electronics Technician', 'فني مختبر': 'Laboratory Technician', 'فني اسنان': 'Dental Technician',
        'فني اشعة': 'Radiology Technician', 'فني شبكات': 'Network Technician', 'فني كمبيوتر': 'Computer Technician',
        'سمكري': 'Panel Beater', 'سمكري سيارات': 'Car Panel Beater', 'بنشرجي': 'Tire Repairer', 'منجد': 'Upholsterer',
        'طباخ': 'Cook', 'طاهي': 'Chef', 'طباخ منزلي': 'House Cook', 'خباز': 'Baker', 'حلواني': 'Confectioner',
        'جزار': 'Butcher', 'نادل': 'Waiter', 'قهوجي': 'Coffee Maker', 'مقدم قهوة': 'Coffee Server', 'صانع قهوة': 'Barista',
        'خياط': 'Tailor', 'خياطة': 'Seamstress', 'حلاق': 'Barber', 'مصففة شعر': 'Hairdresser', 'كوافيرة': 'Hairdresser',
        'بائع': 'Salesman', 'بائعة': 'Saleswoman', 'مندوب مبيعات': 'Sales Representative', 'مسوق': 'Marketer',
        'كاشير': 'Cashier', 'امين صندوق': 'Cashier', 'محاسب': 'Accountant', 'كاتب': 'Clerk', 'موظف استقبال': 'Receptionist',
        'سكرتير': 'Secretary', 'سكرتيرة': 'Secretary', 'مدخل بيانات': 'Data Entry Operator', 'مراسل': 'Messenger',
        'مشرف': 'Supervisor', 'مشرف عمال': 'Labour Supervisor', 'مراقب': 'Foreman', 'ملاحظ': 'Foreman', 'مدير': 'Manager',
        'مهندس': 'Engineer', 'مهندس مدني': 'Civil Engineer', 'مهندس كهربائي': 'Electrical Engineer',
        'مهندس ميكانيكي': 'Mechanical Engineer', 'مهندس معماري': 'Architect',
        'ممرض': 'Nurse', 'ممرضة': 'Nurse', 'طبيب': 'Doctor', 'طبيب عام': 'General Practitioner', 'صيدلي': 'Pharmacist',
        'معلم': 'Teacher', 'معلمة': 'Teacher', 'مدرس': 'Teacher', 'طالب': 'Student',
        'مساعد': 'Helper', 'مساعد طباخ': 'Assistant Cook', 'مساعد فني': 'Assistant Technician', 'مساعد سائق': 'Driver Helper',
        'مشغل': 'Operator', 'مشغل الة': 'Machine Operator', 'مشغل معدات ثقيلة': 'Heavy Equipment Operator',
        'مشغل حفار': 'Excavator Operator', 'مشغل رافعة': 'Crane Operator', 'مشغل ماكينة': 'Machine Operator',
        'صياد': 'Fisherman', 'صياد اسماك': 'Fisherman', 'غواص': 'Diver', 'ممرض منزلي': 'Home Nurse'
    };

    // ── 2. Word glossary (profession / qualification / company words) ──
    // Values are the English form used when the word MODIFIES another word
    // (e.g. مركبات → "Vehicle" so "تنظيف مركبات" → "Vehicle Cleaning").
    var WORDS = {
        'عامل': 'Worker', 'عاملة': 'Worker', 'عمال': 'Workers', 'سائق': 'Driver', 'فني': 'Technician', 'مساعد': 'Helper',
        'مشغل': 'Operator', 'مشرف': 'Supervisor', 'مهندس': 'Engineer', 'مدير': 'Manager', 'بائع': 'Salesman',
        'حارس': 'Guard', 'طباخ': 'Cook', 'كاتب': 'Clerk', 'موظف': 'Employee', 'مندوب': 'Representative',
        'اخصائي': 'Specialist', 'مراقب': 'Controller', 'منسق': 'Coordinator', 'امين': 'Keeper', 'مصلح': 'Repairer',
        'تنظيف': 'Cleaning', 'نظافة': 'Cleaning', 'غسيل': 'Washing', 'مركبات': 'Vehicle', 'مركبة': 'Vehicle',
        'سيارات': 'Car', 'سيارة': 'Car', 'شاحنة': 'Truck', 'شاحنات': 'Truck', 'حافلة': 'Bus', 'حافلات': 'Bus',
        'معدات': 'Equipment', 'الات': 'Machine', 'الة': 'Machine', 'ماكينة': 'Machine', 'مكائن': 'Machine',
        'ثقيلة': 'Heavy', 'ثقيل': 'Heavy', 'خفيفة': 'Light', 'خفيف': 'Light', 'عامة': 'General', 'عام': 'General',
        'منزلي': 'Domestic', 'منزلية': 'Domestic', 'منزل': 'House', 'منازل': 'House', 'خاص': 'Private', 'خاصة': 'Private',
        'زراعي': 'Agricultural', 'زراعية': 'Agricultural', 'مزرعة': 'Farm', 'مزارع': 'Farm', 'حدائق': 'Garden',
        'بناء': 'Construction', 'مباني': 'Building', 'مبنى': 'Building', 'انشاءات': 'Construction', 'انشائية': 'Construction',
        'تحميل': 'Loading', 'تنزيل': 'Unloading', 'تفريغ': 'Unloading', 'نقل': 'Transport', 'توصيل': 'Delivery',
        'صيانة': 'Maintenance', 'تكييف': 'Air Conditioning', 'تبريد': 'Refrigeration', 'كهرباء': 'Electrical',
        'كهربائي': 'Electrician', 'سباكة': 'Plumbing', 'نجارة': 'Carpentry', 'حدادة': 'Blacksmithing', 'لحام': 'Welding',
        'دهان': 'Painter', 'دهانات': 'Paint', 'بلاط': 'Tile', 'مطعم': 'Restaurant', 'مطاعم': 'Restaurant',
        'مطبخ': 'Kitchen', 'فندق': 'Hotel', 'فنادق': 'Hotel', 'مستودع': 'Warehouse', 'مستودعات': 'Warehouse',
        'مخزن': 'Store', 'محل': 'Shop', 'محلات': 'Shops', 'مصنع': 'Factory', 'مصانع': 'Factory', 'انتاج': 'Production',
        'مغسلة': 'Laundry', 'ملابس': 'Clothes', 'اغذية': 'Food', 'غذائية': 'Food', 'مواد': 'Materials', 'مبيعات': 'Sales',
        'امن': 'Security', 'حراسة': 'Security', 'محطة': 'Station', 'وقود': 'Fuel', 'بنزين': 'Petrol', 'رافعة': 'Crane',
        'شوكية': 'Forklift', 'حفار': 'Excavator', 'ابل': 'Camel', 'غنم': 'Sheep', 'مواشي': 'Livestock', 'اسماك': 'Fish',
        'مختبر': 'Laboratory', 'اسنان': 'Dental', 'اشعة': 'Radiology', 'كمبيوتر': 'Computer', 'حاسب': 'Computer',
        'شبكات': 'Network', 'الكترونيات': 'Electronics', 'مدني': 'Civil', 'معماري': 'Architectural', 'ميكانيكي': 'Mechanical',
        'اطفال': 'Children', 'قهوة': 'Coffee', 'شاي': 'Tea', 'حلويات': 'Sweets', 'خضار': 'Vegetables', 'فواكه': 'Fruits',
        'المنيوم': 'Aluminium', 'الالمنيوم': 'Aluminium', 'الومنيوم': 'Aluminium', 'المونيوم': 'Aluminium',
        'زجاج': 'Glass', 'الزجاج': 'Glass', 'حديد': 'Steel', 'الحديد': 'Steel', 'اثاث': 'Furniture', 'الاثاث': 'Furniture',
        'زاويه': 'Zawiya', 'زاوية': 'Zawiya', 'تحفه': 'Tuhfa', 'تحفة': 'Tuhfa', 'نجمة': 'Najma', 'الصحراء': 'Al Sahra',
        'مكيفات': 'AC', 'مكيف': 'AC', 'تكييف': 'Air Conditioning', 'ثلاجات': 'Refrigerator', 'غسالات': 'Washing Machine',
        'اول': 'First', 'ثاني': 'Second', 'رئيس': 'Head', 'كبير': 'Senior',
        // qualification
        'ابتدائي': 'Primary', 'ابتدائية': 'Primary', 'متوسط': 'Intermediate', 'متوسطة': 'Intermediate',
        'ثانوي': 'Secondary', 'ثانوية': 'Secondary', 'جامعي': 'University', 'جامعية': 'University', 'دبلوم': 'Diploma',
        'بكالوريوس': 'Bachelor', 'ماجستير': 'Master', 'دكتوراه': 'Doctorate', 'امي': 'Illiterate', 'يقرا': 'Read', 'يكتب': 'Write',
        'لا': 'No', 'يوجد': 'Qualification',
        // company / establishment words
        'مؤسسة': 'Est.', 'موسسة': 'Est.', 'شركة': 'Company', 'مكتب': 'Office', 'مجموعة': 'Group', 'مصنع': 'Factory',
        'مركز': 'Center', 'مجمع': 'Complex', 'معرض': 'Showroom', 'ورشة': 'Workshop', 'مخبز': 'Bakery', 'صيدلية': 'Pharmacy',
        'مستشفى': 'Hospital', 'مدرسة': 'School', 'مقاولات': 'Contracting', 'المقاولات': 'Contracting',
        'نقليات': 'Transport', 'النقليات': 'Transport', 'تجارة': 'Trading', 'التجارة': 'Trading', 'تجارية': 'Trading',
        'التجارية': 'Trading', 'خدمات': 'Services', 'الخدمات': 'Services', 'الصيانة': 'Maintenance', 'الزراعية': 'Agricultural',
        'الصناعية': 'Industrial', 'صناعية': 'Industrial', 'للصناعة': 'Industry', 'المحدودة': 'Ltd', 'محدودة': 'Ltd',
        'القابضة': 'Holding', 'العالمية': 'International', 'الدولية': 'International', 'الوطنية': 'National',
        'المتحدة': 'United', 'الحديثة': 'Modern', 'السعودية': 'Saudi', 'العربية': 'Arabian', 'الخليج': 'Gulf',
        'للتشغيل': 'Operation', 'التشغيل': 'Operation', 'استقدام': 'Recruitment', 'الاستقدام': 'Recruitment',
        'التوظيف': 'Employment', 'للتوظيف': 'Employment', 'العقارية': 'Real Estate', 'العقار': 'Real Estate',
        'الاغذية': 'Foods', 'الاستثمار': 'Investment', 'للاستثمار': 'Investment', 'والتجارة': 'and Trading',
        'والمقاولات': 'and Contracting', 'والخدمات': 'and Services', 'والصيانة': 'and Maintenance', 'والنقليات': 'and Transport'
    };

    // Entity words that read better at the END in English ("Faisal … Transport Est.").
    var ENTITY_LAST = { 'مؤسسة': 1, 'موسسة': 1, 'شركة': 1, 'مكتب': 1, 'مجموعة': 1, 'مصنع': 1, 'مركز': 1, 'مجمع': 1,
        'معرض': 1, 'ورشة': 1, 'مخبز': 1, 'صيدلية': 1, 'مستشفى': 1, 'مدرسة': 1 };

    // ── 3. Known Arabic names (given + family). Checked before phonetics. ──
    var NAMES = {
        'محمد': 'Mohammed', 'احمد': 'Ahmed', 'محمود': 'Mahmoud', 'مصطفى': 'Mustafa', 'علي': 'Ali', 'عمر': 'Omar',
        'عثمان': 'Othman', 'حسن': 'Hassan', 'حسين': 'Hussain', 'خالد': 'Khalid', 'فيصل': 'Faisal', 'فهد': 'Fahad',
        'سعد': 'Saad', 'سعود': 'Saud', 'سعيد': 'Saeed', 'سلمان': 'Salman', 'سليمان': 'Sulaiman', 'سالم': 'Salem',
        'ناصر': 'Nasser', 'منصور': 'Mansour', 'ماجد': 'Majed', 'مشعل': 'Mishal', 'متعب': 'Mutaib', 'تركي': 'Turki',
        'بندر': 'Bandar', 'نايف': 'Naif', 'نواف': 'Nawaf', 'بدر': 'Badr', 'راشد': 'Rashed', 'حمد': 'Hamad',
        'حامد': 'Hamed', 'مبارك': 'Mubarak', 'عادل': 'Adel', 'عيسى': 'Eisa', 'يوسف': 'Yousef', 'ابراهيم': 'Ibrahim',
        'اسماعيل': 'Ismail', 'يحيى': 'Yahya', 'يحيي': 'Yahya', 'زكريا': 'Zakaria', 'موسى': 'Musa', 'موسي': 'Musa',
        'هادي': 'Hadi', 'فالح': 'Falih', 'فلاح': 'Fallah', 'مانع': 'Mane', 'مشاري': 'Mishari', 'طلال': 'Talal',
        'وليد': 'Waleed', 'هشام': 'Hisham', 'ياسر': 'Yasser', 'عماد': 'Emad', 'رياض': 'Riyadh', 'زياد': 'Ziyad',
        'عبدالله': 'Abdullah', 'عبدالرحمن': 'Abdulrahman', 'عبدالعزيز': 'Abdulaziz', 'عبدالمحسن': 'Abdulmohsen',
        'عبدالكريم': 'Abdulkarim', 'عبدالرحيم': 'Abdulrahim', 'عبدالمجيد': 'Abdulmajeed', 'عبدالاله': 'Abdulelah',
        'عبدالهادي': 'Abdulhadi', 'عبداللطيف': 'Abdullatif', 'عبدالملك': 'Abdulmalik', 'عبدالمنعم': 'Abdulmonem',
        'عبدالوهاب': 'Abdulwahab', 'عبدالحميد': 'Abdulhamid', 'عبدالناصر': 'Abdulnasser', 'عبدالسلام': 'Abdulsalam',
        'عبدالرزاق': 'Abdulrazzaq', 'عبدالقادر': 'Abdulqader', 'عبدالمطلب': 'Abdulmuttalib',
        'نورة': 'Noura', 'فاطمة': 'Fatima', 'عائشة': 'Aisha', 'مريم': 'Maryam', 'سارة': 'Sara', 'هند': 'Hind',
        'منيرة': 'Munira', 'لطيفة': 'Latifa', 'حصة': 'Hessa', 'الجوهرة': 'Al Jawhara', 'امل': 'Amal', 'ريم': 'Reem',
        'بن': 'Bin', 'ابن': 'Bin', 'بنت': 'Bint', 'ابو': 'Abu', 'ام': 'Umm', 'ال': 'Al',
        // family / tribal (with ال)
        'اليامي': 'Al Yami', 'العتيبي': 'Al Otaibi', 'الغامدي': 'Al Ghamdi', 'الشهري': 'Al Shehri', 'الزهراني': 'Al Zahrani',
        'القحطاني': 'Al Qahtani', 'المطيري': 'Al Mutairi', 'الدوسري': 'Al Dosari', 'الحربي': 'Al Harbi', 'العنزي': 'Al Anazi',
        'الشمري': 'Al Shammari', 'السبيعي': 'Al Subaie', 'الرشيدي': 'Al Rashidi', 'العمري': 'Al Omari', 'المالكي': 'Al Malki',
        'البقمي': 'Al Buqami', 'الجهني': 'Al Juhani', 'الشهراني': 'Al Shahrani', 'العسيري': 'Al Asiri', 'الاسمري': 'Al Asmari',
        'الخالدي': 'Al Khalidi', 'الهاجري': 'Al Hajri', 'المري': 'Al Marri', 'العجمي': 'Al Ajmi', 'الرويلي': 'Al Ruwaili',
        'السهلي': 'Al Sahli', 'الظفيري': 'Al Dhafiri', 'البلوي': 'Al Balawi', 'الحارثي': 'Al Harthi', 'الثبيتي': 'Al Thubaiti',
        'السلمي': 'Al Sulami', 'الراجحي': 'Al Rajhi', 'السديري': 'Al Sudairi', 'التميمي': 'Al Tamimi', 'الغامدى': 'Al Ghamdi',
        'الشريف': 'Al Sharif', 'الانصاري': 'Al Ansari', 'الفيفي': 'Al Faifi', 'المطرفي': 'Al Mutrafi', 'الصاعدي': 'Al Saedi',
        'اليحيى': 'Al Yahya', 'الفهد': 'Al Fahad', 'السعيد': 'Al Saeed', 'القرني': 'Al Qarni', 'الزهراني ': 'Al Zahrani'
    };

    // ── 4. Readable phonetic fallback (never letter-doubling like "ee/ll/aa"). ──
    var CONS = { 'ب':'b','ت':'t','ث':'th','ج':'j','ح':'h','خ':'kh','د':'d','ذ':'dh','ر':'r','ز':'z','س':'s','ش':'sh',
        'ص':'s','ض':'d','ط':'t','ظ':'z','غ':'gh','ف':'f','ق':'q','ك':'k','ل':'l','م':'m','ن':'n','ه':'h' };
    // Arabic omits short vowels, so an "a" is added to break a word-initial
    // consonant pair or any run of three consonants (CC… → CaC…, …CCC → …CCaC).
    function toLatin(word) {
        var w = norm(word), out = '', run = 0, vowelSeen = false;
        function vowel(v) { out += v; run = 0; vowelSeen = true; }
        function cons(c, semi) {
            if (!semi && ((run === 1 && !vowelSeen) || run >= 2)) { out += 'a'; run = 0; vowelSeen = true; }
            out += c; run++;
        }
        for (var i = 0; i < w.length; i++) {
            var ch = w[i], next = w[i + 1], first = i === 0, last = i === w.length - 1;
            if (ch === 'ا' || ch === 'ة') { vowel('a'); continue; }
            if (ch === 'ع') { vowel(first || last ? 'a' : "'"); continue; }
            if (ch === 'ء' || ch === 'ئ' || ch === 'ؤ') { vowel("'"); continue; }
            if (ch === 'و') { if (first || next === 'ا') cons('w'); else vowel('oo'); continue; }
            if (ch === 'ي') {
                if (first) cons('y', true);
                else if (next === 'ا') { if (run >= 1) { vowel('i'); cons('y', true); } else cons('y', true); }
                else if (last) vowel('i');
                else vowel('ee');
                continue;
            }
            if (ch === 'ه' && last && i > 0 && out && !/[aeiou']$/.test(out)) { vowel('a'); continue; }
            if (CONS[ch]) { cons(CONS[ch]); continue; }
            if (/[0-9A-Za-z]/.test(ch)) out += ch;
        }
        out = out.replace(/'+/g, "'").replace(/^'|'$/g, '');
        return out ? out.charAt(0).toUpperCase() + out.slice(1) : '';
    }

    // Look a single word up in a map, also trying without ال / لل / ل / و prefixes.
    function lookupWord(map, w) {
        if (map[w]) return { en: map[w] };
        if (/^و../.test(w)) { var r = lookupWord(map, w.slice(1)); if (r) return { en: 'and ' + r.en }; }
        if (/^ال../.test(w) && map[w.slice(2)]) return { en: map[w.slice(2)] };
        if (/^لل../.test(w) && map[w.slice(2)]) return { en: map[w.slice(2)] };
        if (/^لل../.test(w) && map['ال' + w.slice(2)]) return { en: map['ال' + w.slice(2)] };
        return null;
    }

    // Profession / qualification: phrase → else glossary in English order
    // (Arabic head-first "عامل تنظيف مركبات" → English head-last "Vehicle Cleaning Worker").
    function profession(value) {
        var v = norm(value);
        if (!v) return '';
        if (PROFESSION[v]) return PROFESSION[v];
        var tokens = v.split(' '), units = [];
        tokens.forEach(function (t) {
            var hit = lookupWord(WORDS, t) || lookupWord(PROFESSION, t);
            var en = hit ? hit.en : toLatin(t.replace(/^ال/, ''));
            // Keep "X and Y" together (e.g. تحميل وتنزيل → "Loading and Unloading").
            if (/^and /.test(en) && units.length) units[units.length - 1] += ' ' + en;
            else units.push(en);
        });
        return title(units.reverse().join(' '));
    }

    // Person / company name: known names, company words, "ال…" → "Al …".
    function name(value) {
        var v = norm(value);
        if (!v) return '';
        var tokens = v.split(' '), out = [], entity = null;
        // "عبد الله" written as two words → one name.
        for (var i = 0; i < tokens.length; i++) {
            if ((tokens[i] === 'عبد' || tokens[i] === 'عبدال') && tokens[i + 1]) {
                tokens[i + 1] = 'عبد' + (tokens[i + 1].indexOf('ال') === 0 ? tokens[i + 1] : 'ال' + tokens[i + 1]);
                tokens.splice(i, 1);
            }
        }
        tokens.forEach(function (t, idx) {
            if (idx === 0 && ENTITY_LAST[t]) { entity = WORDS[t]; return; }
            var n = lookupWord(NAMES, t);
            if (n) { out.push(n.en); return; }
            var w = lookupWord(WORDS, t);
            if (w) { out.push(w.en); return; }
            if (/^عبدال../.test(t)) { out.push('Abdul' + toLatin(t.slice(5)).toLowerCase()); return; }
            if (/^(ال|لل)../.test(t)) { out.push('Al ' + toLatin(t.slice(2))); return; }
            out.push(toLatin(t));
        });
        if (entity) out.push(entity);
        return title(out.join(' ')).replace(/\bAl ([a-z])/g, function (m, c) { return 'Al ' + c.toUpperCase(); });
    }

    // Generic phrase (address etc.): glossary word-by-word in the same order.
    function phrase(value) {
        var v = norm(value);
        if (!v) return '';
        return title(v.split(' ').map(function (t) {
            var hit = lookupWord(WORDS, t) || lookupWord(NAMES, t);
            return hit ? hit.en : toLatin(t);
        }).join(' '));
    }

    // ── Arabic / Hijri date → yyyy-mm-dd (Gregorian) ──
    var AR_DIGITS = '٠١٢٣٤٥٦٧٨٩', FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
    function latinDigits(s) {
        return String(s || '').replace(/[٠-٩]/g, function (d) { return AR_DIGITS.indexOf(d); })
            .replace(/[۰-۹]/g, function (d) { return FA_DIGITS.indexOf(d); });
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function validYmd(y, m, d) {
        var dt = new Date(Date.UTC(y, m - 1, d));
        return dt.getUTCFullYear() === y && dt.getUTCMonth() === m - 1 && dt.getUTCDate() === d;
    }
    // Hijri (Umm al-Qura when the browser supports it; tabular fallback) → Gregorian.
    function hijriToGregorian(hy, hm, hd) {
        // Tabular estimate (Kuwaiti algorithm) as the starting point.
        var jd = Math.floor((11 * hy + 3) / 30) + 354 * hy + 30 * hm - Math.floor((hm - 1) / 2) + hd + 1948440 - 385;
        var guess = new Date(Date.UTC(1970, 0, 1) + (jd - 2440588) * 86400000);
        try {
            var fmt = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura-nu-latn', { timeZone: 'UTC', year: 'numeric', month: 'numeric', day: 'numeric' });
            for (var off = 0; off <= 6; off++) {
                for (var sign = -1; sign <= 1; sign += 2) {
                    var cand = new Date(guess.getTime() + sign * off * 86400000);
                    var parts = {};
                    fmt.formatToParts(cand).forEach(function (p) { parts[p.type] = parseInt(p.value, 10); });
                    if (parts.year === hy && parts.month === hm && parts.day === hd) return cand;
                    if (off === 0) break;
                }
            }
        } catch (e) { /* Intl calendar unsupported → tabular estimate */ }
        return guess;
    }
    // Accepts dd/mm/yyyy or yyyy/mm/dd, Arabic or Latin digits, Gregorian or Hijri year.
    // Returns { iso: 'yyyy-mm-dd', hijri: bool } or null.
    function date(value) {
        var s = latinDigits(value).replace(/[‎‏]/g, '').replace(/\s*(هـ|ه|م)\s*$/, '').trim();
        var m = /^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/.exec(s), y, mo, d;
        if (m) { y = +m[1]; mo = +m[2]; d = +m[3]; }
        else if ((m = /^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/.exec(s))) { d = +m[1]; mo = +m[2]; y = +m[3]; }
        else return null;
        if (mo < 1 || mo > 12 || d < 1 || d > 31) return null;
        if (y >= 1300 && y <= 1500) {
            if (d > 30) return null;
            var g = hijriToGregorian(y, mo, d);
            return { iso: g.getUTCFullYear() + '-' + pad(g.getUTCMonth() + 1) + '-' + pad(g.getUTCDate()), hijri: true };
        }
        if (y >= 1900 && y <= 2100 && validYmd(y, mo, d)) return { iso: y + '-' + pad(mo) + '-' + pad(d), hijri: false };
        return null;
    }
    // Gregorian yyyy-mm-dd → Hijri "yyyy/mm/dd" (Umm al-Qura), or '' if unsupported.
    function toHijri(iso) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
        if (!m) return '';
        try {
            var parts = {};
            new Intl.DateTimeFormat('en-u-ca-islamic-umalqura-nu-latn', { timeZone: 'UTC', year: 'numeric', month: '2-digit', day: '2-digit' })
                .formatToParts(new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])))
                .forEach(function (p) { parts[p.type] = p.value; });
            return parts.year ? parseInt(parts.year, 10) + '/' + parts.month + '/' + parts.day : '';
        } catch (e) { return ''; }
    }

    function professionExact(value) { return PROFESSION[norm(value)] || ''; }

    window.HrArEn = { professionExact: professionExact, profession: profession, name: name, phrase: phrase, date: date, toHijri: toHijri, toLatin: toLatin, norm: norm };
})();
</script>
