<?php

namespace Webkul\Insurance\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Webkul\Insurance\Services\ContactImportPreview;

class PreviewContacts extends Command
{
    protected $signature = 'insurance:preview-contacts {file}';

    protected $description = 'Read-only CSV intake review; never creates, merges or updates contacts';

    public function handle(ContactImportPreview $preview): int
    {
        try {
            $result = $preview->preview($this->argument('file'));
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $result['invalid'] || $result['review'] ? self::FAILURE : self::SUCCESS;
    }
}
