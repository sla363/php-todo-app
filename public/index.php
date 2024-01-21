<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

main();

/**
 * @throws Exception
 */
function main(): void
{
    $requestUri = $_SERVER['REQUEST_URI'];
    try {
        $routes = createRouteList(__DIR__.'/../src/'.'*');
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
    if (array_key_exists($requestUri, $routes)) {
        executeRoute($routes[$requestUri]);
    }
}

function createRouteList(string $path): array
{
    $filesAndFolders = glob($path);
    $routes = [];

    foreach ($filesAndFolders as $filesAndFolder) {
        if (is_dir($filesAndFolder)) {
            $result = createRouteList($filesAndFolder.'/*');
            $routes = array_merge($routes, $result);
        } elseif (preg_match_all('/^.*\/(.*)\.php$/', $filesAndFolder, $matches) && isset($matches[1][0])) {
            $routes = scanFileForRoutes($matches[1][0], $filesAndFolder);
        }
    }

    return $routes;
}

/**
 * @throws Exception
 */
function scanFileForRoutes(string $className, string $fileName): array
{
    $routes = [];

    $classNameWithNameSpace = getNameSpace($fileName).'\\'.$className;
    if ($classNameWithNameSpace !== '') {
        $routes = getRoutesFromClass($classNameWithNameSpace);
    }

    return $routes;
}

/**
 * @throws Exception
 */
function getRoutesFromClass(string $classNameWithNameSpace): array
{
    $routes = [];

    try {
        $reflection = new ReflectionClass($classNameWithNameSpace);
    } catch (ReflectionException) {
        return [];
    }

    $routesConstant = $reflection->getConstant('ROUTES');
    if ($routesConstant) {
        foreach ($routesConstant as $name => $value) {
            if ($reflection->hasMethod($value) === false) {
                throw new Exception('Method '.$classNameWithNameSpace.'::'.$value.' does not exist');
            }
            $routes[$name] = $classNameWithNameSpace.'::'.$value;
        }
    }

    return $routes;
}

function getNameSpace(string $fileName): string
{
    $classNameWithNameSpace = '';
    $handle = fopen($fileName, 'r');

    while ($handle !== false && ($line = fgets($handle)) !== false) {
        $line = trim($line);
        $matches = [];
        preg_match_all('/^namespace(.*);$/', $line, $matches);
        if (!empty($matches[1][0])) {
            $classNameWithNameSpace = trim($matches[1][0]);
            break;
        }
    }
    fclose($handle);

    return $classNameWithNameSpace;
}

function executeRoute(string $route): void
{
    [$className, $methodName] = explode('::', $route);
    $controllerObject = new $className();
    if (method_exists($controllerObject, $methodName)) {
        $controllerObject->$methodName();
    }
}
