<?php

namespace Tests\Feature\Auth;

use App\Livewire\Settings\Store;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TemporaryUploadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function grant(User $admin): string
    {
        $this->actingAs($admin);
        $component = Livewire::test(Store::class)->call('_startUpload', 'logo', [
            ['name' => 'logo.png', 'size' => 128, 'type' => 'image/png'],
        ], false);
        $component->assertDispatched('upload:generatedSignedUrl');

        return $component->effects['dispatches'][0]['params']['url'];
    }

    public function test_current_admin_can_upload_with_default_validation_and_preview_behavior(): void
    {
        Storage::fake('tmp-for-tests');
        $admin = User::factory()->create(['role' => 'admin']);
        $url = $this->grant($admin);
        $response = $this->postJson($url, ['files' => [UploadedFile::fake()->image('logo.png')]])->assertOk();
        $path = TemporaryUploadedFile::extractPathFromSignedPath($response->json('paths.0'));
        Storage::disk('tmp-for-tests')->assertExists('livewire-tmp/'.$path);
        $upload = TemporaryUploadedFile::createFromLivewire($path);
        $this->assertTrue($upload->isPreviewable());
        $preview = $upload->temporaryUrl();
        $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get($preview.'tampered')->assertUnauthorized();
        $this->assertSame(['required', 'file', 'max:12288'], FileUploadConfiguration::rules());
        $this->assertSame(5, FileUploadConfiguration::maxUploadTime());
        $this->assertTrue(FileUploadConfiguration::shouldCleanupOldUploads());
        $before = Storage::disk('tmp-for-tests')->allFiles();
        $this->postJson($url, ['files' => [UploadedFile::fake()->create('too-large.png', 12289)]])->assertUnprocessable()->assertJsonValidationErrors('files.0');
        $this->assertSame($before, Storage::disk('tmp-for-tests')->allFiles());
    }

    public static function revokedStates(): array
    {
        return [['demoted'], ['inactive']];
    }

    #[DataProvider('revokedStates')]
    public function test_real_signed_grant_cannot_write_after_account_permission_is_revoked(string $state): void
    {
        Storage::fake('tmp-for-tests');
        $actor = User::factory()->create(['role' => 'admin']);
        $url = $this->grant($actor);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $editor = Livewire::test(Index::class)->call('edit', $actor->id);
        $state === 'demoted' ? $editor->set('role', 'teknisi') : $editor->set('is_active', false);
        $editor->call('save')->assertHasNoErrors();
        $before = Storage::disk('tmp-for-tests')->allFiles();
        $this->actingAs($actor)->postJson($url, ['files' => [UploadedFile::fake()->image('revoked.png')]])->assertForbidden();
        $this->assertSame($before, Storage::disk('tmp-for-tests')->allFiles());
        $this->actingAs($actor);
        Livewire::test(Store::class)->assertForbidden();
    }

    public function test_guest_and_technician_cannot_reuse_a_grant_or_request_a_new_one(): void
    {
        Storage::fake('tmp-for-tests');
        $url = $this->grant(User::factory()->create(['role' => 'admin']));
        auth()->logout();
        $this->postJson($url, ['files' => [UploadedFile::fake()->image('guest.png')]])->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson($url, ['files' => [UploadedFile::fake()->image('technician.png')]])->assertForbidden();
        Livewire::test(Store::class)->assertForbidden();
        $this->assertSame([], Storage::disk('tmp-for-tests')->allFiles());
    }

    public function test_admin_still_needs_a_valid_signature_and_upload_throttle_is_retained(): void
    {
        Storage::fake('tmp-for-tests');
        $url = $this->grant(User::factory()->create(['role' => 'admin'])).'tampered';
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->postJson($url, ['files' => [UploadedFile::fake()->image('tampered.png')]])->assertUnauthorized();
        }
        $this->postJson($url, ['files' => [UploadedFile::fake()->image('throttled.png')]])->assertTooManyRequests();
        $this->assertSame([], Storage::disk('tmp-for-tests')->allFiles());
    }

    public function test_admin_cannot_write_with_an_expired_signed_grant(): void
    {
        Storage::fake('tmp-for-tests');
        $url = $this->grant(User::factory()->create(['role' => 'admin']));
        $this->travel(6)->minutes();
        $this->postJson($url, ['files' => [UploadedFile::fake()->image('expired.png')]])->assertUnauthorized();
        $this->assertSame([], Storage::disk('tmp-for-tests')->allFiles());
    }
}
