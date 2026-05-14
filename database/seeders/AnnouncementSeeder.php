<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("📢 AnnouncementSeeder is running...");

        // Get an admin user to associate with announcements
        $admin = User::where('role', 'super_admin')
            ->orWhere('role', 'admin')
            ->first();

        if (!$admin) {
            $this->command->warn("No admin user found. Please run SuperAdminSeeder first.");
            return;
        }

        $announcements = [
            [
                'title' => '⚠️ IMPORTANT: Campus Closure May 30th',
                'content' => 'The entire campus will be closed on May 30th for Memorial Day observance. All buildings, facilities, and services will be unavailable. Normal operations will resume on May 31st. For emergencies, call campus security at extension 911.',
                'is_active' => true,
                'is_pinned' => true,
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => '🚨 ALERT: Parking Lot B Closed for Maintenance',
                'content' => 'Parking Lot B will be completely closed from May 22nd to May 25th for resurfacing and repainting. Please use Parking Lots A or C during this period. Temporary parking permits will be issued at the main security gate.',
                'is_active' => true,
                'is_pinned' => true,
                'published_at' => now()->subDays(4),
            ],
            [
                'title' => 'Academic Calendar Update - Extended Finals Period',
                'content' => 'Due to the earlier start of the summer session, the finals period has been extended to May 30th. All instructors should submit final grades by June 2nd. Students must complete all exams by May 28th. Makeup exams will be available June 1-2.',
                'is_active' => true,
                'is_pinned' => false,
                'published_at' => now()->subDays(6),
            ],
            [
                'title' => 'Technology Services: System Maintenance Schedule',
                'content' => 'Our IT team will perform critical system maintenance on May 24th from 2 AM to 6 AM. During this time, email, file storage, and online portals may be unavailable. We apologize for any inconvenience. Please complete important work before this maintenance window.',
                'is_active' => true,
                'is_pinned' => false,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Campus Sustainability Initiative - Plastic Ban Effective June 1',
                'content' => 'Starting June 1st, single-use plastic bags, straws, and containers will be banned across campus. All vendors and cafeterias must use eco-friendly alternatives. Campus bookstore now offers reusable bags at a 30% discount for sustainability members.',
                'is_active' => true,
                'is_pinned' => false,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Welcome Class of 2030 - Orientation Dates Announced',
                'content' => 'Freshman orientation for the Class of 2030 will be held June 15-17, 2026. All incoming students must attend at least 2 out of 3 days. Online pre-registration opens May 20th. New students will receive housing assignments and course registration guidance during orientation.',
                'is_active' => true,
                'is_pinned' => false,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Construction Update: New STEM Building Progress',
                'content' => 'The new STEM building is now 75% complete! The structural work has finished and interior finishes are underway. We expect the building to open for Fall 2026 semester. Tours of the construction site are available on Saturdays from 2-4 PM.',
                'is_active' => false,
                'is_pinned' => false,
                'published_at' => null,
            ],
        ];

        foreach ($announcements as $announcementData) {
            // Check if announcement with same title already exists
            if (Announcement::where('title', $announcementData['title'])->exists()) {
                $this->command->warn("Announcement [{$announcementData['title']}] already exists. Skipping.");
                continue;
            }

            $announcementData['created_by'] = $admin->id;
            $announcementData['updated_by'] = $admin->id;

            if ($announcementData['published_at']) {
                $announcementData['published_by'] = $admin->id;
            }

            Announcement::create($announcementData);
            $this->command->info("✓ Created announcement: {$announcementData['title']}");
        }

        $this->command->info("📢 AnnouncementSeeder completed!");
    }
}
