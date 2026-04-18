<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class CampusLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            // Receptions
            [
                'name' => '1st Reception (Kirkuk Road)',
                'type' => 'reception',
                'category' => 'services',
                'latitude' => 36.142247,
                'longitude' => 44.021455,
                'description' => 'Main reception area for visitors and student guidance on Kirkuk Road.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => '2nd Reception',
                'type' => 'reception',
                'category' => 'services',
                'latitude' => 36.145886,
                'longitude' => 44.024228,
                'description' => 'Secondary reception area for visitor assistance and information.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Reception Parking
            [
                'name' => '1st Reception Parking (Kirkuk Road)',
                'type' => 'parking',
                'category' => 'parking',
                'latitude' => 36.141204,
                'longitude' => 44.021338,
                'description' => 'Designated parking area for visitors near the first reception.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => '2nd Reception Parking',
                'type' => 'parking',
                'category' => 'parking',
                'latitude' => 36.145589,
                'longitude' => 44.023759,
                'description' => 'Designated parking area for visitors near the second reception.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Administrative Office
            [
                'name' => 'Deanery of College',
                'type' => 'office',
                'category' => 'administration',
                'latitude' => 36.142397,
                'longitude' => 44.022555,
                'description' => 'Administrative office for college deanery and academic affairs.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Engineering Departments
            [
                'name' => 'Civil Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.142727,
                'longitude' => 44.023472,
                'description' => 'Academic department for civil engineering studies and research.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Survey Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.141679,
                'longitude' => 44.026396,
                'description' => 'Academic department for surveying engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Architecture Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.143654,
                'longitude' => 44.024052,
                'description' => 'Academic department for architecture engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Software Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.143602,
                'longitude' => 44.023473,
                'description' => 'Academic department for software engineering and computer science studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Electrical Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.143896,
                'longitude' => 44.022798,
                'description' => 'Academic department for electrical engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Mechanical Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.144604,
                'longitude' => 44.021783,
                'description' => 'Academic department for mechanical engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Water Resources & Dam Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.145555,
                'longitude' => 44.025164,
                'description' => 'Academic department for water resources and dam engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Chemical Engineering',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.145416,
                'longitude' => 44.025035,
                'description' => 'Academic department for chemical engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Aviation Department',
                'type' => 'department',
                'category' => 'engineering',
                'latitude' => 36.145082,
                'longitude' => 44.022251,
                'description' => 'Academic department for aviation engineering studies.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Cafeterias
            [
                'name' => 'Cafeteria 1',
                'type' => 'cafeteria',
                'category' => 'food',
                'latitude' => 36.143034,
                'longitude' => 44.024646,
                'description' => 'Food and beverage area for students and staff.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
            [
                'name' => 'Cafeteria 2',
                'type' => 'cafeteria',
                'category' => 'food',
                'latitude' => 36.141819,
                'longitude' => 44.023390,
                'description' => 'Food and beverage area for students and staff.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Mosque
            [
                'name' => 'Campus Mosque',
                'type' => 'mosque',
                'category' => 'religious',
                'latitude' => 36.141689,
                'longitude' => 44.024872,
                'description' => 'Prayer area.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],

            // Registration Office
            [
                'name' => 'Registration Office',
                'type' => 'office',
                'category' => 'administration',
                'latitude' => 36.144137,
                'longitude' => 44.024899,
                'description' => 'Administrative office for student registration and enrollment services.',
                'opening_hours' => null,
                'notes' => null,
                'image' => null,
            ],
        ];

        foreach ($locations as $location) {
            Building::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}
