<?php

use App\Jobs\SendSmsChunkJob;
use App\Models\School;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\SmsDispatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('creates sms logs immediately by default', function () {
    config(['sms.send_immediately' => true]);
    config(['queue.default' => 'database']);

    Http::fake(['*' => Http::response(['success_message' => 'SMS sent'], 200)]);

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    Setting::create(['school_id' => $school->id, 'key' => 'sms_api_url', 'value' => 'https://example.com/sms']);
    Setting::create(['school_id' => $school->id, 'key' => 'sms_api_key', 'value' => 'test-key']);

    $payloads = [[
        'mobile' => '01711111111',
        'message' => 'Test message',
        'meta' => ['recipient_type' => 'custom', 'message_type' => 'manual'],
    ]];

    Bus::fake();

    SmsDispatch::dispatchChunks($school->id, 1, $payloads);

    Bus::assertNotDispatched(SendSmsChunkJob::class);
    expect(SmsLog::where('school_id', $school->id)->count())->toBe(1);
});

it('queues sms chunks when immediate sending is disabled', function () {
    config(['sms.send_immediately' => false]);
    config(['queue.default' => 'database']);

    $school = School::create(['name' => 'Test School', 'status' => 'active']);

    $payloads = [[
        'mobile' => '01722222222',
        'message' => 'Queued message',
        'meta' => ['recipient_type' => 'custom'],
    ]];

    Bus::fake();

    SmsDispatch::dispatchChunks($school->id, 1, $payloads);

    Bus::assertDispatched(SendSmsChunkJob::class);
    expect(SmsLog::count())->toBe(0);
});
