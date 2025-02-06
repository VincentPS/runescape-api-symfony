<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', method: 'onKernelRequest')]
class RequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        $ignoredRoutes = ['welcome'];

        /** @var string $currentRoute */
        $currentRoute = $event->getRequest()->attributes->get('_route');

        if (
            !str_starts_with($currentRoute, '_wdt')
            && !str_starts_with($currentRoute, '_profiler')
            && !in_array($currentRoute, $ignoredRoutes, true)
            && empty($event->getRequest()->getSession()->get('currentPlayerName'))
        ) {
            $event->setResponse(new RedirectResponse('/welcome'));
        }

        if ($currentRoute === 'welcome' && !empty($event->getRequest()->getSession()->get('currentPlayerName'))) {
            $event->setResponse(new RedirectResponse('/'));
        }
    }
}
