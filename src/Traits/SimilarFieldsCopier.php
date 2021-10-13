<?php

namespace Hi\Traits;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\Persistence\ObjectManager;
use http\Exception\InvalidArgumentException;

Trait SimilarFieldsCopier
{

    /**
     * This method converts the source object class into the requested class
     * By copying all the similar fields..
     *
     * @param $sourceObject
     * @param string $className
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function copyTo($sourceObject, string $className)
    {
        if (class_exists($className) === false) {
            throw new InvalidArgumentException("The class '{$className}' cannot be found!'");
        }
        $sourceObjectReflection = new \ReflectionObject($sourceObject);
        $targetObject = new $className();
        $targetReflectionClass = new \ReflectionClass($targetObject);
        foreach ($sourceObjectReflection->getProperties() as $sourceProperty) {
//            try {
                $sourcePropertyName = $sourceProperty->getName();
                $sourceProperty->setAccessible(true);
                $sourcePropertyValue = $sourceProperty->getValue($sourceObject);
                if (null !== $sourcePropertyValue && true === $targetReflectionClass->hasProperty($sourcePropertyName)) {
                    $targetMethod = $targetReflectionClass->getMethod("set{$sourcePropertyName}");
                    $targetMethod->invoke($targetObject, $sourcePropertyValue);
//                    $targetProperty = $targetReflectionClass->getProperty($sourcePropertyName);
//                    $targetProperty->setAccessible(true);
//                    $targetProperty->setValue($targetObject, $sourcePropertyValue);
                } else {
                    throw new \LogicException("Error Property Tax \"{$sourcePropertyName}\" not exists !");
                }
//            } catch (\ReflectionException $exception) {
//                continue;
//            }
        }
        return $targetObject;
    }

}
