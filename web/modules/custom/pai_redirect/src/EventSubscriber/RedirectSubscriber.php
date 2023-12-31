<?php

namespace Drupal\pai_redirect\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class EventSubscriber.
 */
class RedirectSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onException($event) {
    // Grab the exception.
    $exception = $event->getThrowable();

    // Make the exception available for example when rendering a block.
    $request = $event->getRequest();
    $request->attributes->set('exception', $exception);

    $handled_formats = $this->getHandledFormats();

    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    if ($exception instanceof HttpExceptionInterface && empty($handled_formats) || in_array($format, $handled_formats)) {
      $method = 'on' . $exception->getStatusCode();
      // Keep just the leading number of the status code to produce either a
      // on400 or a 500 method callback.
      $method_fallback = 'on' . substr($exception->getStatusCode(), 0, 1) . 'xx';
      // We want to allow the method to be called and still not set a response
      // if it has additional filtering logic to determine when it will apply.
      // It is therefore the method's responsibility to set the response on the
      // event if appropriate.
      if (method_exists($this, $method)) {
        $this->$method($event);
      }
      elseif (method_exists($this, $method_fallback)) {
        $this->$method_fallback($event);
      }
    }
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the request.
   */
  public function on404(KernelEvent $event) {

    $old_url = $event->getRequest()->getUri();
    preg_replace('/(stoppers|projects|pencils|pens|ornaments)\/(20|21)-[0-9]{3}/i', '', $old_url, 1, $count);

    if ($count > 0) {
      $response = new TrustedRedirectResponse('/node/39', 301);
      $event->setResponse($response);
    }
  }

}
