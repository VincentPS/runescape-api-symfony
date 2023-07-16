<?php

namespace App\Doctrine\Type;

use App\Dto\Activity;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ActivityType extends CustomJsonbType
{
    public const NAME = 'activity';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getDtoFqcn(): string
    {
        return Activity::class;
    }

    /**
     * @return array<int, NormalizerInterface|DenormalizerInterface>
     */
    protected function normalizers(): array
    {
        return [
            new DateTimeNormalizer(['datetime_format' => 'Y-m-d H:i:s']),
            new ArrayDenormalizer(),
            new BackedEnumNormalizer(),
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor())
        ];
    }
}
