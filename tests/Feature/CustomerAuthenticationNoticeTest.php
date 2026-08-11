<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerAuthenticationNoticeTest extends TestCase
{
    public function test_guest_sees_an_explanation_after_opening_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response
            ->assertRedirect('/login')
            ->assertSessionHas('auth_notice');

        $this->followRedirects($response)
            ->assertSee('Silakan masuk untuk melanjutkan. Fitur ini tersedia bagi customer HM Music yang telah memiliki akun.');
    }
}
