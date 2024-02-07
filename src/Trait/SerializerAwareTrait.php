<?php

namespace App\Trait;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

trait SerializerAwareTrait
{
    protected Serializer $serializer;

    protected function getSerializer(): Serializer
    {
        if (!isset($this->serializer)) {
            $this->serializer = new Serializer(
                [
                    new DateTimeNormalizer(),
                    new ArrayDenormalizer(),
                    new BackedEnumNormalizer(),
                    new ObjectNormalizer(
                        classMetadataFactory: new ClassMetadataFactory(new AttributeLoader()),
                        propertyTypeExtractor: new ReflectionExtractor()
                    )
                ],
                [
                    new JsonEncoder()
                ]
            );
        }

        return $this->serializer;
    }
}
