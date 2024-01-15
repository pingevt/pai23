// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {

  const apiUrl = "/api/quick-edit";

  Drupal.behaviors.quick_edit = {
    attach: function (context, settings) {
      // console.log('HELLO WORLD');
      const qeElements = context.querySelectorAll(".qe-element:not(.qe-init)");
      // console.log(qeElements);

      qeElements.forEach(el => {

        el.addEventListener('change', (evt) => {
          let data = {};

          let el = evt.target;

          let urlSlug = el.closest('[data-api-slug]').dataset.apiSlug;

          el.classList.add('qe-working');
          el.disabled = true;

          data.id = el.dataset.entityId;
          data.field = el.dataset.field;
          data.value = el.value;
          data.property = el.dataset.property;

          fetch(apiUrl + "/" + urlSlug, {
            method: "POST",
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            'body': JSON.stringify(data),
          })
          .then(function (res) {

            return res.json();
          })
          .then(function (body) {

            el.classList.add('qe-success');
            el.classList.remove('qe-working');

            el.disabled = false;

            setTimeout(() => { el.classList.remove('qe-success'); }, 1000);
          })
          .catch(function (res) {
            console.error(res);
            el.classList.add('qe-error');
            el.classList.remove('qe-working');
            el.disabled = false;
          });
        });

        el.classList.add('qe-init');
      });
    }
  }

  // eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
