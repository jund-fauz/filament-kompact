<?php

namespace JundFauz\FilamentKompact;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;

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
        foreach (Macros::lists as $macroClass) {
            foreach ((new ReflectionClass($macroClass))->getMethods() as $method) {
                if ($method->isStatic() && $method->isPublic() && $method->getDeclaringClass()->getName() === $macroClass) {
                    $macroClass::{$method->getName()}();
                }
            }
        }
    }
}