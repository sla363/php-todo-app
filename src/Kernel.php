<?php

declare(strict_types=1);

namespace TodoApp;

use TodoApp\Attribute\Route;

class Kernel
{
    private const string ROOT_APP_FOLDER = __DIR__.'/../src/';

    /** @var array<int, string> */
    private const array ENV_VARIABLES = [
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
    ];
    private string $routeName;

    private array $config;

    public function __construct()
    {
        try {
            $this->config = self::initConfig(self::ROOT_APP_FOLDER.'*');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $this->routeName = $_SERVER['REQUEST_URI'];
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        $routeExists = isset($this->config['routes'][$this->routeName]);

        if ($routeExists === true) {
            $this->executeRoute($this->routeName);
        }
    }

    /**
     * @throws \Exception
     */
    private static function initConfig(string $path): array
    {
        $config = [];

        if (file_exists($envFile = self::ROOT_APP_FOLDER.'../.env') &&
            ($handle = fopen($envFile, 'r')) !== false) {
            while (($line = fgets($handle)) !== false) {
                preg_match('/^(.*)=(.*)$/', $line, $matches);
                $envVariableName = isset($matches[1]) ? trim($matches[1]) : null;
                $envVariableValue = isset($matches[2]) ? trim($matches[2]) : null;

                if ($envVariableName !== null && $envVariableValue !== null
                    && in_array($envVariableName, self::ENV_VARIABLES)) {
                    $config['env'][$envVariableName] = $envVariableValue;
                }
            }

            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $envData = $config['env'] ?? null;

        $filesAndFoldersConfig = self::scanFilesAndFolders($path, $envData);

        return array_merge($config, $filesAndFoldersConfig);
    }

    /**
     * @throws \Exception
     */
    private static function scanFilesAndFolders(string $path, array $envData = null): array
    {
        $config = [];

        $filesAndFolders = glob($path);
        if ($filesAndFolders === false) {
            throw new \Exception('Could not open file: '.$path);
        }

        foreach ($filesAndFolders as $filesAndFolder) {
            if (is_dir($filesAndFolder)) {
                $result = self::scanFilesAndFolders($filesAndFolder.'/*', $envData);
                foreach ($result as $key => $value) {
                    $config[$key] = array_merge($config[$key] ?? [], $value);
                }
            } elseif (preg_match_all('/^.*\/(.*)\.php$/', $filesAndFolder, $matches) && isset($matches[1][0])) {
                $result = self::scanFile($matches[1][0], $filesAndFolder, $envData);
                foreach ($result as $key => $value) {
                    $config[$key] = array_merge($config[$key] ?? [], $value);
                }
            }
        }

        return $config;
    }

    /**
     * @throws \Exception
     */
    private static function scanFile(string $className, string $fileName, array $envData = null): array
    {
        $result = [];

        $classNameWithNameSpace = self::getNameSpace($fileName).'\\'.$className;
        if ($classNameWithNameSpace !== '') {
            $result = self::getDataFromClass($classNameWithNameSpace, $envData);
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    private static function getDataFromClass(string $classNameWithNameSpace, array $envData = null): array
    {
        if (class_exists($classNameWithNameSpace) === false) {
            return [];
        }

        $reflectionClass = new \ReflectionClass($classNameWithNameSpace);
        $result = [];

        foreach ($reflectionClass->getMethods() as $method) {
            $routeAttributes = $method->getAttributes(Route::class);

            foreach ($routeAttributes as $routeAttribute) {
                $routeAttributeInstance = $routeAttribute->newInstance();
                $routeName = $routeAttributeInstance->name;
                $result['routes'][$routeName] = [
                    'name' => $classNameWithNameSpace.'::'.$method->getName(),
                ];
                if (!empty($reflectionParameters = $method->getParameters())) {
                    foreach ($reflectionParameters as $reflectionParameter) {
                        if (($reflectionParameterType = $reflectionParameter->getType(
                            )) instanceof \ReflectionNamedType) {
                            $result['routes'][$routeName]['params'][] = $reflectionParameterType->getName();
                        }
                    }
                }
            }
        }

        $reflectionParentClass = $reflectionClass->getParentClass();
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
                $result['services'] = [$classNameWithNameSpace => ['name' => $classNameWithNameSpace]];

                $requiredEnvVariablesConstant = $reflectionClass->getConstant('REQUIRED_ENV_VARIABLES');
                if (is_iterable($requiredEnvVariablesConstant)) {
                    foreach ($requiredEnvVariablesConstant as $item) {
                        if (isset($envData[$item])) {
                            $result['services'][$classNameWithNameSpace]['params'][$item] = $envData[$item];
                        }
                    }
                }
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
            preg_match_all('/^namespace(.*);$/', $line, $matches);
            $classNameWithNameSpace = isset($matches[1][0]) ? trim($matches[1][0]) : '';
            if ($classNameWithNameSpace !== '') {
                break;
            }
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        return $classNameWithNameSpace;
    }

    private function executeRoute(string $route): void
    {
        $routeClassName = $this->config['routes'][$route]['name'];
        [$className, $methodName] = explode('::', $routeClassName);
        $routeParams = $this->config['routes'][$route]['params'] ?? null;
        $controllerObject = new $className();

        if (method_exists($controllerObject, $methodName) && $routeParams !== null) {
            $serviceInstances = [];
            foreach ($routeParams as $routeParam) {
                $serviceInstance = $routeParam::getInstance();
                if (isset($this->config['services'][$routeParam]['params'])) {
                    $serviceParams = $this->config['services'][$routeParam]['params'];
                    $serviceParamsWithoutIndexes = [];

                    foreach ($serviceParams as $serviceParam) {
                        $serviceParamsWithoutIndexes[] = $serviceParam;
                    }
                    $serviceInstance->init(... $serviceParamsWithoutIndexes);
                }
                $serviceInstances[] = $serviceInstance;
            }

            $controllerObject->$methodName(...$serviceInstances);
        } elseif (method_exists($controllerObject, $methodName)) {
            $controllerObject->$methodName();
        }
    }
}