<?php

namespace JundFauz\FilamentKompact;

use Illuminate\Support\ServiceProvider;
use JundFauz\EloquentKompact\Regex\Regex;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use RegexIterator;
use function JundFauz\EloquentKompact\same_with;

class FilamentKompactServiceProvider extends ServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function boot(): void
    {
        $this->applyMacros();
    }

    /**
     * @throws ReflectionException
     */
    public function applyMacros(): void
    {
        $directory = new RecursiveDirectoryIterator(__DIR__);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, Regex::create()->endsWith('Macros.php'), RegexIterator::GET_MATCH);

        foreach ($regex as $info) {
            $filePath = $info[0];

            // 3. Read file content to search namespace and class
            $tokens = token_get_all(file_get_contents($filePath));
            $namespace = '';

            for ($i = 0; $i < count($tokens); $i++) {
                // Detect Namespace
                if ($tokens[$i][0] === T_NAMESPACE) {
                    $namespace = '';
                    $i += 2; // Skip the space
                    while (isset($tokens[$i]) && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NS_SEPARATOR])) {
                        $namespace .= $tokens[$i][1];
                        $i++;
                    }
                    $namespace .= '\\';
                }

                // Detect Class, Interface, or Trait
                if ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE || $tokens[$i][0] === T_TRAIT) {
                    // Make sure this isn't an anonymous class (e.g., new class{})
                    if (isset($tokens[$i - 2]) && $tokens[$i - 2][0] === T_NEW) {
                        continue;
                    }

                    $i += 2; // Skip the space
                    if (isset($tokens[$i][1])) {
                        $class = $namespace . $tokens[$i][1];
                        $reflector = new ReflectionClass($class);
                        // $directTraits = array_keys($reflector->getTraits());

                        foreach ($reflector->getMethods() as $method) {
                            $declaringClass = $method->getDeclaringClass();
                            $declaringClassName = $declaringClass->getName();
                            $isDeclaredInMacroClass = same_with($declaringClassName, $class);
                            // $isFromDirectTrait = $declaringClass->isTrait() && in_array($declaringClassName, $directTraits);

                            if ($isDeclaredInMacroClass) { // || $isFromDirectTrait) {
                                $class::{$method->getName()}();
                            }
                        }
                    }
                }
            }
        }
    }
}