'use strict';
document.addEventListener('DOMContentLoaded', function () {
  const priceDurationToggler = document.querySelector('.price-duration-toggler');
  const pricingCards = document.querySelectorAll('.price-toggle');
  const planChooseBtns = document.querySelectorAll('.plan-choose-btn');

  if (priceDurationToggler) {
    priceDurationToggler.addEventListener('change', function () {
      const isAnnually = this.checked;

      pricingCards.forEach(card => {
        const price = isAnnually ? card.dataset.priceAnnually : card.dataset.priceMonthly;
        card.innerHTML = new Intl.NumberFormat('fr-FR').format(price);

        const durationEl = card.nextElementSibling;
        if(durationEl) {
            durationEl.innerHTML = isAnnually ? '/an' : '/mois';
        }
      });

      planChooseBtns.forEach(btn => {
        let href = btn.href.split('/');
        href.pop();
        href.push(isAnnually ? 'annually' : 'monthly');
        btn.href = href.join('/');
      });
    });
  }
});