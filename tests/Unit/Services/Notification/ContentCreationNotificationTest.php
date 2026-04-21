<?php

namespace Tests\Unit\Services\Notification;

use App\DTOs\Announcement\CreateAnnouncementDTO;
use App\DTOs\Event\CreateEventDTO;
use App\DTOs\News\CreateNewsDTO;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\Notification\NotificationService;
use App\Services\Announcement\AnnouncementService;
use App\Services\Event\EventService;
use App\Services\News\NewsService;
use App\Services\Search\SearchCacheService;
use App\Services\SupabaseStorageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentCreationNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function event_creation_creates_stored_notification_and_recipients(): void
    {
        $creator = User::factory()->create();
        User::factory()->count(2)->create();

        $firebaseMock = Mockery::mock(FirebaseService::class);
        $firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $notificationService = new NotificationService($firebaseMock);
        $cacheMock = Mockery::mock(SearchCacheService::class);
        $storageMock = Mockery::mock(SupabaseStorageService::class);

        $service = new EventService($cacheMock, $notificationService, $storageMock);

        $dto = new CreateEventDTO(
            title: 'Campus Open Day',
            description: 'Welcome event',
            location: 'Main Hall',
            start_time: Carbon::now()->addDay(),
            end_time: Carbon::now()->addDay()->addHour(),
            room_id: null,
            status: 'published',
            is_public: true,
            max_attendees: null,
            registration_required: false
        );

        $event = $service->create($dto, $creator->id);

        $notification = Notification::query()->where('type', 'event')->latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertSame($event->id, $notification->data['event_id'] ?? null);

        $recipients = NotificationRecipient::query()
            ->where('notification_id', $notification->id)
            ->count();

        $this->assertEquals(3, $recipients);
    }

    #[Test]
    public function news_creation_creates_stored_notification(): void
    {
        User::factory()->count(2)->create();

        $firebaseMock = Mockery::mock(FirebaseService::class);
        $firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $notificationService = new NotificationService($firebaseMock);
        $storageMock = Mockery::mock(SupabaseStorageService::class);

        $service = new NewsService($notificationService, $storageMock);

        $dto = new CreateNewsDTO(
            title: 'Library Hours Updated',
            content: 'Library now opens at 7 AM.',
            is_published: true,
            published_at: Carbon::now()
        );

        $news = $service->create($dto);

        $notification = Notification::query()->where('type', 'news')->latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertSame($news->id, $notification->data['news_id'] ?? null);
    }

    #[Test]
    public function announcement_creation_creates_stored_notification(): void
    {
        User::factory()->count(2)->create();

        $firebaseMock = Mockery::mock(FirebaseService::class);
        $firebaseMock->shouldReceive('sendNotification')->andReturn(null);

        $notificationService = new NotificationService($firebaseMock);
        $storageMock = Mockery::mock(SupabaseStorageService::class);

        $service = new AnnouncementService($notificationService, $storageMock);

        $dto = new CreateAnnouncementDTO(
            title: 'Exam Schedule Notice',
            content: 'Please check the updated schedule.',
            is_active: true,
            is_pinned: false,
            published_at: Carbon::now()
        );

        $announcement = $service->create($dto);

        $notification = Notification::query()->where('type', 'announcement')->latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertSame($announcement->id, $notification->data['announcement_id'] ?? null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
