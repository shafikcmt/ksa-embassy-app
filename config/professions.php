<?php

/*
|--------------------------------------------------------------------------
| Profession English → Arabic fallback translations
|--------------------------------------------------------------------------
| Used by the Embassy/Visa List print (prints/embassy-list.blade.php) to show
| Arabic alongside English when a record has no stored `profession_ar`
| (visa.profession_ar / snapshot_profession_ar is optional free-text).
|
| This is only a display FALLBACK — a record's own stored Arabic always wins.
| Keys are matched case-insensitively (lowercased, trimmed). Add/edit freely.
*/

return [
    'domestic worker' => 'عامل منزلي',
    'heavy driver'    => 'سائق ثقيل',
    'light driver'    => 'سائق خفيف',
    'driver'          => 'سائق',
    'cook'            => 'طباخ',
    'student'         => 'طالب',
    'cleaner'         => 'عامل نظافة',
    'labourer'        => 'عامل',
    'laborer'         => 'عامل',
    'worker'          => 'عامل',
    'housemaid'       => 'خادمة منزلية',
    'maid'            => 'خادمة',
    'nurse'           => 'ممرض',
    'electrician'     => 'كهربائي',
    'plumber'         => 'سباك',
    'carpenter'       => 'نجار',
    'welder'          => 'لحام',
    'mason'           => 'بنّاء',
    'painter'         => 'دهّان',
    'security guard'  => 'حارس أمن',
    'guard'           => 'حارس',
    'waiter'          => 'نادل',
    'salesman'        => 'بائع',
    'tailor'          => 'خياط',
    'gardener'        => 'بستاني',
    'mechanic'        => 'ميكانيكي',
    'farmer'          => 'مزارع',
    'shepherd'        => 'راعي غنم',
];
