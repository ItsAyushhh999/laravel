<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

/*test('email can be verified', function () {

    Event::fake();

    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->actingAs($user, 'web')->get($verificationUrl);

    $user->refresh();

    Event::assertDispatched(Verified::class);
    expect($user->hasVerifiedEmail())->toBeTrue();

    $response->assertRedirect('/dashboard?verified=1');
});
*/

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
