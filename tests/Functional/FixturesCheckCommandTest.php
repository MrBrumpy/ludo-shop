<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Command\FixturesCheckCommand;
use App\Entity\Product;
use Symfony\Component\Console\Tester\CommandTester;

class FixturesCheckCommandTest extends FunctionalTestCase
{
    private function getTester(): CommandTester
    {
        return new CommandTester(new FixturesCheckCommand(
            $this->entityManager(),
        ));
    }

    public function testCommandPassesOnValidFixtures(): void
    {
        $tester = $this->getTester();

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('vérifications', $tester->getDisplay());
    }

    public function testCommandFailsOnInvalidData(): void
    {
        // Corrompre les fixtures : produit mature avec âge min trop bas
        $product = $this->repository(Product::class)->findOneBy([]);
        $this->assertNotNull($product);

        $product->setIsMature(true);
        $product->setMinAge(14);
        $this->entityManager()->flush();
        $this->entityManager()->clear();

        $tester = $this->getTester();
        $exitCode = $tester->execute([]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('mature', $tester->getDisplay());
    }
}
