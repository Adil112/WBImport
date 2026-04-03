<?php

namespace App\Services\Normalizers;

trait NormalizesValues
{
    protected function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    protected function toIntOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (int)$value;
    }

    protected function toBoolOrNull(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (bool)$value;
        }
        if (is_string($value)) {
            $value = mb_strtolower(trim($value));
            if ($value === '') {
                return null;
            }
            return match ($value) {
                '1', 'true', 'yes' => true,
                '0', 'false', 'no' => false,
                default => null,
            };
        }
        return null;
    }

    protected function toDateOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    protected function toDateTimeOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ? $value : null;
    }

    protected function toDecimalStringOrNull(mixed $value, int $scale = 2): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = trim(str_replace(',', '.', $value));
        }
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return number_format((float)$value, $scale, '.', '');
    }

    protected function makeRecordHash(array $normalized): string
    {
        return sha1(
            json_encode(
                $normalized,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
