<?php
declare(strict_types=1);

namespace KNCMS\Utils;

final class Formatter
{
    public static function format_cash(float|int $price): string
    {
        return str_replace(",", ".", number_format((float)$price));
    }
}
