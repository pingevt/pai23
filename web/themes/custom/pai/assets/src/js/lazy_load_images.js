// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {

  let contentObserverOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.99,
    passive: true,
  }

  const ajaxContentObserverCallback = (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        let elem = entry.target;

        elem.src = elem.dataset.src;
        elem.srcset = elem.dataset.srcset;
        elem.classList.add("loaded");

        parent = elem.parentNode;
        parent.style = "";
        parent.classList.add('m-loaded')

        imgLazyLoadObserver.unobserve(elem);
      }

    });
  }

  let imgLazyLoadObserver = new IntersectionObserver(ajaxContentObserverCallback, contentObserverOptions);

  Drupal.behaviors.lazy_load_images = {
    attach: function (context, settings) {
      const imgEls = context.querySelectorAll(".media.media--view-mode-teaser img:not(.observing)");

      imgEls.forEach(el => {
        imgLazyLoadObserver.observe(el);
        el.classList.add("observing");
      });
    }
  }

// eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
