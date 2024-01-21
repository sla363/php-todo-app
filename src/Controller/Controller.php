<?php

declare(strict_types=1);

namespace TodoApp\Controller;

abstract class Controller
{
    /**
     * @param array<string, string> $options
     * @throws \Exception
     */
    public static function render(string $fileName, array $options = []): void
    {
        $handle = fopen($fileName, 'r');
        $output = '';

        if ($handle === false) {
            throw new \Exception('Could not open file: '.$fileName);
        }

        while (($line = fgets($handle)) !== false) {
            if (preg_match_all('/{{([^}]*)}}/', $line, $matches)) {
                $stringsToReplace = $matches[0];
                $variablesToReplace = $matches[1];
                $line = self::replacePlaceholdersWithVariableValues(
                    $line,
                    $stringsToReplace,
                    $variablesToReplace,
                    $options
                );
            }
            $output .= $line;
        }

        fclose($handle);
        echo $output;
    }

    /**
     * @param array<string, string> $options
     * @param array<int, string> $stringsToReplace
     * @param array<int, string> $variablesToReplace
     * @throws \Exception
     */
    private static function replacePlaceholdersWithVariableValues(
        string $line,
        array $stringsToReplace,
        array $variablesToReplace,
        array $options
    ): string {
        for ($i = 0; $i < count($variablesToReplace); $i++) {
            $variableName = trim($variablesToReplace[$i]);

            if (array_key_exists($variableName, $options)) {
                $line = preg_replace('/'.preg_quote($stringsToReplace[$i]).'/', $options[$variableName], $line);
            } else {
                throw new \Exception(sprintf('Variable "%s" does not exist', $variableName));
            }

            if ($line === null) {
                throw new \Exception('Could not parse template line: '.$line);
            }
        }

        return $line;
    }
}