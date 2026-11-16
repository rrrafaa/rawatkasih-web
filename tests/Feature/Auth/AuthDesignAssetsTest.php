<?php

test('auth pages serve the standalone design stylesheet without a Vite manifest', function () {
    $css = public_path('css/rawatkasih.css');
    $script = public_path('js/rawatkasih.js');

    expect(is_file($css))->toBeTrue();
    expect(is_file($script))->toBeTrue();

    $stylesheet = file_get_contents($css);
    expect($stylesheet)->toContain('.rk-auth')
        ->toContain('grid-template-columns:43.333333% 56.666667%')
        ->toContain('--rk-green:#005338')
        ->toContain('@media(max-width:760px)');

    $response = $this->get('/login');
    $response->assertOk()
        ->assertSee('css/rawatkasih.css', false)
        ->assertSee('js/rawatkasih.js', false)
        ->assertSee('Masuk ke akun')
        ->assertSee('rawatkasih-mark.svg', false);

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('css/rawatkasih.css', false);
});
