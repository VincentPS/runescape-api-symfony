<?php

namespace App\Doctrine\Type;

use App\Dto\JsonbDTOInterface;
use App\Exception\Jsonb\JsonbConvertToDatabaseValueException;
use App\Exception\Jsonb\JsonbConvertToPHPValueException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use MartinGeorgiev\Doctrine\DBAL\Types\Jsonb;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class CustomJsonbType extends Jsonb
{
    abstract protected function getDtoFqcn(): string;

    /**
     * @return array<int, NormalizerInterface|DenormalizerInterface>
     */
    protected function normalizers(): array
    {
        return [
            new ArrayDenormalizer(),
            new BackedEnumNormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
        ];
    }

    /**
     * @return array<int, EncoderInterface|DecoderInterface>
     */
    protected function encoders(): array
    {
        return [
            new JsonEncoder()
        ];
    }

    /**
     * @return JsonbDTOInterface[]
     * @throws JsonbConvertToPHPValueException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        if (!is_string($value)) {
            throw new JsonbConvertToPHPValueException(
                'CustomJsonbType::convertToPHPValue() must receive a string'
            );
        }

        $result = $this->getSerializer()->deserialize($value, $this->getDtoFqcn() . '[]', 'json');

        if (!is_array($result)) {
            throw new JsonbConvertToPHPValueException(
                'CustomJsonbType::convertToPHPValue() must return an array of ' . $this->getDtoFqcn()
            );
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @throws JsonbConvertToDatabaseValueException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (!is_array($value)) {
            throw new JsonbConvertToDatabaseValueException(
                'CustomJsonbType::convertToDatabaseValue() must receive an array got ' . gettype($value)
            );
        }

        foreach ($value as $item) {
            if (!is_a($item, $this->getDtoFqcn())) {
                throw new JsonbConvertToDatabaseValueException(
                    'CustomJsonbType::convertToDatabaseValue() must receive an array of ' .
                    $this->getDtoFqcn() . ' got ' . gettype($item)
                );
            }
        }

        return $this->getSerializer()->serialize($value, 'json');
    }

    private function getSerializer(): Serializer
    {
        return new Serializer(
            normalizers: $this->normalizers(),
            encoders: $this->encoders()
        );
    }
}
