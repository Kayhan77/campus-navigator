<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("📰 NewsSeeder is running...");

        // Get an admin user to associate with news
        $admin = User::where('role', 'super_admin')
            ->orWhere('role', 'admin')
            ->first();

        if (!$admin) {
            $this->command->warn("No admin user found. Please run SuperAdminSeeder first.");
            return;
        }

        $newsItems = [
            [
                'title' => 'Campus Library Extended Hours for Finals',
                'content' => 'The campus library will be open 24 hours starting May 1st until May 28th to support students during finals week. Additional study zones have been set up on all floors with comfortable seating and charging stations.',
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'New Food Court Opening in Building C',
                'content' => 'We are excited to announce the opening of a new food court in Building C with cuisines from around the world. The grand opening will be on May 20th with special discounts for all students.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Campus Health Center Offers Free Flu Shots',
                'content' => 'The campus health center is offering free flu shots to all students and staff. Shots will be administered on May 15-17 from 9 AM to 4 PM. No appointment necessary. Please bring your student ID.',
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Summer Internship Fair - Register Now',
                'content' => 'Join us for the annual Summer Internship Fair on May 25th in the main auditorium. Over 50 companies will be recruiting for summer positions. Register at the career center by May 20th to get your free career fair kit.',
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Upcoming Campus Maintenance Schedule',
                'content' => 'Scheduled maintenance will be performed on the following dates: May 19-21 (Building A restrooms), May 22-23 (Parking Lot B), May 24 (Main auditorium HVAC). We apologize for any inconvenience this may cause.',
                'is_published' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Student Government Elections - Voting Opens May 16',
                'content' => 'It\'s time to vote for next year\'s student government leaders! Online voting opens on May 16th and closes on May 18th at midnight. All currently enrolled students are eligible to vote. Candidate profiles are available on the student portal.',
                'is_published' => false,
                'published_at' => null,
            ],
        ];

        foreach ($newsItems as $newsData) {
            // Check if news with same title already exists
            if (News::where('title', $newsData['title'])->exists()) {
                $this->command->warn("News [{$newsData['title']}] already exists. Skipping.");
                continue;
            }

            $newsData['created_by'] = $admin->id;
            $newsData['updated_by'] = $admin->id;

            if ($newsData['is_published']) {
                $newsData['published_by'] = $admin->id;
            }

            News::create($newsData);
            $this->command->info("✓ Created news: {$newsData['title']}");
        }

        $this->command->info("📰 NewsSeeder completed!");
    }
}
