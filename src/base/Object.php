<?php

namespace bitaps\base;

use phpDocumentor\Reflection\DocBlock;

if (class_exists('\phpDocumentor\Reflection\DocBlock')) {

} else {
    require_once __DIR__ . '/../../../../vendor/phpdocumentor/reflection-dockblock/src/phpDocumentor/Reflection/DocBlock.php';
}

class Object
{
    protected $otherAttributes = [
    ];
    protected $config;

    /**
     * Object constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $reflection = new \ReflectionClass($this);

        $filled = false;

        if (is_array($config) && count($config) > 0) {
            foreach ($config as $attribute => $value) {
                if (!property_exists($this, $attribute)) {
                    $this->otherAttributes[$attribute] = $value;
                    continue;
                }

                $this->{$attribute} = $value;
                $filled = true;
                $property = $reflection->getProperty($attribute);

                if (!($doc = new DocBlock($property->getDocComment()))) {
                    continue;
                }

                /** @var DocBlock\Tag\VarTag $tag */
                if (!($tags = $doc->getTagsByName('var'))) {
                    continue;
                }
                $tag = $tags[0];
                $type = $tag->getType();

                if (strpos($type, '[]') !== false) {
                    $class = str_replace('[]', '', $tag->getType());
                    if (!class_exists($class)) {
                        continue;
                    }

                    $this->{$attribute} = [];
                    foreach ($value as $item) {
                        $this->{$attribute}[] = new $class($item);
                    }
                } else {
                    if (class_exists($type)) {
                        $this->{$attribute} = new $type($value);
                    }
                }
            }
        }

        if (!$filled) {
            $index = 0;
            foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if (is_array($config) && isset($config[$index])) {
                    $this->{$property->getName()} = $config[$index];
                    $filled = true;
                }
                $index++;
            }
        }

        if (!$filled) {
            $this->config = $config;
        }
    }

    /**
     * @return array
     */
    public function getOtherAttributes()
    {
        return $this->otherAttributes;
    }
}