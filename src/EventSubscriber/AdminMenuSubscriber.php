<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusWishlistPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => [['addAdminMenuItems', 1]],
        ];
    }

    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu()->getChild('customers');

        $menu
            ->addChild('wishlists', [
                'route' => 'bitbag_plugin_wishlist_admin_wishlist_index',
                'routeParameters' => []
            ])
            ->setLabel('bitbag_sylius_wishlist_plugin.ui.wishlist')
            ->setLabelAttribute('icon', 'heart')
        ;
    }
}
