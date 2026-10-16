<?php

namespace Webkul\Insurance\Services;

use Carbon\Carbon;
use InvalidArgumentException;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Teamwork\Http\Middleware\ApplyTimezone;
use Webkul\User\Repositories\UserRepository;

/** Read-only intake review. A shared family phone is a review signal, never a merge instruction. */
class ContactImportPreview
{
    public const COLUMNS = ['name', 'email', 'phone', 'date_of_birth', 'owner_email'];

    public function __construct(protected PersonRepository $persons, protected UserRepository $users) {}

    public function preview(string $path): array
    {
        if (! is_file($path) || ! is_readable($path) || filesize($path) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('A readable CSV file of at most 10 MB is required.');
        }
        $file = fopen($path, 'rb');
        try {
            $header = fgetcsv($file, 0, ',', '"', '');
            if (! $header) {
                throw new InvalidArgumentException('The CSV header is missing.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if ($header !== self::COLUMNS) {
                throw new InvalidArgumentException('Required header: '.implode(',', self::COLUMNS));
            }
            $rows = [];
            while (($values = fgetcsv($file, 0, ',', '"', '')) !== false) {
                if (count($rows) >= 50000) {
                    throw new InvalidArgumentException('The preview supports at most 50000 rows.');
                }
                $rows[] = count($values) === count($header) ? array_combine($header, array_map('trim', $values)) : null;
            }
        } finally {
            fclose($file);
        }

        $knownEmails = $knownPhones = [];
        foreach ($this->persons->getModel()->newQuery()->select(['id', 'emails', 'contact_numbers'])->cursor() as $person) {
            foreach ($person->emails ?? [] as $email) {
                $knownEmails[strtolower(trim($email['value'] ?? ''))] = true;
            }
            foreach ($person->contact_numbers ?? [] as $phone) {
                if ($normalized = $this->phone($phone['value'] ?? '')) {
                    $knownPhones[$normalized] = true;
                }
            }
        }
        $owners = $this->users->getModel()->newQuery()->where('status', 1)->pluck('email')->map(fn ($email) => strtolower($email))->all();
        $emailCounts = $phoneCounts = [];
        foreach ($rows as $row) {
            if (! $row) {
                continue;
            }
            $email = strtolower($row['email']);
            $phone = $this->phone($row['phone']);
            if ($email !== '') {
                $emailCounts[$email] = ($emailCounts[$email] ?? 0) + 1;
            }
            if ($phone) {
                $phoneCounts[$phone] = ($phoneCounts[$phone] ?? 0) + 1;
            }
        }

        $result = ['total' => count($rows), 'ready' => 0, 'review' => 0, 'invalid' => 0, 'rows' => []];
        foreach ($rows as $index => $row) {
            $errors = $warnings = [];
            if (! $row) {
                $errors[] = 'column_count';
            } else {
                $email = strtolower($row['email']);
                $phone = $this->phone($row['phone']);
                if ($row['name'] === '' || mb_strlen($row['name']) > 255) {
                    $errors[] = 'name';
                }
                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'email';
                }
                if ($row['phone'] !== '' && ! $phone) {
                    $errors[] = 'phone';
                }
                if ($email === '' && ! $phone) {
                    $errors[] = 'contact_required';
                }
                if (! in_array(strtolower($row['owner_email']), $owners, true)) {
                    $errors[] = 'active_owner_required';
                }
                if ($row['date_of_birth'] !== '' && ! $this->validBirthDate($row['date_of_birth'])) {
                    $errors[] = 'date_of_birth';
                }
                if ($email !== '' && (($emailCounts[$email] ?? 0) > 1 || isset($knownEmails[$email]))) {
                    $warnings[] = 'possible_duplicate_email';
                }
                if ($phone && (($phoneCounts[$phone] ?? 0) > 1 || isset($knownPhones[$phone]))) {
                    $warnings[] = 'shared_or_duplicate_phone';
                }
            }
            $status = $errors ? 'invalid' : ($warnings ? 'review' : 'ready');
            $result[$status]++;
            // Deliberately return only row numbers and reason codes, never client data.
            $result['rows'][] = ['row' => $index + 2, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings];
        }

        return $result;
    }

    private function phone(string $value): ?string
    {
        if (! preg_match('/^[+()0-9 .-]+$/', $value)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return preg_match('/^1[2-9][0-9]{2}[2-9][0-9]{6}$/', $digits) ? $digits : null;
    }

    private function validBirthDate(string $value): bool
    {
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, ApplyTimezone::resolve());

            return $date && $date->format('Y-m-d') === $value && $date->lessThanOrEqualTo(Carbon::today(ApplyTimezone::resolve()));
        } catch (\Throwable) {
            return false;
        }
    }
}
