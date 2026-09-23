<?php

namespace App\Support;

class SearchHighlighter
{
    /**
     * @return array<int, string>
     */
    public static function terms(string $search): array
    {
        $words = preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return [];
        }

        return array_values(array_unique($words));
    }

    /**
     * @param  array<int, string>  $terms
     */
    public static function highlight(?string $text, array $terms): string
    {
        $safe = e((string) $text);

        $terms = array_values(array_filter($terms, fn (string $term) => $term !== ''));

        if ($safe === '' || $terms === []) {
            return $safe;
        }

        $pattern = '/('.implode('|', array_map(fn (string $term) => preg_quote($term, '/'), $terms)).')/iu';

        return preg_replace($pattern, '<mark class="rounded bg-yellow-200 px-0.5">$1</mark>', $safe) ?? $safe;
    }
}
