<?php

namespace BitBag\SyliusWishlistPlugin\Console\Command;

use BitBag\SyliusWishlistPlugin\Entity\Wishlist;
use BitBag\SyliusWishlistPlugin\Entity\WishlistProduct;
use BitBag\SyliusWishlistPlugin\Factory\WishlistFactoryInterface;
use BitBag\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use SyliusLabs\Polyfill\Symfony\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFakeWishlistCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'bitbag:wishlist:generate-fake-wishlists';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate fake wishlists for dev purposes.')
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Sylius channel code', '')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of fake fake wishlists', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getOption('count');
        $channelCode = $input->getOption('channel');

        if (empty($channelCode)) {
            $channel = $this->channelRepository->findOneBy([]);
        } else {
            $channel = $this->channelRepository->findOneByCode($channelCode);
        }

        $usersSample = $this->customerRepository->findBy([], [], $count);
        $productsSample = $this->productRepository->findBy([], [], 10 * $count);

        for($i = 0; $i < $count; ++$i) {
            $wishlist = new Wishlist();

            /** @var Customer $customer */
            $customer = $usersSample[$i];

            $wishlist->setShopUser($customer->getUser());
            $wishlist->setName(sprintf('Auto generate list for %s', $customer->getFirstName()));

            $productsCount = rand(2, 10);

            shuffle($productsSample);

            foreach($productsSample as $product) {
                if ($product->hasChannel($channel)) {
                    /** @var ProductVariantInterface $productVariant */
                    $productVariant = $product->getVariants()[0];

                    if ($productVariant->hasChannelPricingForChannel($channel)) {
                        $pp = new WishlistProduct();

                        $pp->setProduct($product);
                        $pp->setQuantity(1);
                        $pp->setVariant($productVariant);

                        $wishlist->addWishlistProduct($pp);

                        if ($productsCount-- == 0) {
                            break;
                        }
                    }
                }
            }

            $this->em->persist($wishlist);
        }

        $this->em->flush();

        return 0;
    }
}
