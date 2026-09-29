<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Address;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:fixtures:check', description: 'Check fixture data consistency')]
class FixturesCheckCommand extends Command
{
    private int $errorCount = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->errorCount = 0;

        $output->writeln('Vérification de la cohérence des fixtures...');

        $this->checkUniqueProductReferences($output);
        $this->checkMatureProductsMinAge($output);
        $this->checkWishlistMinorMaturity($output);
        $this->checkUniqueDefaultAddress($output);

        if ($this->errorCount > 0) {
            $output->writeln(sprintf('<error>Échec : %d incohérence(s) détectée(s).</error>', $this->errorCount));

            return Command::FAILURE;
        }

        $output->writeln('<info>Toutes les vérifications sont passées.</info>');

        return Command::SUCCESS;
    }

    private function checkUniqueProductReferences(OutputInterface $output): void
    {
        /** @var list<Product> $products */
        $products = $this->em->getRepository(Product::class)->findAll();

        /** @var array<string, int> $refs */
        $refs = [];
        foreach ($products as $product) {
            $ref = $product->getReference();
            $refs[$ref] = ($refs[$ref] ?? 0) + 1;
        }

        foreach ($refs as $ref => $count) {
            if ($count > 1) {
                $output->writeln(sprintf('  <error>ERREUR</error> Référence "%s" utilisée %d fois.', $ref, $count));
                ++$this->errorCount;
            }
        }

        if (0 === $this->errorCount) {
            $output->writeln(sprintf('  <info>OK</info> %d produits, toutes les références sont uniques.', count($products)));
        }
    }

    private function checkMatureProductsMinAge(OutputInterface $output): void
    {
        /** @var list<Product> $matureProducts */
        $matureProducts = $this->em->getRepository(Product::class)->findBy(['isMature' => true]);

        foreach ($matureProducts as $product) {
            if (null !== $product->getMinAge() && $product->getMinAge() < 18) {
                $output->writeln(sprintf(
                    '  <error>ERREUR</error> Produit "%s" (mature) a minAge=%d < 18.',
                    $product->getName(),
                    $product->getMinAge(),
                ));
                ++$this->errorCount;
            }
        }

        $output->writeln(sprintf('  <info>OK</info> %d produit(s) mature(s) vérifié(s).', count($matureProducts)));
    }

    private function checkWishlistMinorMaturity(OutputInterface $output): void
    {
        /** @var list<WishlistItem> $wishlistItems */
        $wishlistItems = $this->em->getRepository(WishlistItem::class)->findAll();

        foreach ($wishlistItems as $item) {
            $user = $item->getUser();
            $product = $item->getProduct();

            if ($this->isMinor($user) && $product->isMature()) {
                $output->writeln(sprintf(
                    '  <error>ERREUR</error> Le mineur "%s" a le produit mature "%s" dans sa wishlist.',
                    $user->getEmail(),
                    $product->getName(),
                ));
                ++$this->errorCount;
            }
        }

        $output->writeln(sprintf('  <info>OK</info> %d élément(s) de wishlist vérifié(s).', count($wishlistItems)));
    }

    private function checkUniqueDefaultAddress(OutputInterface $output): void
    {
        /** @var list<Address> $addresses */
        $addresses = $this->em->getRepository(Address::class)->findAll();

        /** @var array<int, list<Address>> $byUser */
        $byUser = [];
        foreach ($addresses as $address) {
            $userId = $address->getUser()->getId();

            if (null === $userId) {
                $output->writeln(
                    '  <error>ERREUR</error> Une adresse est associée à un utilisateur sans identifiant.'
                );
                ++$this->errorCount;
                continue;
            }

            $byUser[$userId][] = $address;
        }

        foreach ($byUser as $userId => $userAddresses) {
            $defaultCount = 0;
            foreach ($userAddresses as $address) {
                if ($address->isDefault()) {
                    ++$defaultCount;
                }
            }
            if ($defaultCount > 1) {
                $output->writeln(sprintf(
                    '  <error>ERREUR</error> L\'utilisateur #%d a %d adresses par défaut.',
                    $userId,
                    $defaultCount,
                ));
                ++$this->errorCount;
            }
        }

        $output->writeln(sprintf('  <info>OK</info> %d adresse(s) vérifiée(s).', count($addresses)));
    }

    private function isMinor(User $user): bool
    {
        $birthDate = $user->getBirthDate();
        if (null === $birthDate) {
            return false;
        }

        return $birthDate > new \DateTimeImmutable('-18 years');
    }
}
