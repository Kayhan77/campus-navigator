<?php

use App\Services\Event\EventRecommendationEngineService;

it('returns no matching events message when none are relevant', function (): void {
    $service = new EventRecommendationEngineService();

    $result = $service->recommend('cyber security', [
        [
            'title' => 'Poetry Night',
            'category' => 'arts',
            'tags' => ['poetry', 'creative'],
            'description' => 'Open mic session for writers.',
        ],
    ]);

    expect($result)->toBe([
        'recommendations' => [],
    ]);
});

it('ranks events by relevance and returns top three', function (): void {
    $service = new EventRecommendationEngineService();

    $result = $service->recommend('software engineering', [
        [
            'title' => 'Software Engineering Career Fair',
            'category' => 'software engineering',
            'tags' => ['software', 'engineering', 'career'],
            'description' => 'Meet recruiters for software roles.',
        ],
        [
            'title' => 'Tech Networking Night',
            'category' => 'networking',
            'tags' => ['software', 'networking'],
            'description' => 'Connect with software professionals.',
        ],
        [
            'title' => 'Leadership Fundamentals',
            'category' => 'soft skills',
            'tags' => ['leadership'],
            'description' => 'Build communication and leadership.',
        ],
        [
            'title' => 'Engineering Interview Bootcamp',
            'category' => 'career',
            'tags' => ['engineering', 'interview'],
            'description' => 'Interview prep for engineering students.',
        ],
    ]);

    expect($result)->toHaveKey('recommendations');
    expect($result['recommendations'])->toHaveCount(3);
    expect($result['recommendations'][0]['title'])->toBe('Software Engineering Career Fair');
    expect($result['recommendations'][1]['title'])->toBe('Engineering Interview Bootcamp');
    expect($result['recommendations'][2]['title'])->toBe('Tech Networking Night');
});

it('uses deterministic alphabetical tie-break for equal scores', function (): void {
    $service = new EventRecommendationEngineService();

    $result = $service->recommend('python', [
        [
            'title' => 'Zeta Python Basics',
            'category' => 'tech',
            'tags' => ['python'],
            'description' => '',
        ],
        [
            'title' => 'Alpha Python Basics',
            'category' => 'tech',
            'tags' => ['python'],
            'description' => '',
        ],
    ]);

    expect($result['recommendations'][0]['title'])->toBe('Alpha Python Basics');
    expect($result['recommendations'][1]['title'])->toBe('Zeta Python Basics');
});
