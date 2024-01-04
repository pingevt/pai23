import Flickity from 'flickity';
import 'flickity-imagesloaded';

// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {

  Drupal.behaviors.slideshow = {
    attach: function (context, settings) {
      const slideShowEls = Array.from(
        document.querySelectorAll('.c-inline-gallery')
      );

      console.log(slideShowEls);

      slideShowEls.forEach(el => {
        const sliderElement = el.querySelector('.c-inline-gallery__slider');
        const sliderContent = el.querySelector('.c-inline-gallery__content');

        if (sliderElement.childElementCount > 1) {
          const prevBtn = el.querySelector('.js-inline-gallery-prev');
          const nextBtn = el.querySelector('.js-inline-gallery-next');

          console.log(prevBtn, nextBtn);

          const slider = new Flickity(sliderElement, {
            draggable: true,
            freeScroll: true,
            groupCells: '100%',
            contain: true,
            cellAlign: 'left',
            prevNextButtons: false,
            pageDots: false,
            imagesLoaded: true,
            accessibility: false,
            // wrapAround: true,
            on: {
              ready: function () {
                const cards = Array.from(
                  el.querySelectorAll('.c-inline-gallery-card')
                );

                cards.forEach(card => {
                  const img = card.querySelector('img');
                  const text = card.querySelector('.c-inline-gallery-card__text');
                  sizeImageText(img, text);

                  if (img) {
                    img.addEventListener('load', () => {
                      sizeImageText(img, text);
                      scheduleResize(slider);
                    });
                  }
                });

                // Show slide control only if necessary
                const controlGroup = el.querySelector('.c-inline-gallery__controls');

                setTimeout(() => {
                  const visibleCards = el.querySelectorAll('.c-inline-gallery-card.is-selected');
                  console.log(visibleCards.length, cards.length);
                  if (visibleCards.length < cards.length) {
                    controlGroup.classList.add('is-active');
                    nextBtn.classList.add('is-active');
                    nextBtn.classList.remove("visually-hidden");
                  }

                  window.addEventListener('resize', () => {
                    cards.forEach(card => {
                      const img = card.querySelector('img');
                      const text = card.querySelector('.c-inline-gallery-card__text');
                      sizeImageText(img, text);
                    });
                    const visibleCards = el.querySelectorAll('.c-inline-gallery-card.is-selected');

                    if (visibleCards.length < cards.length) {
                      controlGroup.classList.add('is-active');
                      nextBtn.classList.add('is-active');
                      nextBtn.classList.remove("visually-hidden");
                      prevBtn.setAttribute('disabled', 'true');
                      prevBtn.classList.add("visually-hidden");
                    } else {
                      controlGroup.classList.remove('is-active');
                    }
                  });

                  window.addEventListener('resizeFlickity', () => {
                    scheduleResize(slider);
                  });

                }, "3000");
              },

              // When the slide change animation finishes...
              settle: function () {
                let slides = slider.getCellElements();

                // Run through each slide and...
                slides.forEach(function (slide) {
                  let innerFocusableElements = slide.querySelectorAll('a, button, select, input, textarea');
                  let isHidden = !slide.classList.contains('is-selected');

                  // Make focusable elements of hidden slides impossible to focus
                  innerFocusableElements.forEach(function (el) {
                    if (isHidden) {
                      el.setAttribute('tabindex', -1);
                    } else {
                      el.removeAttribute('tabindex');
                    }
                  });
                })
              }
            },
          });

          // On slide change
          slider.on('change', function (index) {
            if (index > 0) {
              prevBtn.classList.add('is-active');
              prevBtn.classList.remove("visually-hidden");
              prevBtn.removeAttribute('disabled');
            } else {
              prevBtn.classList.remove('is-active');
              prevBtn.classList.add("visually-hidden");
              prevBtn.setAttribute('disabled', 'true');
              sliderContent.focus();
            }

            if (index < slider.slides.length - 1) {
              nextBtn.classList.add('is-active');
              nextBtn.classList.remove("visually-hidden");
              nextBtn.removeAttribute('disabled');
            } else {
              nextBtn.classList.remove('is-active');
              nextBtn.classList.add("visually-hidden");
              nextBtn.setAttribute('disabled', 'true');
              sliderContent.focus();
            }
          });

          // Click Previous
          prevBtn.addEventListener('click', () => {
            slider.previous();
            setSlideAnnounce(slider);
          });

          // Click Next
          nextBtn.addEventListener('click', () => {
            slider.next();
            setSlideAnnounce(slider);
          });
        }

        flickityObserver.observe(sliderElement);
      });
    }
  }




  const sizeImageText = function (img, text) {
    if (img && text) {
      text.style.width = `${img.getBoundingClientRect().width}px`;
    }
  };

  // Resize flickity right before we view it.
  const resizeFlickityEvent = new Event('resizeFlickity');
  const flickityObserver = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0) {
        window.dispatchEvent(resizeFlickityEvent);
        obs.unobserve(entry.target);
      }
    });
  }, {
    rootMargin: "0px 0px -14% 0px",
    threshold: [0]
  });

  let isResizeScheduled = false;
  function scheduleResize(slider) {
    if (!isResizeScheduled) {
      isResizeScheduled = true;
      setTimeout(() => {
        isResizeScheduled = false;
        slider.resize();
      }, 500);
    }
  }

  function setSlideAnnounce(slider) {
    const announceEl = document.getElementById('inline-gallery-slide-announce');
    announceEl.innerHTML = 'Slide ' + (slider.selectedIndex + 1) + ' out of ' + slider.slides.length;
  }

  // eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
