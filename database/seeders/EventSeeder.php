<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("📅 EventSeeder is running...");

        $admin = User::where('role', 'super_admin')
            ->orWhere('role', 'admin')
            ->first();

        if (! $admin) {
            $this->command->warn("No admin user found. Please run SuperAdminSeeder first.");
            return;
        }

        $room = Room::query()->first();

        $events = [
            [
                'title' => 'Welcome Week Opening Ceremony',
                'description' => 'Kick off the semester with speeches, introductions, student performances, and campus tour information for new and returning students.',
                'location' => 'Main Auditorium',
                'location_override' => 'Main Auditorium',
                'start_time' => now()->addDays(2)->setTime(9, 0),
                'end_time' => now()->addDays(2)->setTime(11, 0),
                'status' => 'published',
                'is_public' => true,
                'max_attendees' => 500,
                'registration_required' => false,
                'registered_users_count' => 0,
                'reminders_dispatched' => ['24h'],
            ],
            [
                'title' => 'Computer Science Career Panel',
                'description' => 'Industry speakers from software, cybersecurity, and data engineering will share advice about internships and first jobs.',
                'location' => 'Room 204, Engineering Building',
                'location_override' => 'Engineering Building Room 204',
                'start_time' => now()->addDays(4)->setTime(14, 0),
                'end_time' => now()->addDays(4)->setTime(16, 0),
                'status' => 'published',
                'is_public' => true,
                'max_attendees' => 120,
                'registration_required' => true,
                'registered_users_count' => 34,
                'reminders_dispatched' => ['24h', '1h'],
            ],
            [
                'title' => 'Campus Tree Planting Volunteer Day',
                'description' => 'Join the sustainability office for a volunteer afternoon planting native trees around the south courtyard and library lawn.',
                'location' => 'South Courtyard',
                'location_override' => 'South Courtyard',
                'start_time' => now()->addDays(6)->setTime(8, 30),
                'end_time' => now()->addDays(6)->setTime(12, 0),
                'status' => 'published',
                'is_public' => true,
                'max_attendees' => 80,
                'registration_required' => true,
                'registered_users_count' => 19,
                'reminders_dispatched' => null,
            ],
            [
                'title' => 'Midterm Review Session for Mathematics 101',
                'description' => 'A student-led review session covering practice problems, key formulas, and last-minute questions before the midterm exam.',
                'location' => 'Room 101, Science Hall',
                'location_override' => 'Science Hall Room 101',
                'start_time' => now()->addDays(8)->setTime(17, 0),
                'end_time' => now()->addDays(8)->setTime(19, 0),
                'status' => 'draft',
                'is_public' => false,
                'max_attendees' => 45,
                'registration_required' => true,
                'registered_users_count' => 12,
                'reminders_dispatched' => null,
            ],
            [
                'title' => 'Student Research Expo',
                'description' => 'Browse poster presentations from undergraduate and graduate researchers across engineering, business, and health sciences.',
                'location' => 'Exhibition Hall',
                'location_override' => 'Exhibition Hall',
                'start_time' => now()->addDays(10)->setTime(10, 0),
                'end_time' => now()->addDays(10)->setTime(15, 0),
                'status' => 'published',
                'is_public' => true,
                'max_attendees' => 300,
                'registration_required' => false,
                'registered_users_count' => 0,
                'reminders_dispatched' => ['1h'],
            ],
            [
                'title' => 'Evening Yoga in the Wellness Center',
                'description' => 'Relax and recharge with a free guided yoga class open to all students and staff. Mats are provided on a first-come basis.',
                'location' => 'Wellness Center Studio',
                'location_override' => 'Wellness Center Studio',
                'start_time' => now()->addDays(12)->setTime(18, 0),
                'end_time' => now()->addDays(12)->setTime(19, 0),
                'status' => 'published',
                'is_public' => true,
                'max_attendees' => 60,
                'registration_required' => true,
                'registered_users_count' => 25,
                'reminders_dispatched' => ['24h', '1h', '10min'],
            ],
        ];

        foreach ($events as $eventData) {
            if (Event::where('title', $eventData['title'])->exists()) {
                $this->command->warn("Event [{$eventData['title']}] already exists. Skipping.");
                continue;
            }

            $eventData['created_by'] = $admin->id;
            $eventData['room_id'] = $room?->id;
            $eventData['image'] = null;

            Event::create($eventData);
            $this->command->info("✓ Created event: {$eventData['title']}");
        }

        $this->command->info("📅 EventSeeder completed!");
    }
}
