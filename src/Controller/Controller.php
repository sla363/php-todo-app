<?php

namespace TodoApp\Controller;

abstract class Controller
{
    /**
     * @throws \Exception
     */
    public static function render(string $fileName, array $options = []): void
    {
        $handle = fopen($fileName, 'r');
        $output = '';

        while ($handle !== false && ($line = fgets($handle)) !== false) {
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
        }

        return $line;
    }
}