<?php

declare(strict_types=1);

namespace TodoApp;

class Kernel
{
    private string $requestUri;
    private array $config;

    public function __construct()
    {
        try {
            $this->config = self::initConfig(__DIR__.'/../src/'.'*');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
//        dd($this->routes);
        $this->requestUri = $_SERVER['REQUEST_URI'];
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        if (array_key_exists($this->requestUri, $this->config['routes'])) {
            self::executeRoute($this->config['routes'][$this->requestUri]);
        }
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     */
    private static function initConfig(string $path): array
    {
        $filesAndFolders = glob($path);
        if ($filesAndFolders === false) {
            throw new \Exception('Could not open file: '.$path);
        }

        $config = [];

        foreach ($filesAndFolders as $filesAndFolder) {
            if (is_dir($filesAndFolder)) {
                $result = self::initConfig($filesAndFolder.'/*');
                foreach ($result as $key => $value) {
                    $config[$key] = array_merge($config[$key] ?? [], $value);
                }
            } elseif (preg_match_all('/^.*\/(.*)\.php$/', $filesAndFolder, $matches) && isset($matches[1][0])) {
                $result = self::scanFile($matches[1][0], $filesAndFolder);
                foreach ($result as $key => $value) {
                    $config[$key] = array_merge($config[$key] ?? [], $value);
                }
            }
        }

        return $config;
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     */
    private static function scanFile(string $className, string $fileName): array
    {
        $result = [];

        $classNameWithNameSpace = self::getNameSpace($fileName).'\\'.$className;
        if ($classNameWithNameSpace !== '') {
            $result['routes'] = self::getRoutesFromClass($classNameWithNameSpace);
        }

        return $result;
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     */
    private static function getRoutesFromClass(string $classNameWithNameSpace): array
    {
        if (class_exists($classNameWithNameSpace) === false) {
            return [];
        }

        $reflection = new \ReflectionClass($classNameWithNameSpace);

        $routes = [];
        $routesConstant = $reflection->getConstant('ROUTES');
        if ($routesConstant && is_iterable($routesConstant)) {
            foreach ($routesConstant as $name => $value) {
                if (is_string($name) === false || is_string($value) === false) {
                    throw new \Exception('Route data should be of type <string, string>');
                }

                if ($reflection->hasMethod($value) === false) {
                    throw new \Exception('Method '.$classNameWithNameSpace.'::'.$value.' does not exist');
                }

                $routes[$name] = $classNameWithNameSpace.'::'.$value;
            }
        }

        return $routes;
    }

    /**
     * @throws \Exception
     */
    private static function getNameSpace(string $fileName): string
    {
        $classNameWithNameSpace = '';
        $handle = fopen($fileName, 'r');

        if ($handle === false) {
            throw new \Exception('Cannot open file: '.$fileName);
        }

        while (($line = fgets($handle)) !== false) {
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

    private static function executeRoute(string $route): void
    {
        [$className, $methodName] = explode('::', $route);
        $controllerObject = new $className();
        if (method_exists($controllerObject, $methodName)) {
            $controllerObject->$methodName();
        }
    }

}