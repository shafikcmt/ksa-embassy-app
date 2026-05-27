<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::where('slug', 'al-noor-recruitment-demo')->first();
        if (! $agency) return;

        $admin  = User::where('email', 'admin@alnoor-demo.sa')->first();
        $agents = Agent::where('agency_id', $agency->id)->get();

        $candidates = [
            [
                'profile' => [
                    'full_name_en'   => 'Alemitu Bekele',
                    'full_name_ar'   => 'أليميتو بيكيلي',
                    'nationality'    => 'Ethiopian',
                    'date_of_birth'  => '1992-04-15',
                    'gender'         => 'female',
                    'religion'       => 'Christian',
                    'marital_status' => 'single',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+251911234567',
                    'file_number'    => 'HR-2024-001',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(0)?->id,
                ],
                'passport' => ['passport_number' => 'EP1234567', 'passport_type' => 'regular', 'issue_date' => '2022-06-10', 'expiry_date' => '2027-06-09', 'issue_place' => 'Addis Ababa'],
                'visa'     => ['visa_number' => 'VS-2024-0001', 'visa_type' => 'Work', 'sponsor_name' => 'Abdullah Al-Otaibi', 'sponsor_id' => '1012345678', 'issue_date' => '2024-01-15', 'expiry_date' => '2026-01-14'],
                'clearance'=> ['police_clearance_number' => 'PC-ETH-9901', 'clearance_country' => 'Ethiopia', 'clearance_issue_date' => '2023-11-01', 'clearance_expiry_date' => '2025-10-31', 'medical_fit' => true, 'medical_date' => '2023-12-05', 'medical_center' => 'Al-Hada Medical Center'],
                'other'    => ['contract_period' => '2 years', 'salary' => 800.00, 'work_city' => 'Riyadh', 'employer_name' => 'Abdullah Al-Otaibi', 'arrival_date' => '2024-02-01'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Maria Santos Cruz',
                    'full_name_ar'   => 'ماريا سانتوس كروز',
                    'nationality'    => 'Filipino',
                    'date_of_birth'  => '1988-09-22',
                    'gender'         => 'female',
                    'religion'       => 'Christian',
                    'marital_status' => 'married',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+639171234567',
                    'file_number'    => 'HR-2024-002',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(0)?->id,
                ],
                'passport' => ['passport_number' => 'PP2345678', 'passport_type' => 'regular', 'issue_date' => '2021-03-20', 'expiry_date' => '2026-03-19', 'issue_place' => 'Manila'],
                'visa'     => ['visa_number' => 'VS-2024-0002', 'visa_type' => 'Work', 'sponsor_name' => 'Fahad Al-Ghamdi', 'sponsor_id' => '1023456789', 'issue_date' => '2024-02-10', 'expiry_date' => '2026-02-09'],
                'clearance'=> ['police_clearance_number' => 'PC-PHL-4452', 'clearance_country' => 'Philippines', 'clearance_issue_date' => '2024-01-05', 'clearance_expiry_date' => '2026-01-04', 'medical_fit' => true, 'medical_date' => '2024-01-20', 'medical_center' => 'Riyadh Medical Complex'],
                'other'    => ['contract_period' => '2 years', 'salary' => 900.00, 'work_city' => 'Jeddah', 'employer_name' => 'Fahad Al-Ghamdi', 'arrival_date' => '2024-03-01'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Siti Rahayu Putri',
                    'full_name_ar'   => 'سيتي راهايو بوتري',
                    'nationality'    => 'Indonesian',
                    'date_of_birth'  => '1994-07-08',
                    'gender'         => 'female',
                    'religion'       => 'Islam',
                    'marital_status' => 'single',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+6281234567890',
                    'file_number'    => 'HR-2024-003',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(1)?->id,
                ],
                'passport' => ['passport_number' => 'B3456789', 'passport_type' => 'regular', 'issue_date' => '2023-05-15', 'expiry_date' => '2028-05-14', 'issue_place' => 'Jakarta'],
                'visa'     => ['visa_number' => 'VS-2024-0003', 'visa_type' => 'Work', 'sponsor_name' => 'Khalid Al-Shehri', 'sponsor_id' => '1034567890', 'issue_date' => '2024-03-20', 'expiry_date' => '2026-03-19'],
                'clearance'=> ['police_clearance_number' => 'PC-IDN-7823', 'clearance_country' => 'Indonesia', 'clearance_issue_date' => '2024-02-01', 'clearance_expiry_date' => '2026-01-31', 'medical_fit' => true, 'medical_date' => '2024-02-15', 'medical_center' => 'Saudi German Hospital'],
                'other'    => ['contract_period' => '2 years', 'salary' => 850.00, 'work_city' => 'Riyadh', 'employer_name' => 'Khalid Al-Shehri', 'arrival_date' => '2024-04-10'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Kamala Weerasinghe',
                    'full_name_ar'   => null,
                    'nationality'    => 'Sri Lankan',
                    'date_of_birth'  => '1990-12-03',
                    'gender'         => 'female',
                    'religion'       => 'Buddhist',
                    'marital_status' => 'married',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+94771234567',
                    'file_number'    => 'HR-2024-004',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(1)?->id,
                ],
                'passport' => ['passport_number' => 'N4567890', 'passport_type' => 'regular', 'issue_date' => '2022-08-12', 'expiry_date' => '2027-08-11', 'issue_place' => 'Colombo'],
                'visa'     => ['visa_number' => 'VS-2024-0004', 'visa_type' => 'Work', 'sponsor_name' => 'Mansour Al-Zahrani', 'sponsor_id' => '1045678901', 'issue_date' => '2024-04-05', 'expiry_date' => '2026-04-04'],
                'clearance'=> ['police_clearance_number' => 'PC-LKA-3312', 'clearance_country' => 'Sri Lanka', 'clearance_issue_date' => '2024-03-10', 'clearance_expiry_date' => '2026-03-09', 'medical_fit' => true, 'medical_date' => '2024-03-25', 'medical_center' => 'King Fahad Hospital'],
                'other'    => ['contract_period' => '2 years', 'salary' => 780.00, 'work_city' => 'Makkah', 'employer_name' => 'Mansour Al-Zahrani', 'arrival_date' => '2024-05-01'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Fatima Khatun',
                    'full_name_ar'   => 'فاطمة خاتون',
                    'nationality'    => 'Bangladeshi',
                    'date_of_birth'  => '1995-02-18',
                    'gender'         => 'female',
                    'religion'       => 'Islam',
                    'marital_status' => 'single',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+8801712345678',
                    'file_number'    => 'HR-2024-005',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(2)?->id,
                ],
                'passport' => ['passport_number' => 'AB5678901', 'passport_type' => 'regular', 'issue_date' => '2023-01-20', 'expiry_date' => '2028-01-19', 'issue_place' => 'Dhaka'],
                'visa'     => ['visa_number' => 'VS-2024-0005', 'visa_type' => 'Work', 'sponsor_name' => 'Omar Al-Qahtani', 'sponsor_id' => '1056789012', 'issue_date' => '2024-05-15', 'expiry_date' => '2026-05-14'],
                'clearance'=> ['police_clearance_number' => 'PC-BGD-6674', 'clearance_country' => 'Bangladesh', 'clearance_issue_date' => '2024-04-01', 'clearance_expiry_date' => '2026-03-31', 'medical_fit' => true, 'medical_date' => '2024-04-20', 'medical_center' => 'National Guard Hospital'],
                'other'    => ['contract_period' => '2 years', 'salary' => 750.00, 'work_city' => 'Riyadh', 'employer_name' => 'Omar Al-Qahtani', 'arrival_date' => '2024-06-01'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Amina Bibi',
                    'full_name_ar'   => 'أمينة بيبي',
                    'nationality'    => 'Pakistani',
                    'date_of_birth'  => '1986-05-30',
                    'gender'         => 'female',
                    'religion'       => 'Islam',
                    'marital_status' => 'divorced',
                    'occupation'     => 'Cook',
                    'phone'          => '+923001234567',
                    'file_number'    => 'HR-2024-006',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(0)?->id,
                ],
                'passport' => ['passport_number' => 'AK6789012', 'passport_type' => 'regular', 'issue_date' => '2020-09-05', 'expiry_date' => '2025-09-04', 'issue_place' => 'Lahore'],
                'visa'     => ['visa_number' => 'VS-2024-0006', 'visa_type' => 'Work', 'sponsor_name' => 'Saad Al-Mutairi', 'sponsor_id' => '1067890123', 'issue_date' => '2024-06-01', 'expiry_date' => '2026-05-31'],
                'clearance'=> ['police_clearance_number' => 'PC-PAK-1122', 'clearance_country' => 'Pakistan', 'clearance_issue_date' => '2024-05-10', 'clearance_expiry_date' => '2026-05-09', 'medical_fit' => false, 'medical_date' => null, 'medical_center' => null],
                'other'    => ['contract_period' => '2 years', 'salary' => 1000.00, 'work_city' => 'Khobar', 'employer_name' => 'Saad Al-Mutairi'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Sunita Thapa',
                    'full_name_ar'   => null,
                    'nationality'    => 'Nepalese',
                    'date_of_birth'  => '1991-11-14',
                    'gender'         => 'female',
                    'religion'       => 'Hindu',
                    'marital_status' => 'single',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+9779801234567',
                    'file_number'    => 'HR-2024-007',
                    'status'         => 'active',
                    'agent_id'       => $agents->get(1)?->id,
                ],
                'passport' => ['passport_number' => 'CK7890123', 'passport_type' => 'regular', 'issue_date' => '2022-11-25', 'expiry_date' => '2027-11-24', 'issue_place' => 'Kathmandu'],
                'visa'     => ['visa_number' => 'VS-2024-0007', 'visa_type' => 'Work', 'sponsor_name' => 'Turki Al-Dosari', 'sponsor_id' => '1078901234', 'issue_date' => '2024-07-10', 'expiry_date' => '2026-07-09'],
                'clearance'=> ['police_clearance_number' => 'PC-NPL-5588', 'clearance_country' => 'Nepal', 'clearance_issue_date' => '2024-06-15', 'clearance_expiry_date' => '2026-06-14', 'medical_fit' => true, 'medical_date' => '2024-07-01', 'medical_center' => 'Dallah Hospital'],
                'other'    => ['contract_period' => '2 years', 'salary' => 780.00, 'work_city' => 'Jeddah', 'employer_name' => 'Turki Al-Dosari', 'arrival_date' => '2024-08-01'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Priya Sharma',
                    'full_name_ar'   => null,
                    'nationality'    => 'Indian',
                    'date_of_birth'  => '1993-08-07',
                    'gender'         => 'female',
                    'religion'       => 'Hindu',
                    'marital_status' => 'married',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+919812345678',
                    'file_number'    => 'HR-2024-008',
                    'status'         => 'inactive',
                    'agent_id'       => null,
                ],
                'passport' => ['passport_number' => 'R8901234', 'passport_type' => 'regular', 'issue_date' => '2019-04-10', 'expiry_date' => '2024-04-09', 'issue_place' => 'Chennai'],
                'visa'     => ['visa_number' => null, 'visa_type' => 'Work'],
                'clearance'=> ['medical_fit' => false],
                'other'    => ['work_city' => 'Riyadh'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Grace Achieng Otieno',
                    'full_name_ar'   => null,
                    'nationality'    => 'Kenyan',
                    'date_of_birth'  => '1989-03-25',
                    'gender'         => 'female',
                    'religion'       => 'Christian',
                    'marital_status' => 'widowed',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+254712345678',
                    'file_number'    => 'HR-2024-009',
                    'status'         => 'inactive',
                    'agent_id'       => $agents->get(2)?->id,
                ],
                'passport' => ['passport_number' => 'AK9012345', 'passport_type' => 'regular', 'issue_date' => '2021-07-18', 'expiry_date' => '2026-07-17', 'issue_place' => 'Nairobi'],
                'visa'     => ['visa_number' => 'VS-2023-0099', 'visa_type' => 'Work', 'expiry_date' => '2024-12-31'],
                'clearance'=> ['police_clearance_number' => 'PC-KEN-3399', 'clearance_country' => 'Kenya', 'medical_fit' => true, 'medical_date' => '2023-06-10', 'medical_center' => 'Ibn Sina Hospital'],
                'other'    => ['contract_period' => '2 years', 'salary' => 750.00, 'work_city' => 'Medina', 'employer_name' => 'Hassan Al-Balawi'],
            ],
            [
                'profile' => [
                    'full_name_en'   => 'Akello Sarah Nabwire',
                    'full_name_ar'   => null,
                    'nationality'    => 'Ugandan',
                    'date_of_birth'  => '1997-06-12',
                    'gender'         => 'female',
                    'religion'       => 'Christian',
                    'marital_status' => 'single',
                    'occupation'     => 'Domestic Worker',
                    'phone'          => '+256701234567',
                    'file_number'    => 'HR-2024-010',
                    'status'         => 'blacklisted',
                    'agent_id'       => null,
                    'notes'          => 'Blacklisted due to contract abandonment in previous placement.',
                ],
                'passport' => ['passport_number' => 'B0123456', 'passport_type' => 'regular', 'issue_date' => '2023-09-01', 'expiry_date' => '2028-08-31', 'issue_place' => 'Kampala'],
                'visa'     => ['visa_number' => 'VS-2023-0050', 'visa_type' => 'Work'],
                'clearance'=> ['medical_fit' => false],
                'other'    => [],
            ],
        ];

        foreach ($candidates as $data) {
            $profileData             = $data['profile'];
            $profileData['agency_id'] = $agency->id;
            $profileData['created_by'] = $admin?->id;
            $profileData['updated_by'] = $admin?->id;

            $existing = HrProfile::where('agency_id', $agency->id)
                ->where('file_number', $profileData['file_number'])
                ->first();
            if ($existing) continue;

            $hr = HrProfile::create($profileData);

            $hr->passport()->create(array_merge(['hr_profile_id' => $hr->id], $data['passport']));

            $hr->visa()->create(array_merge(['hr_profile_id' => $hr->id], $data['visa']));

            $hr->clearance()->create(array_merge(['hr_profile_id' => $hr->id], $data['clearance']));

            $hr->otherInfo()->create(array_merge(['hr_profile_id' => $hr->id], $data['other']));
        }
    }
}
