<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\HtmlString;
use Webkul\User\Models\User;

/**
 * "@Name" mentions in team text. The composer inserts the teammate's full
 * name after "@"; matching is on full names, longest first, so names with
 * spaces or parentheses work.
 */
class Mentions
{
    protected ?array $team = null;

    public function __construct(protected TeamScope $teamScope) {}

    /**
     * Ids of the teammates mentioned in a text.
     */
    public function extract(string $text): array
    {
        $ids = [];

        foreach ($this->team() as $id => $name) {
            if (mb_stripos($text, '@'.$name) !== false) {
                $ids[] = $id;

                $text = str_ireplace('@'.$name, '', $text);
            }
        }

        return $ids;
    }

    /**
     * Escaped text with mentions highlighted.
     */
    public function render(?string $text): HtmlString
    {
        $html = e((string) $text);
        $tokens = [];

        // Placeholders first, so a short name inside a longer one is not wrapped twice.
        foreach (array_values($this->team()) as $index => $name) {
            $escaped = e('@'.$name);
            $token = "\u{0}".$index."\u{0}";

            if (mb_stripos($html, $escaped) !== false) {
                $html = str_ireplace($escaped, $token, $html);
                $tokens[$token] = '<span class="tw-mention">'.$escaped.'</span>';
            }
        }

        return new HtmlString(strtr($html, $tokens));
    }

    /**
     * Teammates as id => name, longest names first.
     */
    protected function team(): array
    {
        if ($this->team !== null) {
            return $this->team;
        }

        $user = auth()->guard('user')->user();

        if (! $user) {
            return $this->team = [];
        }

        $team = User::whereIn('id', $this->teamScope->teamUserIds($user))->pluck('name', 'id')->all();

        uasort($team, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $this->team = $team;
    }
}
