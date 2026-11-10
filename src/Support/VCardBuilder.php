<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Card;

class VCardBuilder
{
    public static function build(Card $card): string
    {
        $nameParts = explode(' ', trim($card->name), 2);
        $first = $nameParts[0] ?? '';
        $last = $nameParts[1] ?? '';

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . self::escape($last) . ';' . self::escape($first) . ';;;',
            'FN:' . self::escape($card->name),
        ];

        if (!empty($card->company)) {
            $lines[] = 'ORG:' . self::escape($card->company);
        }

        if (!empty($card->role)) {
            $lines[] = 'TITLE:' . self::escape($card->role);
        }

        if (!empty($card->phone)) {
            $lines[] = 'TEL;TYPE=CELL:' . self::escape($card->phone);
        }

        if (!empty($card->email)) {
            $lines[] = 'EMAIL:' . self::escape($card->email);
        }

        if (!empty($card->website)) {
            $lines[] = 'URL:' . self::escape($card->website);
        }

        if (!empty($card->location)) {
            $lines[] = 'ADR;TYPE=WORK:;;' . self::escape($card->location) . ';;;;';
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    public static function filenameFor(Card $card): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $card->name), '_'));

        return ($slug !== '' ? $slug : 'contact') . '.vcf';
    }

    private static function escape(string $value): string
    {
        return str_replace([',', ';'], ['\,', '\;'], $value);
    }
}
