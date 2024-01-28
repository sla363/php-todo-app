<?php

declare(strict_types=1);

namespace TodoApp;

class Kernel
{
    private string $requestUri;

    /** @var array<string, array<int|string, array<string, array<int, mixed>|string>|string>> */
    private array $config;

    public function __construct()
    {
        try {
            $this->config = self::initConfig(__DIR__.'/../src/'.'*');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $this->requestUri = $_SERVER['REQUEST_URI'];
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        $name = $this->config['routes'][$this->requestUri]['name'] ?? null;
        $params = $this->config['routes'][$this->requestUri]['params'] ?? null;

        if ($params !== null && is_string($name)) {
            /** @var array<int, string> $params */
            self::executeRoute($name, $params);
        } elseif (is_string($name)) {
            self::executeRoute($name);
        }
    }

    /**
     * @return array<string, array<int|string, array<string, array<int, mixed>|string>|string>>
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
     * @return array<string, array<int|string, array<string, array<int, mixed>|string>|class-string>>
     * @throws \Exception
     */
    private static function scanFile(string $className, string $fileName): array
    {
        $result = [];

        $classNameWithNameSpace = self::getNameSpace($fileName).'\\'.$className;
        if ($classNameWithNameSpace !== '') {
            $result = self::getDataFromClass($classNameWithNameSpace);
        }

        return $result;
    }

    /**
     * @return array<string, array<int|string, array<string, array<int,mixed>|string>|class-string>>
     * @throws \Exception
     */
    private static function getDataFromClass(string $classNameWithNameSpace): array
    {
        if (class_exists($classNameWithNameSpace) === false) {
            return [];
        }

        $reflection = new \ReflectionClass($classNameWithNameSpace);
        $result = [];

        $routesConstant = $reflection->getConstant('ROUTES');
        if ($routesConstant && is_iterable($routesConstant)) {
            foreach ($routesConstant as $name => $value) {
                if (is_string($name) === false || is_string($value) === false) {
                    throw new \Exception('Route data should be of type <string, string>');
                }

                if ($reflection->hasMethod($value) === false) {
                    throw new \Exception('Method '.$classNameWithNameSpace.'::'.$value.' does not exist');
                }

                $result['routes'][$name] = ['name' => $classNameWithNameSpace.'::'.$value];
                $reflectionMethod = $reflection->getMethod($value);
                if (!empty($reflectionParameters = $reflectionMethod->getParameters())) {
                    foreach ($reflectionParameters as $reflectionParameter) {
                        if (($reflectionParameterType = $reflectionParameter->getType(
                            )) instanceof \ReflectionNamedType) {
                            $result['routes'][$name]['params'][] = $reflectionParameterType->getName();
                        }
                    }
                }
            }
        }


        $reflectionParentClass = $reflection->getParentClass();
        if ($reflectionParentClass instanceof \ReflectionClass) {
            try {
                $getInstanceMethod = $reflectionParentClass->getMethod('getInstance');
                $getInstanceMethodReturnType = $getInstanceMethod->getReturnType();
            } catch (\Exception|\Error) {
                $getInstanceMethod = null;
                $getInstanceMethodReturnType = null;
            }

            if ($getInstanceMethod instanceof \ReflectionMethod && $getInstanceMethod->isPublic(
                ) && $getInstanceMethod->isStatic(
                ) && $getInstanceMethodReturnType instanceof \ReflectionNamedType && $getInstanceMethodReturnType->getName(
                )) {
                $result['services'] = [$classNameWithNameSpace];
            }
        }

        return $result;
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

    /**
     * @param array<int, string>|null $params
     */
    private static function executeRoute(string $route, array $params = null): void
    {
        [$className, $methodName] = explode('::', $route);
        $controllerObject = new $className();

        if (method_exists($controllerObject, $methodName) && $params !== null) {
            $serviceInstances = [];

            foreach ($params as $param) {
                $serviceInstances[] = $param::getInstance();
            }

            $controllerObject->$methodName(...$serviceInstances);
        } elseif (method_exists($controllerObject, $methodName)) {
            $controllerObject->$methodName();
        }
    }

}