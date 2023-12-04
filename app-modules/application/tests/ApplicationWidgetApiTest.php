<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

use App\Settings\LicenseSettings;
use Illuminate\Support\Facades\URL;
use Assist\Prospect\Models\Prospect;
use Assist\Application\Models\Application;

use function Pest\Laravel\withoutMiddleware;

use Assist\Form\Http\Middleware\EnsureSubmissibleIsEmbeddableAndAuthorized;

test('define is protected with proper feature access control', function () {
    withoutMiddleware([EnsureSubmissibleIsEmbeddableAndAuthorized::class]);

    $settings = app(LicenseSettings::class);

    $settings->data->addons->onlineAdmissions = false;

    $settings->save();

    $application = Application::factory()->create();

    get(URL::signedRoute('applications.define', ['application' => $application]))
        ->assertForbidden()
        ->assertJson([
            'error' => 'Online Admissions is not enabled.',
        ]);

    $settings->data->addons->onlineAdmissions = true;

    $settings->save();

    get(URL::signedRoute('applications.define', ['application' => $application]))
        ->assertSuccessful();
});

test('request-authentication is protected with proper feature access control', function () {
    withoutMiddleware([EnsureSubmissibleIsEmbeddableAndAuthorized::class]);

    $settings = app(LicenseSettings::class);

    $settings->data->addons->onlineAdmissions = false;

    $settings->save();

    $application = Application::factory()->create();

    $prospect = Prospect::factory()->create();

    post(URL::signedRoute('applications.request-authentication', ['application' => $application, 'email' => $prospect->email]))
        ->assertForbidden()
        ->assertJson([
            'error' => 'Online Admissions is not enabled.',
        ]);

    $settings->data->addons->onlineAdmissions = true;

    $settings->save();

    post(URL::signedRoute('applications.request-authentication', ['application' => $application, 'email' => $prospect->email]))
        ->assertSuccessful();
});
