// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {

  let mediaEls = [];
  let panPixelsPerSecond = 150;

  Drupal.behaviors.vertical_pan = {
    attach: function (context, settings) {

      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        mediaEls = context.querySelectorAll(".media.media--view-mode-vertical-pan:not(.vp-init)");

        mediaEls.forEach(el => {

          let imgChildren = el.getElementsByTagName('img');
          if (imgChildren.length >= 1) {

            let img = imgChildren[0];

            if (img.complete) {
              var contStyle = window.getComputedStyle(el);

              let panHeight = parseFloat(parseFloat(contStyle.getPropertyValue('width')) * img.clientHeight / img.clientWidth) - parseFloat(contStyle.getPropertyValue('height'));
              img.dataset.panHeight = panHeight;

              let panDuration = panHeight / panPixelsPerSecond;
              img.dataset.panDuration = panDuration;

              el.classList.add("vp-init");

              img.style.transitionDuration = `${panDuration}s`;
              img.style.transform = `translateY(-${panHeight}px)`;
            }
            else {

              img.addEventListener('load', (evt) => {
                var contStyle = window.getComputedStyle(el);

                let panHeight = parseFloat(parseFloat(contStyle.getPropertyValue('width')) * img.clientHeight / img.clientWidth) - parseFloat(contStyle.getPropertyValue('height'));
                img.dataset.panHeight = panHeight;

                let panDuration = panHeight / panPixelsPerSecond;
                img.dataset.panDuration = panDuration;

                el.classList.add("vp-init");

                img.style.transitionDuration = `${panDuration}s`;
                img.style.transform = `translateY(-${panHeight}px)`;
              });
            }

            img.addEventListener('transitionend', transitionEvent, {once: true});

            img.addEventListener('mouseover', (evt) => {
              var computedStyle = window.getComputedStyle(evt.target),
                transform = computedStyle.getPropertyValue('transform');

              el.classList.add("vp-init");
              evt.target.style.transform = transform;

            });

            img.addEventListener('mouseout', (evt) => {

              let panHeight = evt.target.dataset.panHeight;
              let computedStyle = window.getComputedStyle(evt.target),
                transform = computedStyle.getPropertyValue('transform'),
                matrix = new WebKitCSSMatrix(transform);

              let currentY = matrix.m42;
              let remainingDist = panHeight*1 + currentY;
              let newDur = Math.abs(remainingDist) / panPixelsPerSecond;

              el.classList.remove("vp-init");
              evt.target.style.transitionDuration = `${newDur}s`;
              evt.target.style.transitionDelay = `0s`;
              evt.target.style.transform = `translateY(-${panHeight}px)`;

            });

          }
        });
      }
    }
  }

  // Function to be called for `transitionend` event.
  function transitionEvent(evt) {

    evt.target.classList.add('vp-resetting');
    evt.target.style.transform = `translateY(0%)`;

    setTimeout((evt) => {
      evt.target.classList.remove('vp-resetting');
      evt.target.style.transitionDuration = `${evt.target.dataset.panDuration}s`;
      evt.target.style.transform = `translateY(-${evt.target.dataset.panHeight}px)`;
      evt.target.addEventListener('transitionend', transitionEvent, { once: true });
    }, 1000, evt);
  }

// eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
