<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Insurance\Services\ContactImportPreview;

uses(DatabaseTransactions::class);

function previewSyntheticContacts(array $rows): array
{
    $path = tempnam(sys_get_temp_dir(), 'pilot-preview-');
    try {
        $file = fopen($path, 'wb');
        fputcsv($file, ContactImportPreview::COLUMNS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($file, $row, ',', '"', '');
        }
        fclose($file);

        return app(ContactImportPreview::class)->preview($path);
    } finally {
        unlink($path);
    }
}

it('previews complete contacts without importing or exposing their data', function () {
    $before = Person::count();
    $result = previewSyntheticContacts([['Synthetic Example', 'synthetic@example.invalid', '(202) 555-0123', '2000-02-29', getDefaultAdmin()->email]]);
    expect($result['ready'])->toBe(1)->and($result['invalid'])->toBe(0)->and($result['review'])->toBe(0);
    expect(Person::count())->toBe($before);
    expect(json_encode($result))->not->toContain('Synthetic Example')->not->toContain('synthetic@example.invalid')->not->toContain('555');
});

it('flags a shared family phone on both rows without merging household members', function () {
    $owner = getDefaultAdmin()->email;
    $result = previewSyntheticContacts([
        ['Synthetic Parent', '', '2025550123', '1980-01-01', $owner],
        ['Synthetic Adult Child', '', '+1 (202) 555-0123', '2000-01-01', $owner],
    ]);
    expect($result['review'])->toBe(2)->and($result['ready'])->toBe(0);
    foreach ($result['rows'] as $row) {
        expect($row['warnings'])->toContain('shared_or_duplicate_phone');
    }
});

it('detects existing normalized contact email and phone matches', function () {
    Person::create(['name' => 'Synthetic Existing', 'emails' => [['value' => 'MATCH@example.invalid', 'label' => 'work']], 'contact_numbers' => [['value' => '+1 (202) 555-0124', 'label' => 'mobile']]]);
    $result = previewSyntheticContacts([['Synthetic Incoming', 'match@example.invalid', '2025550124', '', getDefaultAdmin()->email]]);
    expect($result['rows'][0]['warnings'])->toContain('possible_duplicate_email', 'shared_or_duplicate_phone');
});

it('rejects invalid dates missing owners and unusable contact details by row number', function () {
    $result = previewSyntheticContacts([['Synthetic Invalid', 'invalid', 'call me', '2026-02-30', 'missing@example.invalid'], ['short row']]);
    expect($result['invalid'])->toBe(2);
    expect($result['rows'][0]['errors'])->toContain('email', 'phone', 'active_owner_required', 'date_of_birth');
    expect($result['rows'][1]['row'])->toBe(3)->and($result['rows'][1]['errors'])->toBe(['column_count']);
});

it('rejects future birth dates without assuming a birthday', function () {
    $result = previewSyntheticContacts([['Synthetic Future', 'future@example.invalid', '', now()->addYear()->toDateString(), getDefaultAdmin()->email]]);
    expect($result['rows'][0]['errors'])->toContain('date_of_birth');
});

it('rejects unexpected sensitive columns before reviewing a file', function () {
    $path = tempnam(sys_get_temp_dir(), 'pilot-preview-');
    try {
        file_put_contents($path, "name,email,phone,date_of_birth,owner_email,ssn\n");
        expect(fn () => app(ContactImportPreview::class)->preview($path))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($path);
    }
});
