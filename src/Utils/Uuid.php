<?php

declare(strict_types=1);

namespace TodoApp\Utils;

use Random\RandomException;

class Uuid
{
    public static function generateV4(): string
    {
        $format = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        $uuid = preg_replace_callback('/[x,y]/', function ($value) {
            if ($value[array_key_first($value)] === 'x') {
                try {
                    $xValue = dechex(random_int(0, 0xF));
                } catch (RandomException) {
                    $xValues = [];
                    for ($i = 0; $i <= 9; $i++) {
                        $xValues[] = (string)$i;
                    }
                    $xValues = array_merge($xValues, ['a', 'b', 'c', 'd', 'e', 'f']);
                    $xValue = $xValues[array_rand($xValues)];
                }

                return $xValue;
            } elseif ($value[array_key_first($value)] === 'y') {
                $yValues = ['8', '9', 'a', 'b'];

                return $yValues[array_rand($yValues)];
            }

            return $value[array_key_first($value)];
        }, $format);

        if (is_string($uuid)) {
            return $uuid;
        }

        return '';
    }
}