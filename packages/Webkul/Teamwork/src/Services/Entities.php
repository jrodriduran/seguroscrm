<?php

namespace Webkul\Teamwork\Services;

use Illuminate\Support\Facades\DB;

/**
 * Records that can be flagged for follow-up, and the pages that show them.
 */
class Entities
{
    /**
     * type => [table, title column, detail route, route that shows the record]
     */
    const TYPES = [
        'lead' => ['leads', 'title', 'admin.leads.view'],
        'person' => ['persons', 'name', 'admin.contacts.persons.view'],
        'organization' => ['organizations', 'name', 'admin.contacts.organizations.edit'],
        'quote' => ['quotes', 'subject', 'admin.quotes.edit'],
    ];

    public function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /**
     * Title and link of a record, or null if it does not exist.
     */
    public function describe(string $type, int $id): ?array
    {
        if (! $this->exists($type)) {
            return null;
        }

        [$table, $column, $route] = self::TYPES[$type];

        $title = DB::table($table)->where('id', $id)->value($column);

        if ($title === null) {
            return null;
        }

        return [
            'title' => (string) $title,
            // Relative, so links survive a change of domain (e.g. moving to HTTPS).
            'url' => route($route, $id, false),
        ];
    }

    /**
     * The record shown by the current page, based on its route: [type, id] or null.
     */
    public function fromCurrentRoute(): ?array
    {
        $route = request()->route();

        if (! $route) {
            return null;
        }

        foreach (self::TYPES as $type => [, , $routeName]) {
            if ($route->getName() === $routeName && ($id = (int) $route->parameter('id'))) {
                return [$type, $id];
            }
        }

        return null;
    }
}
