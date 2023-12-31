<?php

namespace Drupal\pai_utility\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Class EventSubscriber.
 */
class TaxonomySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest', 28];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(KernelEvent $event) {
    $this->processTerm($event);
  }

  /**
   * Process events generically invoking rabbit hole behaviors if necessary.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  private function processTerm(KernelEvent $event) {
    $request = $event->getRequest();

    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with rabbit hole.
    if ($request->get('exception') != NULL) {
      return FALSE;
    }

    // Get the route from the request.
    if ($route = $request->get('_route')) {
      // Only continue if the request route is the an entity canonical.
      if (preg_match('/^entity\.(.+)\.canonical$/', $route)) {
        $entity = $request->get('taxonomy_term');

        if (isset($entity) && $entity instanceof ContentEntityInterface && $entity->bundle() == "tech") {

          $str = "/work?tech_id[" . $entity->id() . "]=" . $entity->id();
          $response = new RedirectResponse($str);
          $event->setResponse($response);

        }
      }
    }
  }

}
