<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Screen readers must hear which field an error belongs to.
 */
class FormFieldTest extends TestCase
{
    public function test_a_field_with_an_error_points_to_its_message(): void
    {
        $this->withViewErrors(['email' => 'The email field is required.'])
            ->blade('<x-field name="email" label="Email" />')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="email-error"', false)
            ->assertSee('<p id="email-error"', false)
            ->assertSee('The email field is required.');
    }

    public function test_a_field_without_an_error_is_not_marked_invalid(): void
    {
        $this->withViewErrors([])
            ->blade('<x-field name="email" label="Email" />')
            ->assertSee('<label for="email"', false)
            ->assertDontSee('aria-invalid', false)
            ->assertDontSee('aria-describedby', false);
    }
}
