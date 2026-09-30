<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;

class SchemaValidationTest extends FunctionalTestCase
{
    public function testSchemaIsSynchronized(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->entityManager();

        $validator = new SchemaValidator($em);

        $this->assertTrue(
            $validator->schemaInSyncWithMetadata(),
            'Le schéma Doctrine n\'est pas synchronisé avec les métadonnées.',
        );
    }
}
