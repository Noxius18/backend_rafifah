<?php

namespace App\Helpers {
    function file_exists(string $path): bool
    {
        if (\Tests\Unit\FcmHelperTestState::$credentialsFileExists !== null) {
            return \Tests\Unit\FcmHelperTestState::$credentialsFileExists;
        }

        return \file_exists($path);
    }

    function file_get_contents(string $path): string|false
    {
        if (\Tests\Unit\FcmHelperTestState::$credentialsJson !== null) {
            return \Tests\Unit\FcmHelperTestState::$credentialsJson;
        }

        return \file_get_contents($path);
    }
}

namespace Tests\Unit {

    use App\Helpers\FcmHelper;
    use Illuminate\Support\Facades\Http;
    use RuntimeException;
    use Tests\TestCase;

    final class FcmHelperTestState
    {
        public static ?bool $credentialsFileExists = null;
        public static ?string $credentialsJson = null;
    }

    class FcmHelperTest extends TestCase
    {
        protected function tearDown(): void
        {
            FcmHelperTestState::$credentialsFileExists = null;
            FcmHelperTestState::$credentialsJson = null;

            parent::tearDown();
        }

        public function test_send_to_topic_fails_fast_when_credentials_are_missing(): void
        {
            FcmHelperTestState::$credentialsFileExists = false;

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Firebase credentials tidak ditemukan di storage/app/private/credentials.json.');

            FcmHelper::sendToTopic('mahasantri', 'Title', 'Body');
        }

        public function test_send_to_topic_posts_with_access_token_when_credentials_exist(): void
        {
            Http::fake([
                'fcm.googleapis.com/*' => Http::response(['name' => 'projects/pmb-ramq/messages/123'], 200),
            ]);

            $response = FcmHelper::sendToTopic(
                'mahasantri',
                'Title',
                'Body',
                ['foo' => 'bar'],
                fn (): string => 'test-access-token'
            );

            Http::assertSent(function ($request): bool {
                $data = $request->data();

                return $request->url() === 'https://fcm.googleapis.com/v1/projects/pmb-ramq/messages:send'
                    && $request->hasHeader('Authorization', 'Bearer test-access-token')
                    && $data['message']['topic'] === 'mahasantri'
                    && $data['message']['data']['foo'] === 'bar';
            });

            $this->assertSame(['name' => 'projects/pmb-ramq/messages/123'], $response);
        }
    }
}
