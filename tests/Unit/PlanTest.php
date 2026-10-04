<?php

use App\Enums\Plan;

it('limits basic staff and unlocks the assistant on pro', function () {
    expect(Plan::Basic->maxStaff())->toBe(3)
        ->and(Plan::Basic->allowsAssistant())->toBeFalse()
        ->and(Plan::Pro->maxStaff())->toBeNull()
        ->and(Plan::Pro->allowsAssistant())->toBeTrue();
});
