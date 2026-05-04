<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Support;

/**
 * Pure-string helpers used by detectors and exporters. No I/O, no Laravel
 * facade dependencies, fully testable without booting the framework.
 */
final class StrHelpers
{
    /**
     * Strip the configured technical suffix (Action, Job, Event, ...) from
     * the end of a class basename. Returns the input unchanged if no suffix
     * matches.
     *
     * @param  list<string>  $suffixes
     */
    public static function stripBusinessSuffix(string $className, array $suffixes): string
    {
        foreach ($suffixes as $suffix) {
            if ($suffix === '' || $className === $suffix) {
                continue;
            }

            if (str_ends_with($className, $suffix)) {
                return substr($className, 0, -strlen($suffix));
            }
        }

        return $className;
    }

    /**
     * Strip a leading verb from a StudlyCased name. e.g. "CreateLead" → "Lead".
     *
     * @param  list<string>  $verbs
     */
    public static function stripLeadingVerb(string $className, array $verbs): string
    {
        foreach ($verbs as $verb) {
            if ($verb === '') {
                continue;
            }

            $studly = ucfirst(strtolower($verb));

            if (str_starts_with($className, $studly) && strlen($className) > strlen($studly)) {
                $remainder = substr($className, strlen($studly));

                if ($remainder !== '' && ctype_upper($remainder[0])) {
                    return $remainder;
                }
            }
        }

        return $className;
    }

    /**
     * Convert a StudlyCased identifier to a human readable label.
     * "LeadManagement" → "Lead Management". Acronyms are preserved.
     */
    public static function humanize(string $className): string
    {
        $spaced = preg_replace('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $className);

        return trim((string) $spaced);
    }

    /**
     * Generate a stable, lowercase, hyphenated slug from any string.
     * Used by the process detector and the MCP layer (URI fragments).
     */
    public static function slug(string $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));

        return $slug === '' ? 'process' : $slug;
    }

    /**
     * Build a deterministic, mermaid-safe identifier for a string. Mermaid
     * node IDs cannot contain spaces, dots, slashes or special characters.
     */
    public static function safeMermaidId(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_]+/', '_', $value) ?? '';
        $slug = trim($slug, '_');

        if ($slug === '') {
            return 'node_'.substr(md5($value), 0, 8);
        }

        if (! ctype_alpha($slug[0])) {
            $slug = 'n_'.$slug;
        }

        return $slug;
    }

    /**
     * Pluralise the discovered entity for a process label. Naive but adequate
     * for the heuristic process detector. e.g. "Lead" → "Leads".
     */
    public static function pluralise(string $word): string
    {
        if ($word === '') {
            return $word;
        }

        $lower = strtolower($word);

        if (str_ends_with($lower, 'y') && ! self::endsInVowelY($lower)) {
            return substr($word, 0, -1).'ies';
        }

        if (str_ends_with($lower, 's') || str_ends_with($lower, 'x') || str_ends_with($lower, 'z')) {
            return $word.'es';
        }

        return $word.'s';
    }

    private static function endsInVowelY(string $lower): bool
    {
        if (strlen($lower) < 2) {
            return false;
        }

        return in_array($lower[strlen($lower) - 2], ['a', 'e', 'i', 'o', 'u'], true);
    }
}
